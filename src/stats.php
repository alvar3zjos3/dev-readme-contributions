<?php

declare(strict_types=1);

/**
 * Carga las variables de entorno desde el archivo .env de la raíz del proyecto.
 *
 * No sobrescribe variables ya definidas por el sistema, Docker, Apache,
 * Nginx/PHP-FPM o la terminal. Por tanto, permite usar el mismo código
 * tanto en desarrollo local como en producción.
 *
 * @return void
 */
function loadEnvironmentVariables(): void
{
    static $loaded = false;

    if ($loaded) {
        return;
    }

    /*
     * stats.php se encuentra en src/. Por lo tanto, dirname(__DIR__)
     * corresponde a la raíz del repositorio:
     *
     * dev-readme-contributions/
     * ├── .env
     * └── src/
     *     └── stats.php
     */
    $environmentFile = dirname(__DIR__) . "/.env";

    if (!is_readable($environmentFile)) {
        $loaded = true;
        return;
    }

    $lines = file(
        $environmentFile,
        FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES,
    );

    if ($lines === false) {
        $loaded = true;
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Ignora comentarios y líneas vacías.
        if ($line === "" || str_starts_with($line, "#")) {
            continue;
        }

        // Cada variable debe tener formato NOMBRE=VALOR.
        if (!str_contains($line, "=")) {
            continue;
        }

        [$name, $value] = explode("=", $line, 2);

        $name = trim($name);
        $value = trim($value);

        // Solo permite nombres de variables de entorno válidos.
        if (
            $name === ""
            || !preg_match("/^[A-Za-z_][A-Za-z0-9_]*$/", $name)
        ) {
            continue;
        }

        // Elimina comillas externas, si las hay.
        if (
            strlen($value) >= 2
            && (
                ($value[0] === '"' && $value[strlen($value) - 1] === '"')
                || ($value[0] === "'" && $value[strlen($value) - 1] === "'")
            )
        ) {
            $value = substr($value, 1, -1);
        }

        /*
         * No sobrescribe un valor establecido explícitamente fuera de .env.
         * Esto es importante para producción y para usar export TOKEN=...
         * temporalmente desde Git Bash.
         */
        if (getenv($name) === false) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    $loaded = true;
}

loadEnvironmentVariables();

require_once "whitelist.php";

/**
 * Construye la consulta GraphQL para obtener el calendario de contribuciones de un año.
 *
 * @param string $user Nombre de usuario de GitHub
 * @param int $year Año del que se obtendrán las contribuciones
 * @return string Consulta GraphQL formateada
 */
function buildContributionGraphQuery(string $user, int $year): string
{
    $start = "{$year}-01-01T00:00:00Z";
    $end = "{$year}-12-31T23:59:59Z";

    return "query {
        user(login: \"{$user}\") {
            createdAt
            contributionsCollection(from: \"{$start}\", to: \"{$end}\") {
                contributionYears
                contributionCalendar {
                    weeks {
                        contributionDays {
                            contributionCount
                            date
                        }
                    }
                }
            }
        }
    }";
}

/**
 * Ejecuta múltiples peticiones cURL en paralelo y gestiona límites de API y reintentos.
 *
 * @param string $user Nombre de usuario de GitHub
 * @param array<int> $years Lista de años a consultar
 * @return array<int,stdClass> Respuestas GraphQL decodificadas con los años como clave
 */
function executeContributionGraphRequests(string $user, array $years): array
{
    $tokens = [];
    $requests = [];

    // Prepara las peticiones individuales para cada año.
    foreach ($years as $year) {
        $tokens[$year] = getGitHubToken();
        $query = buildContributionGraphQuery($user, $year);
        $requests[$year] = getGraphQLCurlHandle($query, $tokens[$year]);
    }

    // Inicializa multi-cURL para ejecutar consultas en paralelo.
    $multi = curl_multi_init();

    foreach ($requests as $handle) {
        curl_multi_add_handle($multi, $handle);
    }

    // Espera activa controlada para resolver todas las peticiones.
    $running = null;

    do {
        $status = curl_multi_exec($multi, $running);

        if ($running) {
            curl_multi_select($multi);
        }
    } while ($running > 0 && $status === CURLM_OK);

    // Procesa las respuestas obtenidas.
    $responses = [];

    foreach ($requests as $year => $handle) {
        $contents = curl_multi_getcontent($handle);
        $decoded = is_string($contents) ? json_decode($contents) : null;

        // Si la respuesta está vacía o contiene errores, reintenta de forma individual.
        if (empty($decoded) || empty($decoded->data) || !empty($decoded->errors)) {
            $curlErrno = curl_errno($handle);
            $curlError = curl_error($handle);
            $message = $decoded->errors[0]->message
                ?? ($decoded->message ?? "Ocurrió un error en la API ({$curlErrno}: {$curlError}).");
            $errorType = $decoded->errors[0]->type ?? "";

            // Error de certificado SSL.
            if ($curlErrno === 60) {
                throw new AssertionError("Error de certificado SSL: {$curlError}", 500);
            }
            // Otros errores de conexión cURL.
            elseif ($curlErrno) {
                throw new AssertionError("Error de cURL: {$curlError}", 500);
            }
            // Usuario no encontrado en GitHub.
            elseif ($errorType === "NOT_FOUND") {
                throw new InvalidArgumentException(
                    "No se encontró ningún usuario con ese nombre en GitHub.",
                    404,
                );
            }

            // Si se excede la cuota de peticiones, rota el token.
            if (str_contains($message, "rate limit exceeded")) {
                removeGitHubToken($tokens[$year]);
            }

            error_log("Falló el primer intento para las contribuciones de {$user} en {$year}. {$message}");

            // Reintento único síncrono.
            $query = buildContributionGraphQuery($user, $year);
            $token = getGitHubToken();
            $request = getGraphQLCurlHandle($query, $token);
            $contents = curl_exec($request);
            $decoded = is_string($contents) ? json_decode($contents) : null;

            // Si vuelve a fallar, registra el error y omite el año.
            if (empty($decoded) || empty($decoded->data)) {
                $retryErrno = curl_errno($request);
                $retryError = curl_error($request);
                $message = $decoded->errors[0]->message
                    ?? ($decoded->message ?? "Error de API (cURL {$retryErrno}: {$retryError})");

                if (str_contains($message, "rate limit exceeded")) {
                    removeGitHubToken($token);
                }

                error_log("Fallaron 2 intentos para las contribuciones de {$user} en {$year}. {$message}");
                continue;
            }
        }

        $responses[$year] = $decoded;
    }

    // Limpia los manejadores de multi-cURL.
    foreach ($requests as $request) {
        curl_multi_remove_handle($multi, $request);
    }

    curl_multi_close($multi);

    return $responses;
}

/**
 * Obtiene todas las respuestas de contribución para un usuario.
 *
 * @param string $user Nombre de usuario de GitHub
 * @param int|null $startingYear Año mínimo personalizado para iniciar el conteo
 * @return array<int,stdClass> Lista de respuestas con los grafos de contribución
 */
function getContributionGraphs(string $user, ?int $startingYear = null): array
{
    // Valida la lista blanca si está configurada.
    if (!isWhitelisted($user)) {
        throw new InvalidArgumentException(
            "El usuario no está en la lista blanca autorizada.",
            403,
        );
    }

    // Obtiene el año actual y la fecha de registro del usuario.
    $currentYear = (int) date("Y");
    $responses = executeContributionGraphRequests($user, [$currentYear]);
    $userCreatedDateTimeString = $responses[$currentYear]->data->user->createdAt ?? null;

    if (empty($userCreatedDateTimeString)) {
        throw new AssertionError(
            "No se pudieron obtener las contribuciones. Posible problema de API en GitHub.",
            500,
        );
    }

    // Determina el año de registro en GitHub.
    $userCreatedYear = (int) explode("-", $userCreatedDateTimeString)[0];

    // Establece el año inicial (mínimo 2005, año de creación de Git).
    $minimumYear = $startingYear ?: $userCreatedYear;
    $minimumYear = max($minimumYear, 2005);
    $yearsToRequest = range($minimumYear, $currentYear - 1);

    // Revisa si existen contribuciones anteriores a 2005 por fechas manipuladas.
    $contributionYears = $responses[$currentYear]->data->user->contributionsCollection->contributionYears ?? [];
    $firstContributionYear = $contributionYears[count($contributionYears) - 1] ?? $userCreatedYear;

    if ($firstContributionYear < 2005) {
        array_unshift($yearsToRequest, $firstContributionYear);
    }

    // Consulta los años históricos restantes.
    $responses += executeContributionGraphRequests($user, $yearsToRequest);

    return $responses;
}

/**
 * Obtiene todos los tokens configurados en variables de entorno (TOKEN, TOKEN2, TOKEN3, etc.).
 *
 * @return array<string> Lista de tokens de GitHub
 */
function getGitHubTokens(): array
{
    // Retorna el pool almacenado en memoria si ya fue calculado.
    if (isset($GLOBALS["ALL_TOKENS"])) {
        return $GLOBALS["ALL_TOKENS"];
    }

    $tokens = [];
    $index = 1;

    while (true) {
        $name = $index === 1 ? "TOKEN" : "TOKEN{$index}";
        $token = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name) ?: null;

        if ($token === null || trim((string) $token) === "") {
            break;
        }

        $tokens[] = trim((string) $token);
        $index++;
    }

    $GLOBALS["ALL_TOKENS"] = $tokens;

    return $tokens;
}

/**
 * Selecciona un token aleatorio del pool disponible.
 *
 * @return string Token de GitHub
 *
 * @throws AssertionError Si no hay ningún token disponible
 */
function getGitHubToken(): string
{
    $allTokens = getGitHubTokens();

    if (empty($allTokens)) {
        throw new AssertionError(
            "No hay ningún token de GitHub disponible en la configuración.",
            500,
        );
    }

    return $allTokens[array_rand($allTokens)];
}

/**
 * Elimina un token agotado temporalmente por límite de peticiones.
 *
 * @param string $token Token a remover del pool
 * @return void
 */
function removeGitHubToken(string $token): void
{
    $index = array_search($token, $GLOBALS["ALL_TOKENS"] ?? [], true);

    if ($index !== false) {
        unset($GLOBALS["ALL_TOKENS"][$index]);
    }

    if (empty($GLOBALS["ALL_TOKENS"])) {
        throw new AssertionError(
            "Se ha excedido el límite de solicitudes de la API de GitHub. Revisa tus tokens.",
            429,
        );
    }
}

/**
 * Crea un manejador cURL para realizar una petición POST a la API GraphQL de GitHub.
 *
 * @param string $query Consulta GraphQL
 * @param string $token Token de GitHub para la autenticación
 * @return CurlHandle Manejador cURL configurado
 */
function getGraphQLCurlHandle(string $query, string $token): CurlHandle
{
    $headers = [
        "Authorization: bearer {$token}",
        "Content-Type: application/json",
        "Accept: application/vnd.github.v4.idl",
        "User-Agent: dev-readme-contributions",
    ];
    $body = ["query" => $query];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.github.com/graphql");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    // En Windows (PHP 8.2+), utiliza los certificados nativos del sistema operativo.
    if (defined("CURLSSLOPT_NATIVE_CA")) {
        curl_setopt($ch, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    }

    return $ch;
}

/**
 * Mapea las fechas (Y-m-d) con la cantidad de contribuciones correspondientes.
 *
 * @param array<int,stdClass> $contributionGraphs Lista de respuestas GraphQL por año
 * @param string $timezone Zona horaria del usuario o vacío para la del servidor
 * @param DateTimeImmutable|null $now Fecha y hora actual (útil para pruebas)
 * @return array<string,int> Array asociativo de fechas y número de contribuciones
 */
function getContributionDates(
    array $contributionGraphs,
    string $timezone = "",
    ?DateTimeImmutable $now = null,
): array {
    $contributions = [];
    $today = getCurrentDate($timezone, $now);
    $tomorrow = date("Y-m-d", strtotime("{$today} +1 day"));

    // Ordena los calendarios cronológicamente por año.
    ksort($contributionGraphs);

    foreach ($contributionGraphs as $graph) {
        $weeks = $graph->data->user->contributionsCollection->contributionCalendar->weeks ?? [];

        foreach ($weeks as $week) {
            foreach ($week->contributionDays as $day) {
                $date = $day->date;
                $count = $day->contributionCount;

                // Contabiliza contribuciones hasta hoy, o mañana si ya hay actividad registrada.
                if ($date <= $today || ($date === $tomorrow && $count > 0)) {
                    $contributions[$date] = $count;
                }
            }
        }
    }

    return $contributions;
}

/**
 * Devuelve la fecha actual en formato Y-m-d para una zona horaria dada.
 *
 * @param string $timezone Identificador de zona horaria (ej. Europe/Madrid)
 * @param DateTimeImmutable|null $now Sobrescritura de fecha/hora para pruebas
 * @return string Fecha actual en formato Y-m-d
 */
function getCurrentDate(string $timezone = "", ?DateTimeImmutable $now = null): string
{
    try {
        $dateTimezone = new DateTimeZone($timezone ?: date_default_timezone_get());
    } catch (Exception) {
        throw new InvalidArgumentException("Zona horaria no válida.", 400);
    }

    $now = $now ?: new DateTimeImmutable("now");

    return $now->setTimezone($dateTimezone)->format("Y-m-d");
}

/**
 * Normaliza los nombres de los días de la semana a formato abreviado (Sun, Mon, etc.).
 *
 * @param array<string> $days Lista de días de la semana recibidos
 * @return array<string> Lista normalizada de días válidos
 */
function normalizeDays(array $days): array
{
    return array_values(array_filter(
        array_map(
            function (string $dayOfWeek): ?string {
                $dayOfWeek = substr(ucfirst(strtolower(trim($dayOfWeek))), 0, 3);

                $validDays = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

                return in_array($dayOfWeek, $validDays, true) ? $dayOfWeek : null;
            },
            $days,
        ),
    ));
}

/**
 * Comprueba si una fecha corresponde a un día de la semana excluido del cálculo.
 *
 * @param string $date Fecha en formato Y-m-d
 * @param array<string> $excludedDays Días de la semana excluidos
 * @return bool True si el día está excluido, false si no
 */
function isExcludedDay(string $date, array $excludedDays): bool
{
    if (empty($excludedDays)) {
        return false;
    }

    $day = date("D", strtotime($date));

    return in_array($day, $excludedDays, true);
}

/**
 * Calcula las métricas de racha diaria, total y fechas de inicio/fin.
 *
 * @param array<string,int> $contributions Fechas con conteo de contribuciones
 * @param array<string> $excludedDays Días excluidos del cálculo
 * @return array<string,mixed> Estadísticas calculadas de la racha
 */
function getContributionStats(array $contributions, array $excludedDays = []): array
{
    if (empty($contributions)) {
        throw new AssertionError(
            "No se encontraron contribuciones para este usuario.",
            204,
        );
    }

    $today = array_key_last($contributions);
    $first = array_key_first($contributions);

    $stats = [
        "mode" => "daily",
        "totalContributions" => 0,
        "firstContribution" => "",
        "longestStreak" => [
            "start" => $first,
            "end" => $first,
            "length" => 0,
        ],
        "currentStreak" => [
            "start" => $first,
            "end" => $first,
            "length" => 0,
        ],
        "excludedDays" => $excludedDays,
    ];

    foreach ($contributions as $date => $count) {
        $stats["totalContributions"] += $count;

        // Comprueba si continúa la racha o si es un día excluido dentro de una racha activa.
        if ($count > 0 || ($stats["currentStreak"]["length"] > 0 && isExcludedDay($date, $excludedDays))) {
            ++$stats["currentStreak"]["length"];
            $stats["currentStreak"]["end"] = $date;

            if ($stats["currentStreak"]["length"] === 1) {
                $stats["currentStreak"]["start"] = $date;
            }

            if (!$stats["firstContribution"]) {
                $stats["firstContribution"] = $date;
            }

            // Actualiza la racha más larga si la actual la supera.
            if ($stats["currentStreak"]["length"] > $stats["longestStreak"]["length"]) {
                $stats["longestStreak"]["start"] = $stats["currentStreak"]["start"];
                $stats["longestStreak"]["end"] = $stats["currentStreak"]["end"];
                $stats["longestStreak"]["length"] = $stats["currentStreak"]["length"];
            }
        } elseif ($date !== $today) {
            // Reinicia la racha actual, excepto si hoy aún no ha terminado.
            $stats["currentStreak"]["length"] = 0;
            $stats["currentStreak"]["start"] = $today;
            $stats["currentStreak"]["end"] = $today;
        }
    }

    return $stats;
}

/**
 * Obtiene el domingo anterior a una fecha dada.
 *
 * @param string $date Fecha en formato Y-m-d
 * @return string Fecha del domingo anterior en formato Y-m-d
 */
function getPreviousSunday(string $date): string
{
    $dayOfWeek = date("w", strtotime($date));

    return date("Y-m-d", strtotime("-{$dayOfWeek} days", strtotime($date)));
}

/**
 * Calcula las estadísticas de contribuciones en modo semanal.
 *
 * @param array<string,int> $contributions Fechas con conteo de contribuciones
 * @return array<string,mixed> Estadísticas de racha semanal
 */
function getWeeklyContributionStats(array $contributions): array
{
    if (empty($contributions)) {
        throw new AssertionError(
            "No se encontraron contribuciones para este usuario.",
            204,
        );
    }

    $thisWeek = getPreviousSunday((string) array_key_last($contributions));
    $first = (string) array_key_first($contributions);
    $firstWeek = getPreviousSunday($first);

    $stats = [
        "mode" => "weekly",
        "totalContributions" => 0,
        "firstContribution" => "",
        "longestStreak" => [
            "start" => $firstWeek,
            "end" => $firstWeek,
            "length" => 0,
        ],
        "currentStreak" => [
            "start" => $firstWeek,
            "end" => $firstWeek,
            "length" => 0,
        ],
    ];

    // Agrupa contribuciones por semana.
    $weeks = [];

    foreach ($contributions as $date => $count) {
        $week = getPreviousSunday($date);

        if (!isset($weeks[$week])) {
            $weeks[$week] = 0;
        }

        if ($count > 0) {
            $weeks[$week] += $count;

            if (!$stats["firstContribution"]) {
                $stats["firstContribution"] = $date;
            }
        }
    }

    // Calcula la racha semana a semana.
    foreach ($weeks as $week => $count) {
        $stats["totalContributions"] += $count;

        if ($count > 0) {
            ++$stats["currentStreak"]["length"];
            $stats["currentStreak"]["end"] = $week;

            if ($stats["currentStreak"]["length"] === 1) {
                $stats["currentStreak"]["start"] = $week;
            }

            if ($stats["currentStreak"]["length"] > $stats["longestStreak"]["length"]) {
                $stats["longestStreak"]["start"] = $stats["currentStreak"]["start"];
                $stats["longestStreak"]["end"] = $stats["currentStreak"]["end"];
                $stats["longestStreak"]["length"] = $stats["currentStreak"]["length"];
            }
        } elseif ($week !== $thisWeek) {
            $stats["currentStreak"]["length"] = 0;
            $stats["currentStreak"]["start"] = $thisWeek;
            $stats["currentStreak"]["end"] = $thisWeek;
        }
    }

    return $stats;
}