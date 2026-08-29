<?php

declare(strict_types=1);

const DEFAULT_TIMEZONE = "Europe/Madrid";

require_once "../card.php";
require_once "../stats.php";
require_once "../cache.php";

/**
 * Obtiene un parámetro de consulta como texto seguro.
 *
 * Si el parámetro no existe, es un array o no es una cadena, devuelve el
 * valor predeterminado indicado.
 *
 * @param string $name Nombre del parámetro GET
 * @param string $default Valor predeterminado
 * @return string Valor normalizado
 */
function getQueryString(string $name, string $default = ""): string
{
    $value = $_GET[$name] ?? $default;

    return is_string($value) ? trim($value) : $default;
}

/**
 * Obtiene y valida el año inicial opcional para calcular contribuciones.
 *
 * @return int|null Año inicial válido o null si no se indica
 *
 * @throws InvalidArgumentException Si el año es inválido
 */
function getStartingYear(): ?int
{
    $value = getQueryString("starting_year");

    if ($value === "") {
        return null;
    }

    if (!ctype_digit($value)) {
        throw new InvalidArgumentException(
            "El año inicial debe ser un número entero válido.",
            400,
        );
    }

    $year = (int) $value;
    $currentYear = (int) date("Y");

    if ($year < 2005 || $year > $currentYear) {
        throw new InvalidArgumentException(
            "El año inicial debe estar entre 2005 y {$currentYear}.",
            400,
        );
    }

    return $year;
}

/**
 * Devuelve una zona horaria IANA válida.
 *
 * Si el parámetro timezone no se incluye o llega vacío, la aplicación usa
 * Europe/Madrid internamente sin reflejarlo en la URL.
 *
 * @param string $requestedTimezone Zona horaria enviada por el cliente
 * @return string Zona horaria efectiva
 *
 * @throws InvalidArgumentException Si la zona horaria es inválida
 */
function getEffectiveTimezone(string $requestedTimezone): string
{
    $timezone = $requestedTimezone !== ""
        ? $requestedTimezone
        : DEFAULT_TIMEZONE;

    try {
        new DateTimeZone($timezone);
    } catch (Exception) {
        throw new InvalidArgumentException(
            "La zona horaria indicada no es válida. Usa un identificador IANA, por ejemplo Europe/Madrid.",
            400,
        );
    }

    return $timezone;
}

/**
 * Valida el formato de un usuario de GitHub.
 *
 * GitHub permite entre 1 y 39 caracteres: letras, números o guiones. Un
 * nombre de usuario no puede comenzar ni terminar con un guion.
 *
 * @param string $user Usuario de GitHub
 * @return void
 *
 * @throws InvalidArgumentException Si el usuario está vacío o es inválido
 */
function validateGitHubUsername(string $user): void
{
    if ($user === "") {
        throw new InvalidArgumentException(
            "Debes introducir un usuario de GitHub.",
            400,
        );
    }

    if (!preg_match("/^[A-Za-z\d](?:[A-Za-z\d-]{0,37}[A-Za-z\d])?$/", $user)) {
        throw new InvalidArgumentException(
            "El nombre de usuario de GitHub no es válido.",
            400,
        );
    }
}

/**
 * Obtiene estadísticas reales desde caché o desde la API GraphQL de GitHub.
 *
 * @param string $user Usuario de GitHub
 * @param string $mode Modo de cálculo: daily o weekly
 * @param array<string> $excludedDays Días excluidos para modo diario
 * @param string $timezone Zona horaria IANA efectiva
 * @param int|null $startingYear Año inicial opcional
 * @return array<string,mixed> Estadísticas preparadas para renderizar
 */
function getRealContributionStats(
    string $user,
    string $mode,
    array $excludedDays,
    string $timezone,
    ?int $startingYear,
): array {
    /*
     * Se guarda la zona efectiva en caché, no el valor crudo de GET. Así,
     * una petición sin timezone y otra con timezone=Europe/Madrid usan la
     * misma clave de caché.
     */
    $cacheOptions = [
        "mode" => $mode,
        "exclude_days" => $mode === "daily" ? implode(",", $excludedDays) : "",
        "starting_year" => $startingYear,
        "timezone" => $timezone,
    ];

    $cachedStats = getCachedStats($user, $cacheOptions);

    if ($cachedStats !== null) {
        return $cachedStats;
    }

    $contributionGraphs = getContributionGraphs($user, $startingYear);
    $contributions = getContributionDates($contributionGraphs, $timezone);

    $stats = $mode === "weekly"
        ? getWeeklyContributionStats($contributions)
        : getContributionStats($contributions, $excludedDays);

    if (!setCachedStats($user, $cacheOptions, $stats)) {
        error_log("No se pudieron guardar en caché las estadísticas de {$user}.");
    }

    return $stats;
}

try {
    $user = getQueryString("user");
    $mode = getQueryString("mode", "daily");
    $requestedTimezone = getQueryString("timezone");
    $timezone = getEffectiveTimezone($requestedTimezone);
    $startingYear = getStartingYear();
    $excludedDays = normalizeDays(
        explode(",", getQueryString("exclude_days")),
    );

    if (!in_array($mode, ["daily", "weekly"], true)) {
        throw new InvalidArgumentException(
            "El modo debe ser daily o weekly.",
            400,
        );
    }

    validateGitHubUsername($user);

    /*
     * Se establece solo después de validar. Esto garantiza que date(), time(),
     * DateTime y las funciones semanales usen la zona de cálculo efectiva.
     */
    date_default_timezone_set($timezone);

    // Los días excluidos solo se aplican al cálculo diario.
    if ($mode === "weekly") {
        $excludedDays = [];
    }

    $stats = getRealContributionStats(
        $user,
        $mode,
        $excludedDays,
        $timezone,
        $startingYear,
    );

    /*
     * renderOutput() decide automáticamente el Content-Type:
     * - SVG: image/svg+xml
     * - PNG: image/png
     * - JSON: application/json
     */
    renderOutput($stats);
} catch (InvalidArgumentException | AssertionError $error) {
    $errorCode = $error->getCode();

    if (!is_int($errorCode) || $errorCode < 400 || $errorCode > 599) {
        $errorCode = 500;
    }

    error_log("Error {$errorCode}: {$error->getMessage()}");

    if ($errorCode >= 500) {
        error_log($error->getTraceAsString());
    }

    renderOutput($error->getMessage(), $errorCode);
}