<?php

declare(strict_types=1);

/**
 * Convierte una fecha de Y-M-D a un formato más legible.
 *
 * @param string $dateString Cadena con formato Y-M-D
 * @param string|null $format Formato de fecha que se utilizará, o null para usar el formato predeterminado del idioma
 * @param string $locale Código de idioma
 * @return string Cadena de fecha con formato
 */
function formatDate(string $dateString, string|null $format, string $locale): string
{
    $date = new DateTime($dateString);
    $formatted = "";
    $patternGenerator = new IntlDatePatternGenerator($locale);

    // Si corresponde al año actual, muestra únicamente el mes y el día.
    if (date_format($date, "Y") == date("Y")) {
        if ($format) {
            // Elimina los corchetes y todo el contenido que se encuentre dentro de ellos.
            $formatted = date_format($date, preg_replace("/\[.*?\]/", "", $format));
        } else {
            // Aplica el formato regional sin año.
            $pattern = $patternGenerator->getBestPattern("MMM d");
            $dateFormatter = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::NONE,
                pattern: $pattern,
            );
            $formatted = $dateFormatter->format($date);
        }
    }
    // En otros casos, muestra mes, día y año.
    else {
        if ($format) {
            // Elimina los corchetes, pero conserva el contenido dentro de ellos.
            $formatted = date_format($date, str_replace(["[", "]"], "", $format));
        } else {
            // Aplica el formato regional con año.
            $pattern = $patternGenerator->getBestPattern("yyyy MMM d");
            $dateFormatter = new IntlDateFormatter(
                $locale,
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::NONE,
                pattern: $pattern,
            );
            $formatted = $dateFormatter->format($date);
        }
    }

    // Sanitiza y devuelve la fecha formateada.
    return htmlspecialchars($formatted);
}

/**
 * Traduce los días de la semana.
 *
 * Recibe una lista de días, por ejemplo ["Sun", "Mon", "Sat"], y devuelve las
 * abreviaturas correspondientes para otro idioma.
 *
 * Ejemplo: ["Sun", "Mon", "Sat"] -> ["dim", "lun", "sáb"]
 *
 * @param array<string> $days Lista de días que se deben traducir
 * @param string $locale Código de idioma
 * @return array<string> Días traducidos
 */
function translateDays(array $days, string $locale): array
{
    if ($locale === "es") {
        return $days;
    }

    $patternGenerator = new IntlDatePatternGenerator($locale);
    $pattern = $patternGenerator->getBestPattern("EEE");
    $dateFormatter = new IntlDateFormatter(
        $locale,
        IntlDateFormatter::NONE,
        IntlDateFormatter::NONE,
        pattern: $pattern,
    );

    $translatedDays = [];
    foreach ($days as $day) {
        $translatedDays[] = $dateFormatter->format(new DateTime($day));
    }

    return $translatedDays;
}

/**
 * Obtiene el texto de los días excluidos.
 *
 * @param array<string> $excludedDays Lista de días excluidos
 * @param array<string,string> $localeTranslations Traducciones del idioma
 * @param string $localeCode Código de idioma
 * @return string Texto de los días excluidos
 */
function getExcludingDaysText($excludedDays, $localeTranslations, $localeCode)
{
    $separator = $localeTranslations["comma_separator"] ?? ", ";
    $daysCommaSeparated = implode($separator, translateDays($excludedDays, $localeCode));

    return str_replace(
        "{days}",
        $daysCommaSeparated,
        $localeTranslations["Excluding {days}"],
    );
}

/**
 * Normaliza el nombre de un tema.
 *
 * @param string $theme Nombre del tema
 * @return string Nombre normalizado del tema
 */
function normalizeThemeName(string $theme): string
{
    return strtolower(str_replace("_", "-", $theme));
}

/**
 * Procesa los parámetros de tema y colores para generar la configuración visual.
 *
 * @param array<string,string> $params Parámetros de la solicitud
 * @return array<string,string> Tema solicitado o tema predeterminado
 */
function getRequestedTheme(array $params): array
{
    /**
     * @var array<string,array<string,string>> $THEMES
     * Lista de temas con sus colores asociados.
     */
    $THEMES = include "themes.php";

    /**
     * @var array<string> $CSS_COLORS
     * Lista de colores CSS válidos.
     */
    $CSS_COLORS = include "colors.php";

    // Normaliza el nombre del tema solicitado.
    $selectedTheme = normalizeThemeName($params["theme"] ?? "default");

    // Obtiene los colores del tema o los colores predeterminados si no existe.
    $theme = $THEMES[$selectedTheme] ?? $THEMES["default"];

    // Personalizaciones del tema.
    $properties = array_keys($theme);
    foreach ($properties as $prop) {
        // Comprueba si cada propiedad se recibió como parámetro.
        if (isset($params[$prop])) {
            // Ignora mayúsculas y minúsculas.
            $param = strtolower($params[$prop]);

            // Comprueba si el color hexadecimal tiene 3, 4, 6 u 8 dígitos.
            if (preg_match("/^([a-f0-9]{3}|[a-f0-9]{4}|[a-f0-9]{6}|[a-f0-9]{8})$/", $param)) {
                $theme[$prop] = "#" . $param;
            }
            // Comprueba si el color CSS es válido.
            elseif (in_array($param, $CSS_COLORS)) {
                $theme[$prop] = $param;
            }
            // Permite gradientes de fondo: ángulo,color_inicial,...,color_final.
            elseif (
                $prop == "background"
                && preg_match("/^-?[0-9]+,[a-f0-9]{3,8}(,[a-f0-9]{3,8})+$/", $param)
            ) {
                $theme[$prop] = $param;
            }
        }
    }

    // Oculta los bordes si se solicita.
    if (isset($params["hide_border"]) && $params["hide_border"] == "true") {
        $theme["border"] = "#0000";
    }

    // Configura el fondo y los gradientes.
    $gradient = "";
    $backgroundParts = explode(",", $theme["background"] ?? "");

    if (count($backgroundParts) >= 3) {
        $theme["background"] = "url(#gradient)";
        $gradient = "<linearGradient id='gradient' gradientTransform='rotate({$backgroundParts[0]})' gradientUnits='userSpaceOnUse'>";
        $backgroundColors = array_slice($backgroundParts, 1);
        $colorCount = count($backgroundColors);

        for ($index = 0; $index < $colorCount; $index++) {
            $offset = ($index * 100) / ($colorCount - 1);
            $gradient .= "<stop offset='{$offset}%' stop-color='#{$backgroundColors[$index]}' />";
        }

        $gradient .= "</linearGradient>";
    }

    $theme["backgroundGradient"] = $gradient;

    return $theme;
}

/**
 * Ajusta una cadena a un número máximo de caracteres.
 *
 * Es similar a `wordwrap()`, pero utiliza expresiones regulares y no falla con
 * determinados caracteres que no pertenecen a ASCII.
 *
 * @param string $string Cadena de entrada
 * @param int $width Cantidad de caracteres a partir de la cual se divide el texto
 * @param string $break Separador utilizado para dividir las líneas
 * @param bool $cut_long_words Si es true, divide las palabras largas al ancho especificado
 * @return string Cadena ajustada a la longitud indicada
 */
function utf8WordWrap(
    string $string,
    int $width = 75,
    string $break = "\n",
    bool $cut_long_words = false,
): string {
    // Busca hasta $width caracteres seguidos por espacio en blanco o fin de cadena.
    $string = preg_replace("/(.{1,$width})(?:\s|$)/uS", "$1$break", $string);

    // Divide palabras demasiado largas si se ha solicitado.
    if ($cut_long_words) {
        $string = preg_replace("/(\S{" . $width . "})(?=\S)/u", "$1$break", $string);
    }

    // Elimina los saltos de línea finales.
    return rtrim($string, $break);
}

/**
 * Obtiene la longitud de una cadena con caracteres UTF-8.
 *
 * Es similar a `strlen()`, pero utiliza expresiones regulares y evita
 * problemas con determinados caracteres que no pertenecen a ASCII.
 *
 * @param string $string Cadena de entrada
 * @return int Longitud de la cadena
 */
function utf8Strlen(string $string): int
{
    return preg_match_all("/./us", $string, $matches);
}

/**
 * Divide texto en líneas mediante elementos <tspan>.
 *
 * Divide el texto cuando contiene un salto de línea o supera el número máximo
 * de caracteres permitido por línea.
 *
 * @param string $text Texto que se debe dividir
 * @param int $maxChars Máximo de caracteres por línea
 * @param int $line1Offset Desplazamiento vertical de la primera línea
 * @return string Texto original en una línea o texto dividido con elementos <tspan>
 */
function splitLines(string $text, int $maxChars, int $line1Offset): string
{
    // Si el texto supera el máximo, inserta \n antes de un espacio o guion cuando sea posible.
    if ($maxChars > 0 && utf8Strlen($text) > $maxChars && strpos($text, "\n") === false) {
        // Da prioridad a dividir en " - " si existe.
        if (strpos($text, " - ") !== false) {
            $text = str_replace(" - ", "\n- ", $text);
        }
        // En caso contrario, divide por espacios.
        else {
            $text = utf8WordWrap($text, $maxChars, "\n", true);
        }
    }

    $text = htmlspecialchars($text);

    return preg_replace(
        "/^(.*)\n(.*)/",
        "<tspan x='0' dy='{$line1Offset}'>$1</tspan><tspan x='0' dy='16'>$2</tspan>",
        $text,
    );
}

/**
 * Normaliza un código de idioma.
 *
 * @param string $localeCode Código de idioma
 * @return string Código de idioma normalizado
 */
function normalizeLocaleCode(string $localeCode): string
{
    preg_match(
        "/^([a-z]{2,3})(?:[_-]([a-z]{4}))?(?:[_-]([0-9]{3}|[a-z]{2}))?$/i",
        $localeCode,
        $matches,
    );

    if (empty($matches)) {
        return "es";
    }

    $language = $matches[1];
    $script = $matches[2] ?? "";
    $region = $matches[3] ?? "";

    // Convierte el idioma a minúsculas.
    $language = strtolower($language);

    // Convierte la escritura a formato de título.
    $script = ucfirst(strtolower($script));

    // Convierte la región a mayúsculas.
    $region = strtoupper($region);

    // Combina idioma, escritura y región mediante guiones bajos.
    return implode("_", array_filter([$language, $script, $region]));
}

/**
 * Obtiene las traducciones de un idioma después de normalizar su código.
 *
 * @param string $localeCode Código de idioma
 * @return array Traducciones correspondientes al código de idioma
 */
function getTranslations(string $localeCode): array
{
    // Normaliza el código de idioma.
    $localeCode = normalizeLocaleCode($localeCode);

    // Obtiene las etiquetas desde el archivo de traducciones.
    $translations = include "translations.php";

    // Si no existe el idioma completo, prueba únicamente con el idioma base.
    if (!isset($translations[$localeCode])) {
        $localeCode = explode("_", $localeCode)[0];
    }

    // Obtiene las traducciones o un array vacío si el idioma no existe.
    $localeTranslations = $translations[$localeCode] ?? [];

    // Si el resultado es una cadena, se trata de un alias hacia otro idioma.
    if (is_string($localeTranslations)) {
        $localeTranslations = $translations[$localeTranslations];
    }

    // Completa traducciones faltantes con español.
    $localeTranslations += $translations["es"];

    return $localeTranslations;
}

/**
 * Obtiene el ancho de la tarjeta según los parámetros, el mínimo y el valor predeterminado.
 *
 * @param array<string,string> $params Parámetros de la solicitud
 * @param int $numColumns Número de columnas de la tarjeta
 * @return int Ancho de la tarjeta
 */
function getCardWidth(array $params, int $numColumns = 3): int
{
    $defaultWidth = 495;
    $minimumWidth = 100 * $numColumns;

    return max($minimumWidth, intval($params["card_width"] ?? $defaultWidth));
}

/**
 * Obtiene el alto de la tarjeta según los parámetros, el mínimo y el valor predeterminado.
 *
 * @param array<string,string> $params Parámetros de la solicitud
 * @return int Alto de la tarjeta
 */
function getCardHeight(array $params): int
{
    $defaultHeight = 195;
    $minimumHeight = 170;

    return max($minimumHeight, intval($params["card_height"] ?? $defaultHeight));
}

/**
 * Formatea un número según el idioma y la abreviación solicitada.
 *
 * @param float $num Número que se debe formatear
 * @param string $localeCode Código de idioma
 * @param bool $useShortNumbers Indica si deben utilizarse números abreviados
 * @return string Número formateado
 */
function formatNumber(float $num, string $localeCode, bool $useShortNumbers): string
{
    $numFormatter = new NumberFormatter($localeCode, NumberFormatter::DECIMAL);
    $suffix = "";

    if ($useShortNumbers) {
        $units = ["", "K", "M", "B", "T"];

        for ($i = 0; $num >= 1000; $i++) {
            $num /= 1000;
        }

        $suffix = $units[$i];
        $num = round($num, 1);
    }

    return $numFormatter->format($num) . $suffix;
}

/**
 * Genera la salida SVG a partir de un array de estadísticas.
 *
 * @param array<string,mixed> $stats Estadísticas de rachas
 * @param array<string,string>|NULL $params Parámetros de la solicitud
 * @return string Tarjeta SVG de estadísticas de rachas
 *
 * @throws InvalidArgumentException Si no existe un idioma
 */
function generateCard(array $stats, ?array $params = null): string
{
    $params = $params ?? $_REQUEST;

    // Obtiene el tema solicitado.
    $theme = getRequestedTheme($params);

    // Obtiene el idioma solicitado; español es el valor predeterminado.
    $localeCode = $params["locale"] ?? "es";
    $localeTranslations = getTranslations($localeCode);

    // Determina si el idioma se escribe de derecha a izquierda.
    $direction = $localeTranslations["rtl"] ?? false ? "rtl" : "ltr";

    // Obtiene el formato de fecha.
    // El formateador regional se usa solamente si no se indica date_format.
    $dateFormat = $params["date_format"] ?? ($localeTranslations["date_format"] ?? null);

    // Lee border_radius; utiliza 4.5 si no se proporciona.
    $borderRadius = $params["border_radius"] ?? 4.5;

    $showTotalContributions = ($params["hide_total_contributions"] ?? "") !== "true";
    $showCurrentStreak = ($params["hide_current_streak"] ?? "") !== "true";
    $showLongestStreak = ($params["hide_longest_streak"] ?? "") !== "true";
    $numColumns = intval($showTotalContributions) + intval($showCurrentStreak) + intval($showLongestStreak);

    $cardWidth = getCardWidth($params, $numColumns);
    $rectWidth = $cardWidth - 1;
    $columnWidth = $numColumns > 0 ? $cardWidth / $numColumns : 0;

    $cardHeight = getCardHeight($params);
    $rectHeight = $cardHeight - 1;
    $heightOffset = ($cardHeight - 195) / 2;

    // Desplazamientos X de las barras que separan columnas.
    $barOffsets = [-999, -999];
    for ($i = 0; $i < $numColumns - 1; $i++) {
        $barOffsets[$i] = $columnWidth * ($i + 1);
    }

    // Desplazamientos del texto en cada columna.
    $columnOffsets = [];
    for ($i = 0; $i < $numColumns; $i++) {
        $columnOffsets[] = $columnWidth / 2 + $columnWidth * $i;
    }

    // Invierte las columnas cuando el idioma se escribe de derecha a izquierda.
    if ($direction === "rtl") {
        $columnOffsets = array_reverse($columnOffsets);
    }

    $nextColumnIndex = 0;
    $totalContributionsOffset = $showTotalContributions ? $columnOffsets[$nextColumnIndex++] : -999;
    $currentStreakOffset = $showCurrentStreak ? $columnOffsets[$nextColumnIndex++] : -999;
    $longestStreakOffset = $showLongestStreak ? $columnOffsets[$nextColumnIndex++] : -999;

    // Desplazamientos Y de las barras.
    $barHeightOffsets = [28 + $heightOffset / 2, 170 + $heightOffset];

    // Desplazamientos Y de números y fechas.
    $longestStreakHeightOffset = $totalContributionsHeightOffset = [
        48 + $heightOffset,
        84 + $heightOffset,
        114 + $heightOffset,
    ];

    $currentStreakHeightOffset = [
        48 + $heightOffset,
        108 + $heightOffset,
        145 + $heightOffset,
        71 + $heightOffset,
        19.5 + $heightOffset,
    ];

    $useShortNumbers = ($params["short_numbers"] ?? "") === "true";

    // Contribuciones totales.
    $totalContributions = formatNumber($stats["totalContributions"], $localeCode, $useShortNumbers);
    $firstContribution = formatDate($stats["firstContribution"], $dateFormat, $localeCode);
    $totalContributionsRange = $firstContribution . " - " . $localeTranslations["Present"];

    // Racha actual.
    $currentStreak = formatNumber($stats["currentStreak"]["length"], $localeCode, $useShortNumbers);
    $currentStreakStart = formatDate($stats["currentStreak"]["start"], $dateFormat, $localeCode);
    $currentStreakEnd = formatDate($stats["currentStreak"]["end"], $dateFormat, $localeCode);
    $currentStreakRange = $currentStreakStart;

    if ($currentStreakStart != $currentStreakEnd) {
        $currentStreakRange .= " - " . $currentStreakEnd;
    }

    // Racha más larga.
    $longestStreak = formatNumber($stats["longestStreak"]["length"], $localeCode, $useShortNumbers);
    $longestStreakStart = formatDate($stats["longestStreak"]["start"], $dateFormat, $localeCode);
    $longestStreakEnd = formatDate($stats["longestStreak"]["end"], $dateFormat, $localeCode);
    $longestStreakRange = $longestStreakStart;

    if ($longestStreakStart != $longestStreakEnd) {
        $longestStreakRange .= " - " . $longestStreakEnd;
    }

    // Si las etiquetas superan el máximo o incluyen un salto de línea, se dividen en elementos tspan.
    $maxCharsPerLineLabels = $numColumns > 0 ? intval(floor($cardWidth / $numColumns / 7.5)) : 0;
    $totalContributionsText = splitLines(
        $localeTranslations["Total Contributions"],
        $maxCharsPerLineLabels,
        -9,
    );

    if ($stats["mode"] === "weekly") {
        $currentStreakText = splitLines(
            $localeTranslations["Week Streak"],
            $maxCharsPerLineLabels,
            -9,
        );
        $longestStreakText = splitLines(
            $localeTranslations["Longest Week Streak"],
            $maxCharsPerLineLabels,
            -9,
        );
    } else {
        $currentStreakText = splitLines(
            $localeTranslations["Current Streak"],
            $maxCharsPerLineLabels,
            -9,
        );
        $longestStreakText = splitLines(
            $localeTranslations["Longest Streak"],
            $maxCharsPerLineLabels,
            -9,
        );
    }

    // Si los rangos superan el máximo, se dividen en elementos tspan.
    $maxCharsPerLineDates = $numColumns > 0 ? intval(floor($cardWidth / $numColumns / 6)) : 0;
    $totalContributionsRange = splitLines($totalContributionsRange, $maxCharsPerLineDates, 0);
    $currentStreakRange = splitLines($currentStreakRange, $maxCharsPerLineDates, 0);
    $longestStreakRange = splitLines($longestStreakRange, $maxCharsPerLineDates, 0);

    // Si existen días excluidos, añade una nota en la esquina.
    $excludedDays = "";
    if (!empty($stats["excludedDays"])) {
        $offset = $direction === "rtl" ? $cardWidth - 5 : 5;
        $excludingDaysText = getExcludingDaysText(
            $stats["excludedDays"],
            $localeTranslations,
            $localeCode,
        );

        $excludedDays = "<g style='isolation: isolate'>
                <!-- Días excluidos -->
                <g transform='translate({$offset},187)'>
                    <text stroke-width='0' text-anchor='right' fill='{$theme["excludeDaysLabel"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='400' font-size='10px' font-style='normal' style='opacity: 0; animation: fadein 0.5s linear forwards 0.9s'>
                        * {$excludingDaysText}
                    </text>
                </g>
            </g>";
    }

    return "<svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink'
                style='isolation: isolate' viewBox='0 0 {$cardWidth} {$cardHeight}' width='{$cardWidth}px' height='{$cardHeight}px' direction='{$direction}'>
        <style>
            @keyframes currstreak {
                0% { font-size: 3px; opacity: 0.2; }
                80% { font-size: 34px; opacity: 1; }
                100% { font-size: 28px; opacity: 1; }
            }
            @keyframes fadein {
                0% { opacity: 0; }
                100% { opacity: 1; }
            }
        </style>
        <defs>
            <clipPath id='outer_rectangle'>
                <rect width='{$cardWidth}' height='{$cardHeight}' rx='{$borderRadius}'/>
            </clipPath>
            <mask id='mask_out_ring_behind_fire'>
                <rect width='{$cardWidth}' height='{$cardHeight}' fill='white'/>
                <ellipse id='mask-ellipse' cx='{$currentStreakOffset}' cy='32' rx='13' ry='18' fill='black'/>
            </mask>
            {$theme["backgroundGradient"]}
        </defs>
        <g clip-path='url(#outer_rectangle)'>
            <g style='isolation: isolate'>
                <rect stroke='{$theme["border"]}' fill='{$theme["background"]}' rx='{$borderRadius}' x='0.5' y='0.5' width='{$rectWidth}' height='{$rectHeight}'/>
            </g>
            <g style='isolation: isolate'>
                <line x1='{$barOffsets[0]}' y1='{$barHeightOffsets[0]}' x2='{$barOffsets[0]}' y2='{$barHeightOffsets[1]}' vector-effect='non-scaling-stroke' stroke-width='1' stroke='{$theme["stroke"]}' stroke-linejoin='miter' stroke-linecap='square' stroke-miterlimit='3'/>
                <line x1='{$barOffsets[1]}' y1='{$barHeightOffsets[0]}' x2='{$barOffsets[1]}' y2='{$barHeightOffsets[1]}' vector-effect='non-scaling-stroke' stroke-width='1' stroke='{$theme["stroke"]}' stroke-linejoin='miter' stroke-linecap='square' stroke-miterlimit='3'/>
            </g>
            <g style='isolation: isolate'>
                <!-- Número grande de contribuciones totales -->
                <g transform='translate({$totalContributionsOffset}, {$totalContributionsHeightOffset[0]})'>
                    <text x='0' y='32' stroke-width='0' text-anchor='middle' fill='{$theme["sideNums"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='700' font-size='28px' font-style='normal' style='opacity: 0; animation: fadein 0.5s linear forwards 0.6s'>
                        {$totalContributions}
                    </text>
                </g>

                <!-- Etiqueta de contribuciones totales -->
                <g transform='translate({$totalContributionsOffset}, {$totalContributionsHeightOffset[1]})'>
                    <text x='0' y='32' stroke-width='0' text-anchor='middle' fill='{$theme["sideLabels"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='400' font-size='14px' font-style='normal' style='opacity: 0; animation: fadein 0.5s linear forwards 0.7s'>
                        {$totalContributionsText}
                    </text>
                </g>

                <!-- Rango de contribuciones totales -->
                <g transform='translate({$totalContributionsOffset}, {$totalContributionsHeightOffset[2]})'>
                    <text x='0' y='32' stroke-width='0' text-anchor='middle' fill='{$theme["dates"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='400' font-size='12px' font-style='normal' style='opacity: 0; animation: fadein 0.5s linear forwards 0.8s'>
                        {$totalContributionsRange}
                    </text>
                </g>
            </g>
            <g style='isolation: isolate'>
                <!-- Etiqueta de racha actual -->
                <g transform='translate({$currentStreakOffset}, {$currentStreakHeightOffset[1]})'>
                    <text x='0' y='32' stroke-width='0' text-anchor='middle' fill='{$theme["currStreakLabel"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='700' font-size='14px' font-style='normal' style='opacity: 0; animation: fadein 0.5s linear forwards 0.9s'>
                        {$currentStreakText}
                    </text>
                </g>

                <!-- Rango de racha actual -->
                <g transform='translate({$currentStreakOffset}, {$currentStreakHeightOffset[2]})'>
                    <text x='0' y='21' stroke-width='0' text-anchor='middle' fill='{$theme["dates"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='400' font-size='12px' font-style='normal' style='opacity: 0; animation: fadein 0.5s linear forwards 0.9s'>
                        {$currentStreakRange}
                    </text>
                </g>

                <!-- Anillo alrededor del número -->
                <g mask='url(#mask_out_ring_behind_fire)'>
                    <circle cx='{$currentStreakOffset}' cy='{$currentStreakHeightOffset[3]}' r='40' fill='none' stroke='{$theme["ring"]}' stroke-width='5' style='opacity: 0; animation: fadein 0.5s linear forwards 0.4s'></circle>
                </g>

                <!-- Icono de fuego -->
                <g transform='translate({$currentStreakOffset}, {$currentStreakHeightOffset[4]})' stroke-opacity='0' style='opacity: 0; animation: fadein 0.5s linear forwards 0.6s'>
                    <path d='M -12 -0.5 L 15 -0.5 L 15 23.5 L -12 23.5 L -12 -0.5 Z' fill='none'/>
                    <path d='M 1.5 0.67 C 1.5 0.67 2.24 3.32 2.24 5.47 C 2.24 7.53 0.89 9.2 -1.17 9.2 C -3.23 9.2 -4.79 7.53 -4.79 5.47 L -4.76 5.11 C -6.78 7.51 -8 10.62 -8 13.99 C -8 18.41 -4.42 22 0 22 C 4.42 22 8 18.41 8 13.99 C 8 8.6 5.41 3.79 1.5 0.67 Z M -0.29 19 C -2.07 19 -3.51 17.6 -3.51 15.86 C -3.51 14.24 -2.46 13.1 -0.7 12.74 C 1.07 12.38 2.9 11.53 3.92 10.16 C 4.31 11.45 4.51 12.81 4.51 14.2 C 4.51 16.85 2.36 19 -0.29 19 Z' fill='{$theme["fire"]}' stroke-opacity='0'/>
                </g>

                <!-- Número grande de racha actual -->
                <g transform='translate({$currentStreakOffset}, {$currentStreakHeightOffset[0]})'>
                    <text x='0' y='32' stroke-width='0' text-anchor='middle' fill='{$theme["currStreakNum"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='700' font-size='28px' font-style='normal' style='animation: currstreak 0.6s linear forwards'>
                        {$currentStreak}
                    </text>
                </g>
            </g>
            <g style='isolation: isolate'>
                <!-- Número grande de racha más larga -->
                <g transform='translate({$longestStreakOffset}, {$longestStreakHeightOffset[0]})'>
                    <text x='0' y='32' stroke-width='0' text-anchor='middle' fill='{$theme["sideNums"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='700' font-size='28px' font-style='normal' style='opacity: 0; animation: fadein 0.5s linear forwards 1.2s'>
                        {$longestStreak}
                    </text>
                </g>

                <!-- Etiqueta de racha más larga -->
                <g transform='translate({$longestStreakOffset}, {$longestStreakHeightOffset[1]})'>
                    <text x='0' y='32' stroke-width='0' text-anchor='middle' fill='{$theme["sideLabels"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='400' font-size='14px' font-style='normal' style='opacity: 0; animation: fadein 0.5s linear forwards 1.3s'>
                        {$longestStreakText}
                    </text>
                </g>

                <!-- Rango de racha más larga -->
                <g transform='translate({$longestStreakOffset}, {$longestStreakHeightOffset[2]})'>
                    <text x='0' y='32' stroke-width='0' text-anchor='middle' fill='{$theme["dates"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='400' font-size='12px' font-style='normal' style='opacity: 0; animation: fadein 0.5s linear forwards 1.4s'>
                        {$longestStreakRange}
                    </text>
                </g>
            </g>
            {$excludedDays}
        </g>
    </svg>
";
}

/**
 * Genera una tarjeta SVG con un mensaje de error.
 *
 * @param string $message Mensaje de error que se mostrará
 * @param array<string,string>|NULL $params Parámetros de la solicitud
 * @return string Tarjeta SVG de error generada
 */
function generateErrorCard(string $message, ?array $params = null): string
{
    $params = $params ?? $_REQUEST;

    // Obtiene el tema solicitado.
    $theme = getRequestedTheme($params);

    // Lee border_radius; utiliza 4.5 si no se proporciona.
    $borderRadius = $params["border_radius"] ?? 4.5;

    // Lee card_width.
    $cardWidth = getCardWidth($params);
    $rectWidth = $cardWidth - 1;
    $centerOffset = $cardWidth / 2;

    // Lee card_height.
    $cardHeight = getCardHeight($params);
    $rectHeight = $cardHeight - 1;
    $heightOffset = ($cardHeight - 195) / 2;
    $errorLabelOffset = $cardHeight / 2 + 10.5;

    return "<svg xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' style='isolation: isolate' viewBox='0 0 {$cardWidth} {$cardHeight}' width='{$cardWidth}px' height='{$cardHeight}px'>
        <style>
            a {
                fill: {$theme["dates"]};
            }
        </style>
        <defs>
            <clipPath id='outer_rectangle'>
                <rect width='{$cardWidth}' height='{$cardHeight}' rx='{$borderRadius}'/>
            </clipPath>
            {$theme["backgroundGradient"]}
        </defs>
        <g clip-path='url(#outer_rectangle)'>
            <g style='isolation: isolate'>
                <rect stroke='{$theme["border"]}' fill='{$theme["background"]}' rx='{$borderRadius}' x='0.5' y='0.5' width='{$rectWidth}' height='{$rectHeight}'/>
            </g>
            <g style='isolation: isolate'>
                <!-- Etiqueta de error -->
                <g transform='translate({$centerOffset}, {$errorLabelOffset})'>
                    <text x='0' y='50' dy='0.25em' stroke-width='0' text-anchor='middle' fill='{$theme["sideLabels"]}' stroke='none' font-family='\"Segoe UI\", Ubuntu, sans-serif' font-weight='400' font-size='14px' font-style='normal'>
                        {$message}
                    </text>
                </g>

                <!-- Máscara para el fondo detrás de la cara -->
                <defs>
                    <mask id='cut-off-area'>
                        <rect x='0' y='0' width='500' height='500' fill='white' />
                        <ellipse cx='{$centerOffset}' cy='31' rx='13' ry='18'/>
                    </mask>
                </defs>

                <!-- Cara triste -->
                <g transform='translate({$centerOffset}, {$heightOffset})'>
                    <path fill='{$theme["fire"]}' d='M0,35.8c-25.2,0-45.7,20.5-45.7,45.7s20.5,45.8,45.7,45.8s45.7-20.5,45.7-45.7S25.2,35.8,0,35.8z M0,122.3c-11.2,0-21.4-4.5-28.8-11.9c-2.9-2.9-5.4-6.3-7.4-10c-3-5.7-4.6-12.1-4.6-18.9c0-22.5,18.3-40.8,40.8-40.8 c10.7,0,20.4,4.1,27.7,10.9c3.8,3.5,6.9,7.7,9.1,12.4c2.6,5.3,4,11.3,4,17.6C40.8,104.1,22.5,122.3,0,122.3z'/>
                    <path fill='{$theme["fire"]}' d='M4.8,93.8c5.4,1.1,10.3,4.2,13.7,8.6l3.9-3c-4.1-5.3-10-9-16.6-10.4c-10.6-2.2-21.7,1.9-28.3,10.4l3.9,3 C-13.1,95.3-3.9,91.9,4.8,93.8z'/>
                    <circle fill='{$theme["fire"]}' cx='-15' cy='71' r='4.9'/>
                    <circle fill='{$theme["fire"]}' cx='15' cy='71' r='4.9'/>
                </g>
            </g>
        </g>
    </svg>
";
}

/**
 * Elimina las animaciones de un SVG.
 *
 * @param string $svg SVG de la tarjeta como cadena
 * @return string SVG sin animaciones
 */
function removeAnimations(string $svg): string
{
    $svg = preg_replace("/(<style>\X*?<\/style>)/m", "", $svg);
    $svg = preg_replace("/(opacity: 0;)/m", "opacity: 1;", $svg);
    $svg = preg_replace("/(animation: fadein[^;'\"]+)/m", "opacity: 1;", $svg);
    $svg = preg_replace("/(animation: currstreak[^;'\"]+)/m", "font-size: 28px;", $svg);
    $svg = preg_replace("/<a \X*?>(\X*?)<\/a>/m", '\1', $svg);

    return $svg;
}

/**
 * Convierte un color hexadecimal de 3, 4, 6 u 8 dígitos a hexadecimal de 6 dígitos y opacidad.
 *
 * @param string $color Color que se debe convertir
 * @return array<string, string> Color convertido
 */
function convertHexColor(string $color): array
{
    $color = preg_replace("/[^0-9a-fA-F]/", "", $color);

    // Duplica cada carácter si el color tiene 3 o 4 dígitos.
    if (strlen($color) === 3) {
        $chars = str_split($color);
        $color = "{$chars[0]}{$chars[0]}{$chars[1]}{$chars[1]}{$chars[2]}{$chars[2]}";
    } elseif (strlen($color) === 4) {
        $chars = str_split($color);
        $color = "{$chars[0]}{$chars[0]}{$chars[1]}{$chars[1]}{$chars[2]}{$chars[2]}{$chars[3]}{$chars[3]}";
    }

    // Convierte a hexadecimal de 6 dígitos y opacidad.
    if (strlen($color) === 6) {
        return [
            "color" => "#{$color}",
            "opacity" => 1,
        ];
    } elseif (strlen($color) === 8) {
        return [
            "color" => "#" . substr($color, 0, 6),
            "opacity" => hexdec(substr($color, 6, 2)) / 255,
        ];
    }

    throw new AssertionError("Color no válido: " . $color);
}

/**
 * Convierte colores hexadecimales transparentes de 4 u 8 dígitos de un SVG
 * a colores hexadecimales de 6 dígitos con su atributo de opacidad correspondiente.
 *
 * @param string $svg SVG de la tarjeta como cadena
 * @return string SVG con los colores convertidos
 */
function convertHexColors(string $svg): string
{
    // Convierte "transparent" a "#0000".
    $svg = preg_replace("/(fill|stroke)=['\"]transparent['\"]/m", '\1="#0000"', $svg);

    // Convierte colores hexadecimales y añade el atributo de opacidad correspondiente.
    $svg = preg_replace_callback(
        "/(fill|stroke|stop-color)=['\"]#([0-9a-fA-F]{4}|[0-9a-fA-F]{8})['\"]/m",
        function ($matches) {
            $attribute = $matches[1];
            $opacityAttribute = $attribute === "stop-color"
                ? "stop-opacity"
                : "{$attribute}-opacity";
            $result = convertHexColor($matches[2]);
            $color = $result["color"];
            $opacity = $result["opacity"];

            return "{$attribute}='{$color}' {$opacityAttribute}='{$opacity}'";
        },
        $svg,
    );

    return $svg;
}

/**
 * Convierte una tarjeta SVG en una imagen PNG.
 *
 * @param string $svg SVG de la tarjeta como cadena
 * @param int $cardWidth Ancho de la tarjeta
 * @param int $cardHeight Alto de la tarjeta
 * @return string Datos PNG generados
 */
function convertSvgToPng(string $svg, int $cardWidth, int $cardHeight): string
{
    // Elimina espacios iniciales y finales para que sea una cadena SVG válida.
    $svg = trim($svg);

    // Elimina estilos y animaciones.
    $svg = removeAnimations($svg);

    // Sustituye saltos de línea por espacios.
    $svg = str_replace("\n", " ", $svg);

    // Escapa el SVG para la consola.
    $svg = escapeshellarg($svg);

    // --pipe: lee la entrada estándar.
    // --export-filename -: escribe la salida en la salida estándar.
    // -w y -h: establece el tamaño de salida.
    // --export-type png: establece PNG como formato de salida.
    $cmd = "echo {$svg} | inkscape --pipe --export-filename - -w {$cardWidth} -h {$cardHeight} --export-type png";

    // Convierte el SVG a PNG.
    $png = shell_exec($cmd); // skipcq: PHP-A1009

    // Comprueba que la conversión se haya realizado correctamente.
    if (empty($png)) {
        // 2>&1 redirige la salida de error estándar hacia la salida estándar.
        $error = shell_exec("$cmd 2>&1"); // skipcq: PHP-A1009
        throw new InvalidArgumentException("No se pudo convertir el SVG a PNG: {$error}", 500);
    }

    return $png;
}

/**
 * Devuelve las cabeceras y la respuesta según el tipo solicitado.
 *
 * @param string|array $output Estadísticas que se mostrarán o mensaje de error
 * @param array<string,string>|NULL $params Parámetros de la solicitud
 * @param int $errorCode Código de error HTTP utilizado para respuestas JSON
 * @return array Cabecera Content-Type, cuerpo de respuesta y código de estado en caso de error
 */
function generateOutput(string|array $output, ?array $params = null, int $errorCode = 200): array
{
    $params = $params ?? $_REQUEST;

    $requestedType = $params["type"] ?? "svg";

    // Genera datos JSON.
    if ($requestedType === "json") {
        $data = gettype($output) === "string"
            ? ["error" => $output, "code" => $errorCode]
            : $output;

        return [
            "contentType" => "application/json",
            "body" => json_encode($data),
        ];
    }

    // Genera la tarjeta SVG.
    $svg = gettype($output) === "string"
        ? generateErrorCard($output, $params)
        : generateCard($output, $params);

    // Algunos renderizadores, como Inkscape, no admiten colores hexadecimales transparentes.
    $svg = convertHexColors($svg);

    // Genera una tarjeta PNG.
    if ($requestedType === "png") {
        try {
            // Extrae las dimensiones del SVG.
            $cardWidth = (int) preg_replace("/.*width=[\"'](\d+)px[\"'].*/", "$1", $svg);
            $cardHeight = (int) preg_replace("/.*height=[\"'](\d+)px[\"'].*/", "$1", $svg);
            $png = convertSvgToPng($svg, $cardWidth, $cardHeight);

            return [
                "contentType" => "image/png",
                "body" => $png,
            ];
        } catch (Exception $e) {
            return [
                "contentType" => "image/svg+xml",
                "status" => 500,
                "body" => generateErrorCard($e->getMessage(), $params),
            ];
        }
    }

    // Elimina animaciones si disable_animations está configurado como true.
    if (isset($params["disable_animations"]) && $params["disable_animations"] == "true") {
        $svg = removeAnimations($svg);
    }

    // Devuelve la tarjeta SVG.
    return [
        "contentType" => "image/svg+xml",
        "body" => $svg,
    ];
}

/**
 * Establece cabeceras y envía la respuesta.
 *
 * @param string|array $output Cabecera Content-Type y cuerpo de respuesta
 * @param int $responseCode Código de respuesta HTTP que se enviará
 * @return void La función termina después de enviar la respuesta
 */
function renderOutput(string|array $output, int $responseCode = 200): void
{
    $response = generateOutput($output, null, $responseCode);

    // Siempre devuelve HTTP 200 en SVG y PNG para que GitHub Camo muestre las tarjetas de error.
    // El código de error original se incluye únicamente en respuestas JSON.
    http_response_code(200);
    header("Content-Type: {$response["contentType"]}");
    exit($response["body"]);
}