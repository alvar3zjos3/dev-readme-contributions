/**
 * Acordeón animado para elementos <details>.
 *
 * Basado en el patrón de animación de <details> con Web Animations API.
 * Respeta la preferencia del usuario de reducir movimiento.
 */
class Accordion {
  /**
   * @param {HTMLDetailsElement} element Elemento <details> que se animará
   */
  constructor(element) {
    this.element = element;
    this.summary = element.querySelector("summary");
    this.content = element.querySelector(".content");
    this.animation = null;
    this.isClosing = false;
    this.isExpanding = false;
    this.reducedMotion = window.matchMedia(
      "(prefers-reduced-motion: reduce)",
    ).matches;
  }

  /**
   * Inicializa los eventos del acordeón.
   */
  init() {
    if (!this.summary || !this.content) {
      return;
    }

    this.summary.addEventListener("click", (event) => this.onClick(event));
  }

  /**
   * Gestiona la apertura y cierre del elemento.
   *
   * @param {MouseEvent} event Evento de clic
   */
  onClick(event) {
    event.preventDefault();

    if (this.reducedMotion) {
      this.element.open = !this.element.open;
      return;
    }

    this.element.style.overflow = "hidden";

    if (this.isClosing || !this.element.open) {
      this.open();
    } else if (this.isExpanding || this.element.open) {
      this.shrink();
    }
  }

  /**
   * Anima el cierre del acordeón.
   */
  shrink() {
    this.isClosing = true;

    const startHeight = `${this.element.offsetHeight}px`;
    const endHeight = `${this.summary.offsetHeight}px`;

    this.cancelAnimation();

    this.animation = this.element.animate(
      {
        height: [startHeight, endHeight],
      },
      {
        duration: 250,
        easing: "ease-out",
      },
    );

    this.animation.onfinish = () => this.finish(false);
    this.animation.oncancel = () => {
      this.isClosing = false;
    };
  }

  /**
   * Abre el elemento y prepara la animación de expansión.
   */
  open() {
    this.element.style.height = `${this.element.offsetHeight}px`;
    this.element.open = true;

    window.requestAnimationFrame(() => this.expand());
  }

  /**
   * Anima la expansión del acordeón.
   */
  expand() {
    this.isExpanding = true;

    const startHeight = `${this.element.offsetHeight}px`;
    const endHeight = `${this.summary.offsetHeight + this.content.offsetHeight}px`;

    this.cancelAnimation();

    this.animation = this.element.animate(
      {
        height: [startHeight, endHeight],
      },
      {
        duration: 250,
        easing: "ease-out",
      },
    );

    this.animation.onfinish = () => this.finish(true);
    this.animation.oncancel = () => {
      this.isExpanding = false;
    };
  }

  /**
   * Cancela la animación actual si existe.
   */
  cancelAnimation() {
    if (this.animation) {
      this.animation.cancel();
    }
  }

  /**
   * Restablece el estado del elemento cuando termina una animación.
   *
   * @param {boolean} isOpen Indica si el elemento queda abierto
   */
  finish(isOpen) {
    this.element.open = isOpen;
    this.animation = null;
    this.isClosing = false;
    this.isExpanding = false;
    this.element.style.height = "";
    this.element.style.overflow = "";
  }
}

/**
 * Inicializa todos los acordeones de la página.
 */
function initializeAccordions() {
  document.querySelectorAll("details").forEach((element) => {
    new Accordion(element).init();
  });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeAccordions);
} else {
  initializeAccordions();
}