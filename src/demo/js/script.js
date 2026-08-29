/*global jscolor*/
/*eslint no-undef: "error"*/

/**
 * Configuración del proyecto para enlaces y textos generados.
 *
 * Puede redefinirse desde index.php antes de cargar este archivo mediante
 * window.DEV_README_CONTRIBUTIONS_CONFIG.
 */
const APP_CONFIG = Object.freeze({
  repositoryUrl: "https://github.com/alvar3zjos3/dev-readme-contributions",
  cardAlt: "Estadísticas de contribuciones de GitHub",
  markdownLabel: "Estadísticas de contribuciones de GitHub",
  ...(window.DEV_README_CONTRIBUTIONS_CONFIG || {}),
});

const preview = {
  /**
   * Valores predeterminados de la API y de la interfaz.
   *
   * Los parámetros con estos valores se omiten de las URLs generadas. El
   * backend aplica español y Europe/Madrid si no se incluyen explícitamente.
   */
  defaults: {
    theme: "default",
    hide_border: "false",
    date_format: "",
    locale: "es",
    timezone: "Europe/Madrid",
    border_radius: "4.5",
    mode: "daily",
    type: "svg",
    exclude_days: "",
    card_width: "495",
    card_height: "195",
    hide_total_contributions: "false",
    hide_current_streak: "false",
    hide_longest_streak: "false",
    short_numbers: "false",
  },

  updateTimer: null,
  jsonRequestController: null,

  /**
   * Actualiza la tarjeta, los enlaces y los bloques de código.
   */
  update() {
    const params = this.getParams();
    const query = this.createQuery(params);
    const cardUrl = this.createCardUrl(query);
    const previewUrl = query ? `preview.php?${query}` : "preview.php";

    if (params.type === "json") {
      this.renderJsonPreview(cardUrl, previewUrl);
    } else {
      this.renderImagePreview(cardUrl, previewUrl);
    }

    this.updateButtonStates();
  },

  /**
   * Extrae y normaliza parámetros de los controles visibles.
   *
   * @returns {Object} Parámetros para la API de tarjetas
   */
  getParams() {
    const params = this.objectFromElements(document.querySelectorAll(".param"));

    /*
     * Las métricas se obtienen directamente de sus casillas marcadas. No se
     * depende del campo oculto #sections, que puede estar desincronizado al
     * cargar la página.
     */
    const visibleSections = Array.from(
      document.querySelectorAll(".sections input[type='checkbox']:checked"),
    ).map((checkbox) => checkbox.value);

    params.hide_total_contributions = String(!visibleSections.includes("total"));
    params.hide_current_streak = String(!visibleSections.includes("current"));
    params.hide_longest_streak = String(!visibleSections.includes("longest"));

    delete params.sections;

    return params;
  },

  /**
   * Crea una cadena de consulta y omite los valores predeterminados.
   *
   * @param {Object} params Parámetros de la tarjeta
   * @returns {string} Cadena de consulta URL codificada
   */
  createQuery(params) {
  return Object.entries(params)
    .filter(([key, value]) => {
      // Nunca incluye parámetros vacíos, por ejemplo timezone=.
      if (value === "") {
        return false;
      }

      // Omite los valores predeterminados, como locale=es.
      return value !== this.defaults[key];
    })
    .map(([key, value]) => {
      return `${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
    })
    .join("&");
},

  /**
   * Construye la URL pública de la tarjeta.
   *
   * @param {string} query Cadena de consulta URL codificada
   * @returns {string} URL pública de la tarjeta
   */
  createCardUrl(query) {
    return query
      ? `${window.location.origin}/?${query}`
      : `${window.location.origin}/`;
  },

  /**
   * Renderiza la vista previa SVG/PNG y genera Markdown y HTML.
   *
   * @param {string} cardUrl URL pública de la tarjeta
   * @param {string} previewUrl URL local de vista previa
   */
  renderImagePreview(cardUrl, previewUrl) {
    const image = document.querySelector(".output img");
    const markdownCode = document.querySelector(".md code");
    const htmlCode = document.querySelector(".html code");

    if (!image || !markdownCode || !htmlCode) {
      return;
    }

    const markdown = `[![${APP_CONFIG.markdownLabel}](${cardUrl})](${APP_CONFIG.repositoryUrl})`;
    const html = `<a href="${APP_CONFIG.repositoryUrl}"><img src="${cardUrl}" alt="${APP_CONFIG.cardAlt}" /></a>`;

    image.onerror = () => {
      image.alt = "No se pudo cargar la tarjeta. Revisa la configuración del servidor.";
    };

    image.onload = () => {
      image.alt = APP_CONFIG.cardAlt;
    };

    image.src = previewUrl;
    markdownCode.innerText = markdown;
    htmlCode.innerText = html;

    this.setVisible(".copy-md", true);
    this.setVisible(".copy-html", true);
    this.setVisible(".output img", true);
    this.setVisible(".output .json", false);
    this.setVisible(".copy-json", false);
  },

  /**
   * Renderiza la respuesta JSON.
   *
   * @param {string} cardUrl URL pública para solicitar JSON
   * @param {string} previewUrl URL local para solicitar JSON
   */
  renderJsonPreview(cardUrl, previewUrl) {
    const jsonOutput = document.querySelector(".output .json pre");
    const jsonCode = document.querySelector(".json code");

    if (!jsonOutput || !jsonCode) {
      return;
    }

    /*
     * Asegura que la solicitud realmente pida JSON incluso si la URL creada
     * todavía no contiene el parámetro type=json.
     */
    const jsonPreviewUrl = this.appendQueryParameter(previewUrl, "type", "json");
    const jsonCardUrl = this.appendQueryParameter(cardUrl, "type", "json");

    jsonCode.innerText = jsonCardUrl;
    jsonOutput.classList.remove("json-error");
    jsonOutput.innerText = "Cargando estadísticas…";

    if (this.jsonRequestController) {
      this.jsonRequestController.abort();
    }

    this.jsonRequestController = new AbortController();

    fetch(jsonPreviewUrl, {
      headers: {
        Accept: "application/json",
      },
      signal: this.jsonRequestController.signal,
    })
      .then(async (response) => {
        const text = await response.text();
        let data;

        try {
          data = JSON.parse(text);
        } catch {
          throw new Error("El servidor no devolvió una respuesta JSON válida.");
        }

        if (!response.ok || data.error) {
          throw new Error(
            data.error || `La solicitud falló con el estado ${response.status}.`,
          );
        }

        return data;
      })
      .then((data) => {
        jsonOutput.innerText = JSON.stringify(data, null, 2);
      })
      .catch((error) => {
        if (error.name === "AbortError") {
          return;
        }

        jsonOutput.innerText = `Error: ${error.message}`;
        jsonOutput.classList.add("json-error");
      });

    this.setVisible(".copy-md", false);
    this.setVisible(".copy-html", false);
    this.setVisible(".output img", false);
    this.setVisible(".output .json", true);
    this.setVisible(".copy-json", true);
  },

  /**
   * Añade o reemplaza un parámetro de consulta en una URL.
   *
   * @param {string} url URL base
   * @param {string} key Nombre del parámetro
   * @param {string} value Valor del parámetro
   * @returns {string} URL actualizada
   */
  appendQueryParameter(url, key, value) {
    const separator = url.includes("?") ? "&" : "?";
    const pattern = new RegExp(`([?&])${key}=[^&]*`);

    if (pattern.test(url)) {
      return url.replace(pattern, `$1${key}=${encodeURIComponent(value)}`);
    }

    return `${url}${separator}${encodeURIComponent(key)}=${encodeURIComponent(value)}`;
  },

  /**
   * Cambia la visibilidad de una sección.
   *
   * @param {string} selector Selector del control o contenedor
   * @param {boolean} visible Indica si debe mostrarse
   */
  setVisible(selector, visible) {
    const element = document.querySelector(selector);

    if (!element) {
      return;
    }

    const container = element.classList.contains("copy-button")
      ? element.parentElement
      : element;

    container.style.display = visible ? "block" : "none";
  },

  /**
   * Actualiza el estado de botones dependientes del formulario.
   */
  updateButtonStates() {
    const userField = document.querySelector("#user");
    const hasValidUser = Boolean(
      userField
      && userField.value.trim() !== ""
      && userField.validity.valid,
    );

    document.querySelectorAll(".copy-button").forEach((button) => {
      button.disabled = !hasValidUser;
    });

    const clearButton = document.querySelector("#clear-button");
    if (clearButton) {
      clearButton.disabled = document.querySelectorAll(".minus").length === 0;
    }
  },

  /**
   * Añade una propiedad de color a la personalización avanzada.
   *
   * @param {string} [property] Propiedad a añadir
   * @param {string} [value] Valor inicial de la propiedad
   */
  addProperty(property, value = "#EB5454FF") {
    const select = document.querySelector("#properties");
    const propertyName = property || select?.value;

    if (!select || !propertyName || select.disabled || this.hasAdvancedProperty(propertyName)) {
      return;
    }

    const option = Array.from(select.options).find(
      (item) => item.value === propertyName,
    );

    if (!option) {
      return;
    }

    option.disabled = true;
    this.selectFirstAvailableProperty(select);

    const parent = document.querySelector(".advanced .color-properties");
    if (!parent) {
      return;
    }

    const gradientEnabled = document.querySelector(
      "#background-type-gradient",
    )?.checked;

    if (propertyName === "background" && gradientEnabled) {
      this.addGradientBackground(parent, propertyName, value);
    } else {
      this.addColorProperty(parent, propertyName, value);
    }

    this.addRemovalButton(parent, propertyName);
    this.update();
  },

  /**
   * Comprueba si una propiedad avanzada ya está presente.
   *
   * @param {string} property Nombre de la propiedad
   * @returns {boolean} True si ya existe
   */
  hasAdvancedProperty(property) {
    return Boolean(
      document.querySelector(
        `.advanced [data-property="${CSS.escape(property)}"]`,
      ),
    );
  },

  /**
   * Selecciona la primera propiedad que sigue disponible.
   *
   * @param {HTMLSelectElement} select Selector de propiedades
   */
  selectFirstAvailableProperty(select) {
    const firstAvailable = Array.from(select.options).find(
      (option) => !option.disabled,
    );

    if (firstAvailable) {
      select.value = firstAvailable.value;
    } else {
      select.disabled = true;
    }
  },

  /**
   * Añade controles para un gradiente de fondo.
   *
   * @param {Element} parent Contenedor de propiedades
   * @param {string} propertyName Nombre de la propiedad
   * @param {string} value Valor: ángulo,color1,color2
   */
  addGradientBackground(parent, propertyName, value) {
    const parts = value.split(",");
    const [angle, color1, color2] = parts.length === 3
      ? parts
      : ["45", "#EB5454FF", "#EB5454FF"];

    const label = document.createElement("span");
    label.id = "background-label";
    label.innerText = "Fondo";
    label.setAttribute("data-property", propertyName);

    const wrapper = document.createElement("span");
    wrapper.className = "grid-middle";
    wrapper.setAttribute("data-property", propertyName);
    wrapper.setAttribute("role", "group");
    wrapper.setAttribute("aria-labelledby", "background-label");

    const angleGroup = document.createElement("div");
    angleGroup.className = "input-text-group";

    const angleInput = document.createElement("input");
    angleInput.className = "param";
    angleInput.id = "rotate";
    angleInput.name = propertyName;
    angleInput.type = "number";
    angleInput.value = angle;
    angleInput.placeholder = "45";
    angleInput.setAttribute("aria-label", "Ángulo del gradiente");

    const degree = document.createElement("span");
    degree.innerText = "°";

    angleGroup.append(angleInput, degree);

    const firstColor = this.createColorInput(
      "background-color1",
      propertyName,
      color1,
      "Primer color del gradiente",
    );

    const secondColor = this.createColorInput(
      "background-color2",
      propertyName,
      color2,
      "Segundo color del gradiente",
    );

    parent.append(label);
    wrapper.append(angleGroup, firstColor, secondColor);
    parent.append(wrapper);

    jscolor.install(wrapper);
    this.checkColor(firstColor.value, firstColor.id);
    this.checkColor(secondColor.value, secondColor.id);
  },

  /**
   * Añade un control de color sólido.
   *
   * @param {Element} parent Contenedor de propiedades
   * @param {string} propertyName Nombre de la propiedad
   * @param {string} value Valor inicial
   */
  addColorProperty(parent, propertyName, value) {
    const label = document.createElement("label");
    label.htmlFor = propertyName;
    label.innerText = propertyName;
    label.setAttribute("data-property", propertyName);

    const input = this.createColorInput(
      propertyName,
      propertyName,
      value,
      `Color para ${propertyName}`,
    );

    input.setAttribute("data-property", propertyName);

    parent.append(label, input);
    jscolor.install(parent);
    this.checkColor(input.value, input.id);
  },

  /**
   * Crea un input de color compatible con jscolor.
   *
   * @param {string} id Id del input
   * @param {string} name Nombre del parámetro API
   * @param {string} value Valor inicial
   * @param {string} label Etiqueta accesible
   * @returns {HTMLInputElement} Input creado
   */
  createColorInput(id, name, value, label) {
    const input = document.createElement("input");

    input.className = "param jscolor";
    input.id = id;
    input.name = name;
    input.value = value;
    input.setAttribute("aria-label", label);
    input.setAttribute(
      "data-jscolor",
      JSON.stringify({
        format: "hexa",
        onChange: `preview.pickerChange(this, '${id}')`,
        onInput: `preview.pickerChange(this, '${id}')`,
      }),
    );

    return input;
  },

  /**
   * Añade un botón para eliminar una propiedad avanzada.
   *
   * @param {Element} parent Contenedor de propiedades
   * @param {string} propertyName Propiedad asociada
   */
  addRemovalButton(parent, propertyName) {
    const button = document.createElement("button");

    button.className = "minus btn";
    button.type = "button";
    button.innerText = "−";
    button.setAttribute("data-property", propertyName);
    button.setAttribute(
      "aria-label",
      `Eliminar la propiedad ${propertyName}`,
    );

    button.addEventListener("click", () => this.removeProperty(propertyName));
    parent.append(button);
  },

  /**
   * Elimina una propiedad avanzada.
   *
   * @param {string} property Nombre de la propiedad
   */
  removeProperty(property) {
    const parent = document.querySelector(".advanced .color-properties");
    const select = document.querySelector("#properties");

    if (!parent || !select) {
      return;
    }

    parent
      .querySelectorAll(`[data-property="${CSS.escape(property)}"]`)
      .forEach((element) => element.remove());

    const option = Array.from(select.options).find(
      (item) => item.value === property,
    );

    if (option) {
      option.disabled = false;
      select.disabled = false;
      select.value = option.value;
    }

    this.update();
  },

  /**
   * Elimina todas las propiedades avanzadas.
   */
  removeAllProperties() {
    const properties = [
      ...new Set(
        Array.from(document.querySelectorAll(".advanced [data-property]"))
          .map((element) => element.getAttribute("data-property"))
          .filter(Boolean),
      ),
    ];

    properties.forEach((property) => this.removeProperty(property));
  },

  /**
   * Convierte los campos .param a un objeto de parámetros.
   *
   * @param {NodeListOf<HTMLInputElement|HTMLSelectElement>} elements Campos
   * @returns {Object} Parámetros normalizados
   */
  objectFromElements(elements) {
    return Array.from(elements).reduce((params, element) => {
      if (!element.name) {
        return params;
      }

      let value = element.value;

      if (value.includes("#")) {
        value = value.replace(/#/g, "");

        if (value.length > 6) {
          value = value.replace(/[Ff]{2}$/, "");
        }
      }

      params[element.name] = element.name in params
        ? `${params[element.name]},${value}`
        : value;

      return params;
    }, {});
  },

  /**
   * Exporta la personalización de colores como array PHP.
   */
  exportPhp() {
    const themeSelect = document.querySelector("#theme");
    const selectedTheme = themeSelect?.options[themeSelect.selectedIndex];

    if (!selectedTheme) {
      return;
    }

    const defaults = { ...selectedTheme.dataset };
    const advanced = this.objectFromElements(
      document.querySelectorAll(".advanced .param"),
    );
    const properties = { ...defaults, ...advanced };

    const mappings = Object.entries(properties)
      .map(([key, value]) => {
        const formattedValue = value.includes(",") ? value : `#${value}`;
        return `    "${key}" => "${formattedValue}",`;
      })
      .join("\n");

    const textarea = document.querySelector("#exported-php");

    if (textarea) {
      textarea.value = `[\n${mappings}\n]`;
      textarea.hidden = false;
    }
  },

  /**
   * Elimina el alfa FF de colores completamente opacos.
   *
   * @param {string} color Color hexadecimal
   * @param {string} inputId Id del campo de color
   */
  checkColor(color, inputId) {
    if (color.length !== 9 || color.slice(-2).toUpperCase() !== "FF") {
      return;
    }

    const input = document.getElementById(inputId);

    if (input) {
      input.value = color.slice(0, -2);
    }
  },

  /**
   * Actualiza el color tras una espera breve para evitar muchas peticiones.
   *
   * @param {Object} picker Instancia jscolor
   * @param {string} inputId Id del campo asociado
   */
  pickerChange(picker, inputId) {
    this.checkColor(picker.toHEXAString(), inputId);

    clearTimeout(this.updateTimer);
    this.updateTimer = setTimeout(() => this.update(), 80);
  },

  /**
   * Marca o desmarca casillas a partir de una lista separada por comas.
   *
   * Si el valor no existe, no modifica las casillas actuales. Esto conserva
   * el estado predeterminado del HTML: Total, Actual y Máxima seleccionadas.
   *
   * @param {string|null} value Valor de la URL
   * @param {string} selector Selector del grupo de casillas
   */
  updateCheckboxes(value, selector) {
    if (value === null) {
      return;
    }

    const selectedValues = value === "" ? [] : value.split(",");
    const checkboxes = document.querySelectorAll(
      `${selector} input[type="checkbox"]`,
    );

    checkboxes.forEach((checkbox) => {
      checkbox.checked = selectedValues.includes(checkbox.value);
    });
  },

  /**
   * Aplica los parámetros de la URL actual al formulario.
   *
   * @param {URLSearchParams} [searchParams] Parámetros de URL
   */
  updateFormInputs(searchParams = new URLSearchParams(window.location.search)) {
    const backgroundValues = searchParams.getAll("background");

    if (backgroundValues.length > 1) {
      const gradient = document.querySelector("#background-type-gradient");

      if (gradient) {
        gradient.checked = true;
      }
    }

    const advancedKeys = new Set();

    searchParams.forEach((value, key) => {
      const input = document.querySelector(
        `[name="${CSS.escape(key)}"]`,
      );

      if (input) {
        input.value = value;
      } else if (key !== "background") {
        advancedKeys.add(key);
      }
    });

    advancedKeys.forEach((key) => {
      const advanced = document.querySelector("details.advanced");

      if (advanced) {
        advanced.open = true;
      }

      this.addProperty(key, searchParams.getAll(key).join(","));
    });

    if (backgroundValues.length > 0) {
      const advanced = document.querySelector("details.advanced");

      if (advanced) {
        advanced.open = true;
      }

      this.addProperty("background", backgroundValues.join(","));
    }

    /*
     * Si sections no viene en la URL, se conserva el estado HTML por defecto:
     * las tres métricas están marcadas.
     */
    this.updateCheckboxes(searchParams.get("exclude_days"), ".weekdays");
    this.updateCheckboxes(searchParams.get("sections"), ".sections");
    this.syncCheckboxValues();
  },

  /**
   * Sincroniza los campos ocultos de días excluidos y secciones visibles.
   */
  syncCheckboxValues() {
    this.syncCheckboxValue(".weekdays", "#exclude-days");
    this.syncCheckboxValue(".sections", "#sections");
  },

  /**
   * Sincroniza un grupo de casillas con un campo oculto.
   *
   * @param {string} groupSelector Selector del grupo
   * @param {string} targetSelector Selector del input oculto
   */
  syncCheckboxValue(groupSelector, targetSelector) {
    const target = document.querySelector(targetSelector);

    if (!target) {
      return;
    }

    target.value = Array.from(
      document.querySelectorAll(
        `${groupSelector} input[type="checkbox"]:checked`,
      ),
    )
      .map((checkbox) => checkbox.value)
      .join(",");
  },
};

const clipboard = {
  /**
   * Copia un bloque de salida al portapapeles.
   *
   * @param {HTMLButtonElement} button Botón pulsado
   */
  async copy(button) {
    const selector = button.classList.contains("copy-md")
      ? ".md code"
      : button.classList.contains("copy-html")
        ? ".html code"
        : ".json code";

    const text = document.querySelector(selector)?.innerText ?? "";

    try {
      await this.writeText(text);
      button.title = "¡Copiado!";
    } catch {
      button.title = "No se pudo copiar. Selecciona el texto manualmente.";
    }
  },

  /**
   * Escribe texto en el portapapeles con API moderna y alternativa.
   *
   * @param {string} text Texto a copiar
   * @returns {Promise<void>} Resultado de la operación
   */
  writeText(text) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(text);
    }

    return new Promise((resolve, reject) => {
      const textarea = document.createElement("textarea");

      textarea.value = text;
      textarea.setAttribute("readonly", "");
      textarea.style.position = "fixed";
      textarea.style.opacity = "0";

      document.body.append(textarea);
      textarea.select();

      try {
        const copied = document.execCommand("copy");
        textarea.remove();

        if (copied) {
          resolve();
        } else {
          reject(new Error("No se pudo copiar el texto."));
        }
      } catch (error) {
        textarea.remove();
        reject(error);
      }
    });
  },
};

const tooltip = {
  /**
   * Elimina el texto temporal del tooltip.
   *
   * @param {Element} element Elemento cuyo tooltip se restablece
   */
  reset(element) {
    element.removeAttribute("title");
  },
};

window.addEventListener("load", () => {
  const form = document.querySelector(".parameters");
  const mode = document.querySelector("#mode");
  const solidBackground = document.querySelector("#background-type-solid");
  const gradientBackground = document.querySelector("#background-type-gradient");

  if (!form || !mode || !solidBackground || !gradientBackground) {
    return;
  }

  /**
   * Alterna entre fondo sólido y gradiente conservando el color actual.
   */
  const toggleBackgroundType = () => {
    const currentColor = document.querySelector(
      "#background, #background-color1",
    )?.value;

    preview.removeProperty("background");

    if (!currentColor) {
      return;
    }

    if (gradientBackground.checked) {
      preview.addProperty(
        "background",
        `45,${currentColor},${currentColor}`,
      );
    } else {
      preview.addProperty("background", currentColor);
    }
  };

  /**
   * Deshabilita los días excluidos cuando el modo es semanal.
   */
  const toggleExcludedDays = () => {
    const isWeekly = mode.value === "weekly";

    document
      .querySelectorAll(".weekdays input[type='checkbox']")
      .forEach((checkbox) => {
        checkbox.disabled = isWeekly;

        const label = checkbox.nextElementSibling;

        if (label) {
          label.title = isWeekly
            ? "No disponible en el modo semanal"
            : label.dataset.tooltip;
        }
      });
  };

  form.addEventListener("input", (event) => {
    if (event.target.matches(".param")) {
      preview.update();
    }
  });

  form.addEventListener("change", (event) => {
    if (event.target.matches(".weekdays input[type='checkbox']")) {
      preview.syncCheckboxValue(".weekdays", "#exclude-days");
    }

    if (event.target.matches(".sections input[type='checkbox']")) {
      preview.syncCheckboxValue(".sections", "#sections");
    }

    if (event.target === mode) {
      toggleExcludedDays();
    }

    if (
      event.target === solidBackground
      || event.target === gradientBackground
    ) {
      toggleBackgroundType();
      return;
    }

    preview.update();
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();

    const params = preview.getParams();
    const query = preview.createQuery(params);
    const destination = query
      ? `${window.location.pathname}?${query}`
      : window.location.pathname;

    window.location.assign(destination);
  });

  preview.updateFormInputs();
  toggleExcludedDays();
  preview.update();
});