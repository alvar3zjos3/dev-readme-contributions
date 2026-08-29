<?php

declare(strict_types=1);

/**
 * Caché simple basado en archivos para estadísticas de contribuciones de GitHub.
 *
 * Guarda las estadísticas durante 24 horas para evitar llamadas repetidas a la API.
 */

// Duración predeterminada de la caché: 24 horas, en segundos.
define("CACHE_DURATION", 24 * 60 * 60);
define("CACHE_DIR", __DIR__ . "/../cache");

/**
 * Genera una clave de caché para la solicitud de un usuario.
 *
 * Utiliza JSON estructurado para evitar colisiones de hash entre distintas
 * combinaciones de usuario y opciones que pudieran generar la misma cadena
 * concatenada.
 *
 * @param string $user Nombre de usuario de GitHub
 * @param array $options Opciones adicionales que afectan las estadísticas:
 *                       mode, exclude_days, starting_year y timezone
 * @return string Clave de caché segura para usar como nombre de archivo
 */
function getCacheKey(string $user, array $options = []): string
{
    ksort($options);

    try {
        $keyData = json_encode(
            ["user" => $user, "options" => $options],
            JSON_THROW_ON_ERROR,
        );
    } catch (JsonException $e) {
        // Usa concatenación simple si no se puede codificar JSON.
        error_log("No se pudo codificar JSON para la clave de caché: " . $e->getMessage());
        $keyData = $user . serialize($options);
    }

    return hash("sha256", $keyData);
}

/**
 * Obtiene la ruta del archivo de caché para una clave determinada.
 *
 * @param string $key Clave de caché
 * @return string Ruta completa del archivo de caché
 */
function getCacheFilePath(string $key): string
{
    return CACHE_DIR . "/" . $key . ".json";
}

/**
 * Garantiza que exista el directorio de caché.
 *
 * @return bool True si el directorio ya existe o se creó correctamente
 */
function ensureCacheDir(): bool
{
    if (!is_dir(CACHE_DIR)) {
        return mkdir(CACHE_DIR, 0755, true);
    }

    return true;
}

/**
 * Obtiene estadísticas desde la caché si están disponibles y no han expirado.
 *
 * @param string $user Nombre de usuario de GitHub
 * @param array $options Opciones adicionales
 * @param int $maxAge Antigüedad máxima en segundos; 24 horas por defecto
 * @return array|null Estadísticas almacenadas o null si no existen o expiraron
 */
function getCachedStats(
    string $user,
    array $options = [],
    int $maxAge = CACHE_DURATION,
): ?array {
    $key = getCacheKey($user, $options);
    $filePath = getCacheFilePath($key);

    if (!file_exists($filePath)) {
        return null;
    }

    $mtime = filemtime($filePath);
    if ($mtime === false) {
        return null;
    }

    $fileAge = time() - $mtime;
    if ($fileAge > $maxAge) {
        unlink($filePath);

        return null;
    }

    $handle = fopen($filePath, "r");
    if ($handle === false) {
        return null;
    }

    if (!flock($handle, LOCK_SH)) {
        fclose($handle);

        return null;
    }

    $contents = stream_get_contents($handle);

    flock($handle, LOCK_UN);
    fclose($handle);

    if ($contents === false || $contents === "") {
        return null;
    }

    $data = json_decode($contents, true);

    if (!is_array($data)) {
        return null;
    }

    return $data;
}

/**
 * Guarda estadísticas en la caché.
 *
 * @param string $user Nombre de usuario de GitHub
 * @param array $options Opciones adicionales
 * @param array $stats Estadísticas que se almacenarán en caché
 * @return bool True si se almacenaron correctamente
 */
function setCachedStats(string $user, array $options, array $stats): bool
{
    if (!ensureCacheDir()) {
        error_log("No se pudo crear el directorio de caché: " . CACHE_DIR);

        return false;
    }

    $key = getCacheKey($user, $options);
    $filePath = getCacheFilePath($key);

    $data = json_encode($stats);

    if ($data === false) {
        error_log("No se pudieron codificar las estadísticas como JSON para el usuario: " . $user);

        return false;
    }

    $result = file_put_contents($filePath, $data, LOCK_EX);

    if ($result === false) {
        error_log("No se pudo escribir el archivo de caché: " . $filePath);

        return false;
    }

    return true;
}

/**
 * Elimina todos los archivos de caché expirados.
 *
 * @param int $maxAge Antigüedad máxima permitida en segundos
 * @return int Cantidad de archivos eliminados
 */
function clearExpiredCache(int $maxAge = CACHE_DURATION): int
{
    if (!is_dir(CACHE_DIR)) {
        return 0;
    }

    $deleted = 0;
    $files = glob(CACHE_DIR . "/*.json");

    if ($files === false) {
        return 0;
    }

    foreach ($files as $file) {
        $mtime = filemtime($file);

        if ($mtime === false) {
            continue;
        }

        $fileAge = time() - $mtime;

        if ($fileAge > $maxAge) {
            if (unlink($file)) {
                $deleted++;
            }
        }
    }

    return $deleted;
}

/**
 * Elimina la caché para un usuario específico.
 *
 * Nota: esta función solo elimina la entrada de caché del usuario usando
 * opciones vacías o predeterminadas. Las entradas con opciones no vacías,
 * como starting_year, mode, exclude_days o timezone, no se eliminarán.
 *
 * Esta limitación se debe al uso de claves hash: no es posible enumerar todas
 * las combinaciones potenciales de opciones sin guardar metadatos adicionales.
 *
 * @param string $user Nombre de usuario de GitHub
 * @return bool True si se eliminó la caché o si no existía
 */
function clearUserCache(string $user): bool
{
    if (!is_dir(CACHE_DIR)) {
        return true;
    }

    $key = getCacheKey($user, []);
    $filePath = getCacheFilePath($key);

    if (file_exists($filePath)) {
        return unlink($filePath);
    }

    return true;
}