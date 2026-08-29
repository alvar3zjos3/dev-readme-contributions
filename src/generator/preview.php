<?php

declare(strict_types=1);

const DEFAULT_TIMEZONE = "Europe/Madrid";

require_once "../card.php";
require_once "../stats.php";
require_once "../cache.php";

/**
 * Obtiene un parámetro GET como texto normalizado.
 *
 * Si el parámetro no existe, contiene un array o no es una cadena, devuelve
 * el valor predeterminado indicado.
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
 * Obtiene y valida el año inicial opcional de las contribuciones.
 *
 * @return int|null Año inicial válido o null si no se indicó
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
 * Si timezone no llega o llega vacío, se emplea Europe/Madrid internamente.
 * El valor predeterminado no necesita estar presente en la URL.
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
 * Valida el formato de un nombre de usuario de GitHub.
 *
 * Los usuarios válidos contienen de 1 a 39 caracteres alfanuméricos o
 * guiones; no pueden empezar ni terminar con un guion.
 *
 * @param string $user Usuario de GitHub
 * @return void
 *
 * @throws InvalidArgumentException Si el usuario es inválido
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
 * Obtiene estadísticas de contribuciones reales desde caché o GitHub.
 *
 * @param string $user Usuario de GitHub
 * @param string $mode Modo: daily o weekly
 * @param array<string> $excludedDays Días excluidos en modo diario
 * @param string $timezone Zona horaria IANA efectiva
 * @param int|null $startingYear Año inicial opcional
 * @return array<string,mixed> Estadísticas listas para renderizar
 */
function getRealContributionStats(
    string $user,
    string $mode,
    array $excludedDays,
    string $timezone,
    ?int $startingYear,
): array {
    /*
     * Usa la zona efectiva para que una solicitud sin timezone y otra con
     * timezone=Europe/Madrid compartan la misma entrada de caché.
     */
    $cacheOptions = [
        "mode" => $mode,
        "exclude_days" => $mode === "daily"
            ? implode(",", $excludedDays)
            : "",
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

/**
 * Convierte un código de excepción en un código HTTP seguro.
 *
 * @param int $code Código original
 * @return int Código HTTP válido
 */
function getHttpErrorCode(int $code): int
{
    return $code >= 400 && $code <= 599 ? $code : 500;
}

try {
    $user = getQueryString("user");
    $mode = getQueryString("mode", "daily");
    $timezone = getEffectiveTimezone(getQueryString("timezone"));
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
     * Configura la zona solo tras validarla. Esto asegura que las funciones
     * date(), DateTime y los cálculos semanales usan la zona solicitada.
     */
    date_default_timezone_set($timezone);

    // Los días excluidos no forman parte del cálculo semanal.
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
     * renderOutput() establece el tipo de contenido apropiado:
     * SVG:  image/svg+xml
     * PNG:  image/png
     * JSON: application/json
     */
    renderOutput($stats);
} catch (InvalidArgumentException | AssertionError $error) {
    $errorCode = getHttpErrorCode((int) $error->getCode());

    error_log("Error {$errorCode}: {$error->getMessage()}");

    if ($errorCode >= 500) {
        error_log($error->getTraceAsString());
    }

    renderOutput($error->getMessage(), $errorCode);
} catch (Throwable $error) {
    /*
     * No expone detalles técnicos del servidor al usuario. La traza completa
     * queda disponible únicamente en el log del proceso PHP.
     */
    error_log("Error inesperado: {$error->getMessage()}");
    error_log($error->getTraceAsString());

    renderOutput(
        "No se pudieron generar las estadísticas en este momento.",
        500,
    );
}