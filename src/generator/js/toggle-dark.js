/**
 * Nombre de la cookie que almacena la preferencia visual.
 */
const THEME_COOKIE_NAME = "darkmode";

/**
 * Duración de la preferencia de tema: 365 días.
 */
const THEME_COOKIE_DAYS = 365;

/**
 * Guarda una cookie de forma segura.
 *
 * @param {string} name Nombre de la cookie
 * @param {string} value Valor de la cookie
 * @param {number} days Días hasta el vencimiento
 */
function setCookie(name, value, days) {
  const expiresAt = new Date();
  expiresAt.setTime(expiresAt.getTime() + days * 24 * 60 * 60 * 1000);

  const secure = window.location.protocol === "https:" ? "; Secure" : "";

  document.cookie = [
    `${encodeURIComponent(name)}=${encodeURIComponent(value)}`,
    `Expires=${expiresAt.toUTCString()}`,
    "Path=/",
    "SameSite=Lax",
    secure,
  ].join("; ");
}

/**
 * Obtiene el valor de una cookie.
 *
 * @param {string} name Nombre de la cookie
 * @returns {string|null} Valor de la cookie o null si no existe
 */
function getCookie(name) {
  const prefix = `${encodeURIComponent(name)}=`;

  const cookie = document.cookie
    .split("; ")
    .find((entry) => entry.startsWith(prefix));

  if (!cookie) {
    return null;
  }

  return decodeURIComponent(cookie.substring(prefix.length));
}

/**
 * Actualiza el icono del botón de tema.
 *
 * @param {boolean} isDark Indica si el tema oscuro está activo
 */
function updateThemeIcon(isDark) {
  const icon = document.querySelector("#darkmode-icon");

  if (icon) {
    icon.textContent = isDark ? "🌞" : "🌙";
  }
}

/**
 * Aplica el tema oscuro y guarda la preferencia.
 *
 * @param {boolean} [persist=true] Indica si se debe guardar una cookie
 */
function enableDarkMode(persist = true) {
  document.body.setAttribute("data-theme", "dark");
  updateThemeIcon(true);

  if (persist) {
    setCookie(THEME_COOKIE_NAME, "on", THEME_COOKIE_DAYS);
  }
}

/**
 * Aplica el tema claro y guarda la preferencia.
 *
 * @param {boolean} [persist=true] Indica si se debe guardar una cookie
 */
function enableLightMode(persist = true) {
  document.body.removeAttribute("data-theme");
  updateThemeIcon(false);

  if (persist) {
    setCookie(THEME_COOKIE_NAME, "off", THEME_COOKIE_DAYS);
  }
}

/**
 * Alterna entre tema claro y oscuro.
 *
 * Esta función permanece global porque index.php la llama desde:
 * onclick="toggleTheme()".
 */
function toggleTheme() {
  const isDark = document.body.getAttribute("data-theme") === "dark";

  if (isDark) {
    enableLightMode();
  } else {
    enableDarkMode();
  }
}

/**
 * Aplica la preferencia guardada. Si el usuario no ha elegido un tema,
 * usa la preferencia del sistema sin crear una cookie.
 */
function initializeTheme() {
  const savedTheme = getCookie(THEME_COOKIE_NAME);

  if (savedTheme === "on") {
    enableDarkMode(false);
    return;
  }

  if (savedTheme === "off") {
    enableLightMode(false);
    return;
  }

  const systemPrefersDark = window.matchMedia(
    "(prefers-color-scheme: dark)",
  ).matches;

  if (systemPrefersDark) {
    enableDarkMode(false);
  } else {
    enableLightMode(false);
  }
}

initializeTheme();