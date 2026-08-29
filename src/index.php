<?php

declare(strict_types=1);

// Carga las dependencias y módulos de la aplicación.
require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/stats.php";
require_once __DIR__ . "/card.php";
require_once __DIR__ . "/cache.php";
require_once __DIR__ . "/generator.php";

// Carga el archivo .env durante el desarrollo local.
// En Vercel y Docker, las variables se reciben desde el entorno del sistema.
$projectRoot = dirname(__DIR__);
$dotenv = \Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->safeLoad();

// Obtiene TOKEN desde .env, Vercel o Docker.
// La prioridad permite compatibilidad entre entornos sin exponer el valor.
$token = $_ENV["TOKEN"] ?? $_SERVER["TOKEN"] ?? getenv("TOKEN") ?: null;

// Comprueba que existe un token antes de consultar la API de GitHub.
if (empty($token)) {
    $message = file_exists($projectRoot . "/.env")
        ? "Falta TOKEN en .env. Consulta CONTRIBUTING.md para configurarlo."
        : "No se encontró .env. Copia .env.example como .env y configura TOKEN.";

    renderOutput($message, 500);
    exit();
}

// Configura una caché pública de 24 horas.
$cacheSeconds = CACHE_DURATION;
header("Expires: " . gmdate("D, d M Y H:i:s", time() + $cacheSeconds) . " GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: public, max-age=$cacheSeconds");

// Redirige a la página de demostración si no se indicó un usuario.
if (!isset($_REQUEST["user"]) || trim((string) $_REQUEST["user"]) === "") {
    header("Location: demo/");
    exit();
}

try {
    $stats = generateStreakStats((string) $_REQUEST["user"], $_REQUEST);
    renderOutput($stats);
} catch (InvalidArgumentException | AssertionError $error) {
    error_log("Error {$error->getCode()}: {$error->getMessage()}");

    // Los errores de servidor incluyen trazas para facilitar el diagnóstico.
    if ($error->getCode() >= 500) {
        error_log($error->getTraceAsString());
    }

    renderOutput($error->getMessage(), $error->getCode());
}