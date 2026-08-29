<?php

declare(strict_types=1);

/**
 * Comprueba si un usuario de GitHub está autorizado por la lista blanca.
 *
 * Si `WHITELIST` está vacía o no está configurada, cualquier usuario puede
 * consultar la tarjeta. Si contiene usuarios separados por comas, solo esos
 * usuarios podrán solicitar estadísticas.
 *
 * @param string $user Nombre de usuario de GitHub que se quiere comprobar
 * @return bool True si el usuario está autorizado o si no hay lista blanca; false en caso contrario
 */
function isWhitelisted(string $user): bool
{
    // Prioridad: .env local, variables de entorno del servidor y, por último, getenv().
    $whitelistRaw = $_ENV["WHITELIST"] ?? ($_SERVER["WHITELIST"] ?? null);

    if ($whitelistRaw === null) {
        $whitelistRaw = getenv("WHITELIST");
        $whitelistRaw = $whitelistRaw === false ? "" : $whitelistRaw;
    }

    // Divide los nombres por comas, elimina espacios y descarta elementos vacíos.
    $whitelist = array_map("trim", array_filter(explode(",", (string) $whitelistRaw)));

    // Sin lista blanca se permiten todos los usuarios; si existe, la coincidencia debe ser exacta.
    return empty($whitelist) || in_array($user, $whitelist, true);
}