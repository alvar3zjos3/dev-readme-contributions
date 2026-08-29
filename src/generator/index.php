<?php

declare(strict_types=1);

$THEMES = include "../themes.php";
$TRANSLATIONS = include "../translations.php";

/**
 * Configuración pública de Dev Readme Contributions.
 */
$GITHUB_OWNER = "alvar3zjos3";
$GITHUB_REPOSITORY = "dev-readme-contributions";
$PROJECT_NAME = "Dev Readme Contributions";

$GITHUB_REPOSITORY_URL = "https://github.com/{$GITHUB_OWNER}/{$GITHUB_REPOSITORY}";
$DOCUMENTATION_URL = "{$GITHUB_REPOSITORY_URL}#readme";

/**
 * Lista de idiomas que contienen traducciones propias.
 *
 * Se excluyen los alias de idiomas; por ejemplo, "zh" => "zh_Hans".
 *
 * @var array<int,string> $LOCALES
 */
$LOCALES = array_values(array_filter(
    array_keys($TRANSLATIONS),
    fn(string $locale): bool => is_array($TRANSLATIONS[$locale]),
));

$darkmode = $_COOKIE["darkmode"] ?? null;

/**
 * Convierte una cadena camelCase a kebab-case para atributos data-*.
 *
 * @param string $value Cadena en camelCase
 * @return string Cadena en kebab-case
 */
function camelToKebabCase(string $value): string
{
    return preg_replace_callback(
        "/[A-Z]/",
        fn(array $matches): string => "-" . strtolower($matches[0]),
        $value,
    );
}

/**
 * Escapa contenido para imprimirlo de forma segura en HTML.
 *
 * @param string $value Valor sin escapar
 * @return string Valor seguro para HTML
 */
function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

/**
 * Genera atributos HTML data-* con la configuración de un tema.
 *
 * Estos valores son leídos por script.js al exportar un tema personalizado.
 *
 * @param array<string,string> $theme Configuración de colores del tema
 * @return string Atributos data-* seguros para una etiqueta option
 */
function getThemeDataAttributes(array $theme): string
{
    $attributes = [];

    foreach ($theme as $key => $value) {
        $attributeName = camelToKebabCase($key);
        $attributeValue = preg_replace("/^#/", "", $value);

        $attributes[] = sprintf(
            'data-%s="%s"',
            escapeHtml($attributeName),
            escapeHtml($attributeValue),
        );
    }

    return implode(" ", $attributes);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta
        name="description"
        content="Genera tarjetas dinámicas con contribuciones, rachas y estadísticas de usuarios de GitHub."
    >
    <meta name="theme-color" content="#fb8c00">

    <meta property="og:type" content="website">
    <meta property="og:title" content="Dev Readme Contributions">
    <meta
        property="og:description"
        content="Genera tarjetas dinámicas con contribuciones, rachas y estadísticas de usuarios de GitHub."
    >
    <meta property="og:url" content="<?= escapeHtml($GITHUB_REPOSITORY_URL) ?>">

    <title><?= escapeHtml($PROJECT_NAME) ?> — Generador de estadísticas de GitHub</title>

    <link rel="stylesheet" href="/generator/css/style.css?v=<?= filemtime(__DIR__ . "/css/style.css") ?>">
    <link rel="stylesheet" href="/generator/css/toggle-dark.css?v=<?= filemtime(__DIR__ . "/css/toggle-dark.css") ?>">

    <!-- Iconos del sitio -->
    <link rel="apple-touch-icon" sizes="180x180" href="/generator/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/generator/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/generator/favicon-32x32.png">
    <link rel="mask-icon" href="/generator/icon.svg" color="#fb8c00">

    <!-- Configuración del proyecto disponible para js/script.js -->
    <script>
        window.DEV_README_CONTRIBUTIONS_CONFIG = <?= json_encode(
            [
                "repositoryUrl" => $GITHUB_REPOSITORY_URL,
                "cardAlt" => "Estadísticas de contribuciones de GitHub",
                "markdownLabel" => "Estadísticas de contribuciones de GitHub",
            ],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        ) ?>;
    </script>

    <script src="/generator/js/jscolor.min.js?v=<?= filemtime(__DIR__ . "/js/jscolor.min.js") ?>" defer></script>
    <script src="/generator/js/script.js?v=<?= filemtime(__DIR__ . "/js/script.js") ?>" defer></script>
    <script src="/generator/js/accordion.js?v=<?= filemtime(__DIR__ . "/js/accordion.js") ?>" defer></script>
    <script src="/generator/js/toggle-dark.js?v=<?= filemtime(__DIR__ . "/js/toggle-dark.js") ?>" defer></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
</head>

<body <?= $darkmode === "on" ? 'data-theme="dark"' : "" ?>>
    <header class="site-header">
        <h1>🔥 <?= escapeHtml($PROJECT_NAME) ?></h1>
        <p>Genera tarjetas dinámicas con tus contribuciones y rachas de GitHub.</p>

        <nav class="github" aria-label="Enlaces del proyecto">
            <a
                class="github-button"
                href="<?= escapeHtml($GITHUB_REPOSITORY_URL) ?>"
                data-color-scheme="no-preference: light; light: light; dark: dark;"
                data-size="large"
                aria-label="Ver <?= escapeHtml($GITHUB_OWNER . "/" . $GITHUB_REPOSITORY) ?> en GitHub"
            >
                Ver en GitHub
            </a>

            <a
                class="github-button"
                href="<?= escapeHtml($GITHUB_REPOSITORY_URL) ?>"
                data-color-scheme="no-preference: light; light: light; dark: dark;"
                data-icon="octicon-star"
                data-size="large"
                data-show-count="true"
                aria-label="Dar una estrella a <?= escapeHtml($GITHUB_OWNER . "/" . $GITHUB_REPOSITORY) ?> en GitHub"
            >
                Dar estrella
            </a>
        </nav>
    </header>

    <main class="container">
        <section class="properties" aria-labelledby="settings-title">
            <h2 id="settings-title">Configuración de la tarjeta</h2>

            <!-- La clase .parameters debe permanecer en este FORM. -->
            <form id="card-generator-form" class="parameters">
                <label for="user">
                    Usuario de GitHub
                    <span title="Campo obligatorio" aria-label="Campo obligatorio">*</span>
                </label>

                <input
                    class="param"
                    type="text"
                    id="user"
                    name="user"
                    value="<?= escapeHtml($GITHUB_OWNER) ?>"
                    placeholder="usuario-github"
                    pattern="^[A-Za-z\d](?:[A-Za-z\d-]{0,37}[A-Za-z\d])?$"
                    title="Entre 1 y 39 caracteres: letras, números o guiones; no puede terminar en guion"
                    autocomplete="off"
                    spellcheck="false"
                    required
                >

                <label for="theme">Tema</label>

                <select class="param" id="theme" name="theme">
                    <?php foreach ($THEMES as $themeName => $themeOptions): ?>
                        <option
                            value="<?= escapeHtml($themeName) ?>"
                            <?= getThemeDataAttributes($themeOptions) ?>
                        >
                            <?= escapeHtml($themeName) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="hide-border">Borde</label>

                <select class="param" id="hide-border" name="hide_border">
                    <option value="false">Mostrar</option>
                    <option value="true">Ocultar</option>
                </select>

                <label for="border-radius">Redondeo de las esquinas</label>

                <input
                    class="param"
                    type="number"
                    id="border-radius"
                    name="border_radius"
                    value="4.5"
                    min="0"
                    max="100"
                    step="0.1"
                    inputmode="decimal"
                >

                <label for="locale">Idioma</label>

                <select class="param" id="locale" name="locale">
                    <?php foreach ($LOCALES as $locale): ?>
                        <?php
                        $displayName = Locale::getDisplayName($locale, "es") ?: $locale;
                        ?>
                        <option
                            value="<?= escapeHtml($locale) ?>"
                            <?= $locale === "es" ? "selected" : "" ?>
                        >
                            <?= escapeHtml(ucfirst($displayName) . " ({$locale})") ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="timezone">Zona horaria</label>

                <!--
                    Se deja value vacío: el backend usará Europe/Madrid por defecto.
                    De ese modo timezone no aparece en la URL a menos que el usuario
                    indique explícitamente otra zona.
                -->
                <input
                    class="param"
                    type="text"
                    id="timezone"
                    name="timezone"
                    value=""
                    placeholder="Europe/Madrid"
                    autocomplete="off"
                    spellcheck="false"
                    title="Opcional. Si se deja vacío se usa Europe/Madrid. Usa identificadores IANA como America/Bogota o America/Mexico_City."
                >

                <label for="short-numbers">Formato de números</label>

                <select class="param" id="short-numbers" name="short_numbers">
                    <option value="false">Completo</option>
                    <option value="true">Abreviado</option>
                </select>

                <label for="date-format">Formato de fecha</label>

                <select class="param" id="date-format" name="date_format">
                    <option value="">Predeterminado del idioma</option>
                    <option value="j/n[/Y]">10/8/2016</option>
                    <option value="n/j[/Y]">8/10/2016</option>
                    <option value="j M[ Y]">10 ago 2016</option>
                    <option value="M j[, Y]">ago 10, 2016</option>
                    <option value="[Y ]M j">2016 ago 10</option>
                    <option value="[Y.]n.j">2016.8.10</option>
                </select>

                <label for="mode">Cálculo de rachas</label>

                <select class="param" id="mode" name="mode">
                    <option value="daily">Diario</option>
                    <option value="weekly">Semanal</option>
                </select>

                <span id="exclude-days-label">Días excluidos</span>

                <div
                    class="checkbox-buttons weekdays"
                    role="group"
                    aria-labelledby="exclude-days-label"
                >
                    <input type="checkbox" value="Sun" id="weekday-sun">
                    <label for="weekday-sun" data-tooltip="Excluir domingo" title="Excluir domingo">D</label>

                    <input type="checkbox" value="Mon" id="weekday-mon">
                    <label for="weekday-mon" data-tooltip="Excluir lunes" title="Excluir lunes">L</label>

                    <input type="checkbox" value="Tue" id="weekday-tue">
                    <label for="weekday-tue" data-tooltip="Excluir martes" title="Excluir martes">M</label>

                    <input type="checkbox" value="Wed" id="weekday-wed">
                    <label for="weekday-wed" data-tooltip="Excluir miércoles" title="Excluir miércoles">X</label>

                    <input type="checkbox" value="Thu" id="weekday-thu">
                    <label for="weekday-thu" data-tooltip="Excluir jueves" title="Excluir jueves">J</label>

                    <input type="checkbox" value="Fri" id="weekday-fri">
                    <label for="weekday-fri" data-tooltip="Excluir viernes" title="Excluir viernes">V</label>

                    <input type="checkbox" value="Sat" id="weekday-sat">
                    <label for="weekday-sat" data-tooltip="Excluir sábado" title="Excluir sábado">S</label>

                    <input
                        type="hidden"
                        id="exclude-days"
                        name="exclude_days"
                        class="param"
                        value=""
                    >
                </div>

                <span id="show-sections-label">Estadísticas visibles</span>

                <div
                    class="checkbox-buttons sections"
                    role="group"
                    aria-labelledby="show-sections-label"
                >
                    <input type="checkbox" value="total" id="section-total" checked>
                    <label
                        for="section-total"
                        data-tooltip="Contribuciones totales"
                        title="Contribuciones totales"
                    >
                        Total
                    </label>

                    <input type="checkbox" value="current" id="section-current" checked>
                    <label
                        for="section-current"
                        data-tooltip="Racha actual"
                        title="Racha actual"
                    >
                        Actual
                    </label>

                    <input type="checkbox" value="longest" id="section-longest" checked>
                    <label
                        for="section-longest"
                        data-tooltip="Racha más larga"
                        title="Racha más larga"
                    >
                        Máxima
                    </label>

                    <input
                        type="hidden"
                        id="sections"
                        name="sections"
                        class="param"
                        value="total,current,longest"
                    >
                </div>

                <label for="card-width">Ancho de la tarjeta</label>

                <input
                    class="param"
                    type="number"
                    id="card-width"
                    name="card_width"
                    value="495"
                    min="300"
                    step="1"
                    inputmode="numeric"
                >

                <label for="card-height">Alto de la tarjeta</label>

                <input
                    class="param"
                    type="number"
                    id="card-height"
                    name="card_height"
                    value="195"
                    min="170"
                    step="1"
                    inputmode="numeric"
                >

                <label for="type">Formato de salida</label>

                <select class="param" id="type" name="type">
                    <option value="svg">SVG</option>
                    <option value="png">PNG</option>
                    <option value="json">JSON</option>
                </select>

                <details class="advanced">
                    <summary>⚙ Personalización avanzada</summary>

                    <div class="content">
                        <!-- No se usa la clase .parameters aquí para no seleccionar este contenedor como formulario. -->
                        <div
                            class="radio-buttons advanced-controls"
                            role="radiogroup"
                            aria-labelledby="background-type-label"
                        >
                            <span id="background-type-label">Tipo de fondo</span>

                            <div class="radio-button-group">
                                <div>
                                    <input
                                        type="radio"
                                        id="background-type-solid"
                                        name="background-type"
                                        value="solid"
                                        checked
                                    >
                                    <label for="background-type-solid">Sólido</label>
                                </div>

                                <div>
                                    <input
                                        type="radio"
                                        id="background-type-gradient"
                                        name="background-type"
                                        value="gradient"
                                    >
                                    <label for="background-type-gradient">Gradiente</label>
                                </div>
                            </div>
                        </div>

                        <!-- script.js requiere .color-properties; no necesita .parameters en este contenedor. -->
                        <div class="color-properties">
                            <label for="properties">Propiedad de color</label>

                            <select id="properties" name="properties">
                                <?php foreach ($THEMES["default"] as $propertyName => $color): ?>
                                    <option value="<?= escapeHtml($propertyName) ?>">
                                        <?= escapeHtml($propertyName) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button
                                class="plus btn"
                                type="button"
                                onclick="preview.addProperty()"
                                aria-label="Añadir propiedad de color"
                                title="Añadir propiedad de color"
                            >
                                +
                            </button>
                        </div>
                    </div>

                    <button
                        class="btn"
                        type="button"
                        onclick="preview.exportPhp()"
                    >
                        Exportar tema a PHP
                    </button>

                    <button
                        id="clear-button"
                        class="btn"
                        type="button"
                        onclick="preview.removeAllProperties()"
                        disabled
                    >
                        Restablecer personalización
                    </button>

                    <textarea
                        id="exported-php"
                        hidden
                        readonly
                        aria-label="Tema exportado como código PHP"
                    ></textarea>
                </details>

                <button class="btn" type="submit">Abrir enlace de la tarjeta</button>
            </form>
        </section>

        <section class="output top-bottom-split" aria-labelledby="preview-title">
            <div class="top">
                <h2 id="preview-title">Vista previa</h2>

                <!--
                    El primer SVG visible coincide con el usuario por defecto.
                    script.js reemplaza src en cuanto termina de cargar la página.
                -->
                <img
                    id="preview-image"
                    alt="Estadísticas de contribuciones de GitHub"
                    src="/generator/preview.php?user=<?= rawurlencode($GITHUB_OWNER) ?>"
                    width="495"
                    height="195"
                >

                <!-- Contenedor de respuesta cuando el tipo es JSON. -->
                <div class="json" style="display: none;">
                    <pre aria-live="polite"></pre>
                </div>

                <div class="markdown-output">
                    <h2>Markdown</h2>

                    <div class="code-container md">
                        <code></code>
                    </div>

                    <button
                        class="copy-button btn tooltip copy-md"
                        type="button"
                        onclick="clipboard.copy(this)"
                        onmouseout="tooltip.reset(this)"
                        disabled
                    >
                        Copiar Markdown
                    </button>
                </div>

                <div class="html-output">
                    <h2>HTML</h2>

                    <div class="code-container html">
                        <code></code>
                    </div>

                    <button
                        class="copy-button btn tooltip copy-html"
                        type="button"
                        onclick="clipboard.copy(this)"
                        onmouseout="tooltip.reset(this)"
                        disabled
                    >
                        Copiar HTML
                    </button>
                </div>

                <div class="json-output">
                    <h2>JSON</h2>

                    <div class="code-container json">
                        <code></code>
                    </div>

                    <button
                        class="copy-button btn tooltip copy-json"
                        type="button"
                        onclick="clipboard.copy(this)"
                        onmouseout="tooltip.reset(this)"
                        disabled
                    >
                        Copiar enlace JSON
                    </button>
                </div>
            </div>

            <div class="bottom">
                <a
                    href="<?= escapeHtml($DOCUMENTATION_URL) ?>"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="underline-hover faq"
                >
                    Documentación
                    <svg
                        stroke="currentColor"
                        fill="currentColor"
                        stroke-width="0"
                        viewBox="0 0 24 24"
                        height="1em"
                        width="1em"
                        aria-hidden="true"
                        focusable="false"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path fill="none" d="M0 0h24v24H0z"></path>
                        <path d="M10 6v2H5v11h11v-5h2v6a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h6zm11-3v9l-3.794-3.793-5.999 6-1.414-1.414 5.999-6L12 3h9z"></path>
                    </svg>
                </a>
            </div>
        </section>
    </main>

    <button
        type="button"
        class="darkmode"
        onclick="toggleTheme()"
        title="<?= $darkmode === "on" ? "Cambiar al modo claro" : "Cambiar al modo oscuro" ?>"
        aria-label="<?= $darkmode === "on" ? "Cambiar al modo claro" : "Cambiar al modo oscuro" ?>"
    >
        <span id="darkmode-icon" aria-hidden="true"><?= $darkmode === "on" ? "🌞" : "🌙" ?></span>
    </button>
</body>

</html>