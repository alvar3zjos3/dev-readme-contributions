<?php

declare(strict_types=1);

/**
 * Genera las estadísticas de racha de un usuario de GitHub a partir de parámetros tipo request.
 *
 * @param string $user Nombre de usuario de GitHub del que se obtendrán estadísticas
 * @param array<string,mixed> $params Opciones que afectan a la consulta y al cálculo de la racha
 * @return array<string,mixed> Estadísticas de racha calculadas
 */
function generateStreakStats(string $user, array $params = []): array
{
    // Elimina caracteres no permitidos para evitar consultas inválidas.
    $user = preg_replace("/[^a-zA-Z0-9\-]/", "", $user);

    if ($user === "") {
        throw new InvalidArgumentException("Se requiere un nombre de usuario de GitHub.", 400);
    }

    // Obtiene y normaliza las opciones recibidas desde la URL.
    $startingYear = isset($params["starting_year"]) ? intval($params["starting_year"]) : null;
    $mode = isset($params["mode"]) ? strval($params["mode"]) : null;
    $excludeDaysRaw = isset($params["exclude_days"]) ? strval($params["exclude_days"]) : "";
    $timezone = isset($params["timezone"]) ? strval($params["timezone"]) : "";

    // Crea una clave de opciones para guardar y recuperar resultados de caché.
    $cacheOptions = [
        "starting_year" => $startingYear,
        "mode" => $mode,
        "exclude_days" => $excludeDaysRaw,
        "timezone" => $timezone,
    ];

    // Permite desactivar la caché con DISABLE_CACHE=true.
    $disableCache = $_ENV["DISABLE_CACHE"] ?? $_SERVER["DISABLE_CACHE"] ?? getenv("DISABLE_CACHE") ?: "false";
    $useCache = strtolower((string) $disableCache) !== "true";

    // Recupera datos almacenados durante 24 horas, salvo que la caché esté desactivada.
    $cachedStats = $useCache ? getCachedStats($user, $cacheOptions) : null;

    // Devuelve la caché si sigue siendo válida para la zona horaria solicitada.
    if (!statsMissingOrStale($cachedStats, $timezone)) {
        return $cachedStats;
    }

    // Obtiene datos actualizados desde la API de GitHub.
    $contributionGraphs = getContributionGraphs($user, $startingYear);
    $contributions = getContributionDates($contributionGraphs, $timezone);

    // Calcula las estadísticas según el modo de racha solicitado.
    if ($mode === "weekly") {
        $stats = getWeeklyContributionStats($contributions);
    } else {
        // Separa y normaliza los días excluidos del cálculo diario.
        $excludeDays = normalizeDays(explode(",", $excludeDaysRaw));
        $stats = getContributionStats($contributions, $excludeDays);
    }

    // Almacena las estadísticas durante 24 horas si la caché está habilitada.
    if ($useCache) {
        setCachedStats($user, $cacheOptions, $stats);
    }

    return $stats;
}

/**
 * Comprueba si las estadísticas almacenadas no existen o están desactualizadas.
 *
 * Una racha puede quedar obsoleta si termina antes del día actual, o antes del
 * comienzo de la semana actual cuando se utiliza el modo semanal.
 *
 * @param array|null $cachedStats Estadísticas almacenadas que se van a comprobar
 * @param string $timezone Identificador de zona horaria o cadena vacía para usar la del servidor
 * @return bool True si las estadísticas deben volver a solicitarse; false si siguen siendo válidas
 */
function statsMissingOrStale(?array $cachedStats, string $timezone = ""): bool
{
    // Sin caché no hay datos que reutilizar.
    if ($cachedStats === null) {
        return true;
    }

    // Si la estructura no tiene los campos necesarios, se considera inválida.
    if (!isset($cachedStats["currentStreak"]["end"]) || !isset($cachedStats["currentStreak"]["length"])) {
        return true;
    }

    // Una racha de longitud 0 se actualiza para detectar actividad nueva sin esperar 24 horas.
    $currentStreakLength = $cachedStats["currentStreak"]["length"];
    if ($currentStreakLength === 0) {
        return true;
    }

    // Comprueba si la racha termina antes de hoy o, en modo semanal, antes de la semana actual.
    $currentStreakEnd = $cachedStats["currentStreak"]["end"];
    $mode = $cachedStats["mode"] ?? "daily";

    if ($mode === "weekly") {
        $today = getCurrentDate($timezone);
        $startOfWeek = getPreviousSunday($today);

        return $currentStreakEnd < $startOfWeek;
    }

    return $currentStreakEnd < getCurrentDate($timezone);
}