# Guía de contribución

¡Las contribuciones son bienvenidas! Puedes abrir un issue para informar de un error, proponer una mejora o realizar una pregunta técnica. Si quieres aportar código, documentación, traducciones, temas, pruebas o mejoras de despliegue, envía un pull request.

`dev-readme-contributions` forma parte del ecosistema de [dev-readme-stats](https://github.com/alvar3zjos3/dev-readme-stats). Antes de enviar cambios, procura que la propuesta sea útil, esté centrada en un objetivo claro y haya sido probada de forma local.

## Contenido

- [Guía de contribución](#guía-de-contribución)
  - [Contenido](#contenido)
  - [Antes de empezar](#antes-de-empezar)
  - [Alcance técnico](#alcance-técnico)
  - [Compatibilidad](#compatibilidad)
  - [Requisitos locales](#requisitos-locales)
    - [Linux (Debian/Ubuntu)](#linux-debianubuntu)
    - [Windows](#windows)
  - [Configuración del proyecto](#configuración-del-proyecto)
    - [1. Haz un fork y clona tu copia](#1-haz-un-fork-y-clona-tu-copia)
    - [2. Instala las dependencias](#2-instala-las-dependencias)
    - [3. Configura las variables locales](#3-configura-las-variables-locales)
    - [4. Inicia la aplicación](#4-inicia-la-aplicación)
  - [Pruebas y formato](#pruebas-y-formato)
  - [Validación por tipo de cambio](#validación-por-tipo-de-cambio)
  - [Flujo de contribución](#flujo-de-contribución)
    - [1. Actualiza tu copia local](#1-actualiza-tu-copia-local)
    - [2. Crea una rama](#2-crea-una-rama)
    - [3. Implementa y prueba el cambio](#3-implementa-y-prueba-el-cambio)
    - [4. Añade los archivos correctos](#4-añade-los-archivos-correctos)
    - [5. Crea un commit claro](#5-crea-un-commit-claro)
    - [6. Sube tu rama](#6-sube-tu-rama)
    - [7. Abre el pull request](#7-abre-el-pull-request)
  - [Desarrollo con Docker](#desarrollo-con-docker)
  - [Reglas de seguridad](#reglas-de-seguridad)
  - [Checklist de pull request](#checklist-de-pull-request)
  - [Cambios del proyecto original](#cambios-del-proyecto-original)
  - [Código de conducta](#código-de-conducta)
  - [¿Necesitas ayuda?](#necesitas-ayuda)

## Antes de empezar

Antes de crear un issue o pull request:

- Busca en los [issues existentes](https://github.com/alvar3zjos3/dev-readme-contributions/issues) para evitar duplicados.
- Describe el problema con pasos para reproducirlo, el resultado actual y el resultado esperado.
- No incluyas tokens, archivos `.env`, capturas con secretos ni datos privados.
- Para cambios visuales, adjunta una captura o una URL reproducible con los parámetros utilizados.
- Para cambios de comportamiento, añade o actualiza las pruebas correspondientes.
- Prueba el cambio localmente antes de abrir un pull request.
- Mantén cada issue y pull request centrado en un único objetivo siempre que sea posible.

## Alcance técnico

Antes de editar el código, identifica el componente responsable. Mantén los cambios pequeños, aislados y acompañados de pruebas o documentación cuando corresponda.

| Tipo de cambio | Archivos habituales | Validación esperada |
|---|---|---|
| Nuevo parámetro de URL | `src/index.php`, `src/card.php` | SVG, JSON, valor predeterminado y valor inválido |
| Cálculo de contribuciones o rachas | `src/stats.php`, `tests/StatsTest.php` | Casos normales, vacíos y casos límite |
| Diseño de la tarjeta | `src/card.php`, `tests/expected/` | Comparación visual y URL reproducible |
| Tema nuevo | `src/themes.php`, documentación de temas | Vista previa y contraste legible |
| Traducción | `src/translations.php` | Etiquetas sin cortes ni desbordamientos |
| Despliegue o automatización | `vercel.json`, `Dockerfile`, `.github/` | Build, contenedor o workflow comprobado |
| Documentación | `README.md`, `CONTRIBUTING.md`, `docs/` | Enlaces, ejemplos y comandos revisados |

## Compatibilidad

`dev-readme-contributions` se utiliza mediante URLs incrustadas en README. Por ese motivo, los cambios deben conservar la compatibilidad con tarjetas y enlaces existentes.

- No elimines ni renombres parámetros de URL publicados sin discutirlo previamente en un issue.
- Los parámetros nuevos deben ser opcionales y tener valores predeterminados seguros.
- No cambies el comportamiento de un tema existente sin documentarlo.
- No modifiques la estructura de la respuesta `type=json` sin actualizar las pruebas y la documentación.
- Evita añadir solicitudes innecesarias a la API de GitHub; consumen cuota y pueden ralentizar la respuesta de la tarjeta.
- Si un cambio es incompatible, explícalo claramente en el issue, el pull request y la documentación afectada.

## Requisitos locales

Necesitas las siguientes herramientas para ejecutar, probar y formatear el proyecto en local:

| Herramienta | Uso | Requisito |
|---|---|---|
| PHP | Ejecutar la aplicación y PHPUnit | PHP 8.3 o una versión compatible con el proyecto |
| Composer | Instalar dependencias y ejecutar scripts | Versión estable actual |
| Git | Clonar el repositorio y gestionar ramas | Versión estable actual |
| Extensión `intl` de PHP | Formato de fechas, números e idiomas | Obligatoria |
| Inkscape | Generar y probar tarjetas PNG | Opcional; SVG y JSON no lo necesitan |
| Docker / Docker Compose | Ejecutar un entorno reproducible en contenedor | Opcional |

> [!NOTE]
> SVG es el formato principal y funciona sin Inkscape. Solo necesitas Inkscape para desarrollar, generar o validar la salida `type=png`.

### Linux (Debian/Ubuntu)

Instala PHP, extensiones y herramientas básicas:

```bash
sudo apt update
sudo apt install -y php php-cli php-curl php-intl composer git
```

Instala Inkscape solo si necesitas validar PNG:

```bash
sudo apt install -y inkscape
```

Comprueba que las herramientas estén disponibles:

```bash
php -v
composer --version
git --version
```

### Windows

Puedes instalar PHP desde [php.net](https://windows.php.net/download/) o mediante una distribución local como [XAMPP](https://www.apachefriends.org/). Comprueba que PHP y Composer estén disponibles en el `PATH` de PowerShell o Git Bash.

Instala también:

- [Composer](https://getcomposer.org/download/)
- [Git para Windows](https://git-scm.com/download/win)
- [Inkscape](https://inkscape.org/release/) solo si vas a probar `type=png`

Comprueba la instalación:

```bash
php -v
composer --version
git --version
```

## Configuración del proyecto

### 1. Haz un fork y clona tu copia

Haz un fork de [dev-readme-contributions](https://github.com/alvar3zjos3/dev-readme-contributions) en tu cuenta de GitHub. Después clona tu fork y registra el repositorio principal como remoto `upstream`:

```bash
git clone git@github.com:TU_USUARIO/dev-readme-contributions.git
cd dev-readme-contributions
git remote add upstream https://github.com/alvar3zjos3/dev-readme-contributions.git
```

Comprueba los remotos configurados:

```bash
git remote -v
```

Debes tener:

- `origin`: tu fork; aquí subirás tus ramas.
- `upstream`: el repositorio principal; se utiliza para actualizar tu copia local.

### 2. Instala las dependencias

```bash
composer install
```

Composer instala las dependencias PHP definidas por el proyecto, incluidas las herramientas necesarias para pruebas y formato en desarrollo.

### 3. Configura las variables locales

Copia `.env.example` como `.env`:

```bash
cp .env.example .env
```

En PowerShell para Windows:

```powershell
Copy-Item .env.example .env
```

Edita `.env` y añade tu token personal de GitHub:

```env
TOKEN=tu_token_personal_de_github
APP_ENV=development
```

Durante las pruebas puedes restringir las consultas a tu propio usuario:

```env
WHITELIST=tu_usuario_de_github
```

El archivo `.env` es privado. No lo subas al repositorio y no añadas nunca un token real a `.env.example`.

Comprueba que Git lo ignora:

```bash
git check-ignore -v .env
```

### 4. Inicia la aplicación

La forma habitual de iniciar el servidor de desarrollo es:

```bash
composer start
```

Si el script `start` no está definido en `composer.json`, usa el servidor integrado de PHP:

```bash
php -S localhost:8000 -t src
```

Prueba una tarjeta en el navegador:

```text
http://localhost:8000/?user=TU_USUARIO
```

Para solicitar los datos en JSON:

```text
http://localhost:8000/?user=TU_USUARIO&type=json
```

Si el proyecto incluye una ruta de demostración, puedes abrir:

```text
http://localhost:8000/demo/
```

## Pruebas y formato

Ejecuta las pruebas antes de enviar cambios:

```bash
composer test
```

Como alternativa, ejecuta PHPUnit directamente:

```bash
./vendor/bin/phpunit
```

En PowerShell:

```powershell
php .\vendor\bin\phpunit
```

El proyecto utiliza Prettier para mantener el formato uniforme de PHP, Markdown, JavaScript y CSS.

Comprueba los archivos que necesitan formato:

```bash
composer lint
```

Aplica el formato automáticamente:

```bash
composer lint-fix
```

Antes de abrir un pull request, intenta que estos comandos terminen sin errores:

```bash
composer test
composer lint
```

## Validación por tipo de cambio

Además de ejecutar pruebas y formato, valida el comportamiento específico de tu modificación.

| Cambio | Comprobaciones mínimas |
|---|---|
| SVG o layout | Tema claro, tema oscuro, tarjeta compacta y etiquetas largas |
| Rachas | Sin contribuciones, contribución hoy, contribución ayer, racha larga y zona horaria |
| Modo semanal | Semanas con y sin contribuciones, más días excluidos |
| Colores | Hexadecimal, color CSS, degradado y valor no válido |
| JSON | Respuesta válida, errores controlados y campos documentados |
| Docker | Construcción de imagen y solicitud `/?user=TU_USUARIO` |
| Vercel | Despliegue de vista previa y solicitud al endpoint |
| GitHub Actions | Ejecución manual, SVG generado y ausencia de commits vacíos |

Para una modificación visual, incluye una URL reproducible en el pull request, por ejemplo:

```text
http://localhost:8000/?user=alvar3zjos3&theme=dark&hide_border=true
```

No incluyas un token en la URL ni en la descripción del pull request.

## Flujo de contribución

### 1. Actualiza tu copia local

Antes de comenzar un cambio, actualiza la rama `master` desde el repositorio principal:

```bash
git checkout master
git fetch upstream
git rebase upstream/master
git push origin master
```

Si tienes cambios sin guardar, realiza un commit, usa `git stash` o resuélvelos antes de ejecutar el rebase.

### 2. Crea una rama

Crea una rama para cada cambio. Su nombre debe describir el objetivo:

```bash
git checkout -b feat/nombre-del-cambio
```

Ejemplos:

```bash
git checkout -b feat/add-custom-theme
git checkout -b fix/current-streak-timezone
git checkout -b docs/improve-vercel-guide
git checkout -b test/add-weekly-streak-cases
```

No desarrolles directamente en `master` si vas a enviar el cambio como pull request.

### 3. Implementa y prueba el cambio

Haz las modificaciones necesarias y pruébalas en local.

- Para cambios de diseño, verifica el SVG con temas, tamaños y valores de color distintos.
- Para cambios de cálculo, prueba rachas vacías, activas, contribuciones hoy, contribuciones ayer, semanas y días excluidos.
- Para traducciones, revisa que las etiquetas no se corten ni desborden la tarjeta.
- Para cambios de API, revisa las respuestas SVG y `type=json`.
- Para documentación, comprueba enlaces, bloques de código y ejemplos.

### 4. Añade los archivos correctos

Revisa el estado del proyecto:

```bash
git status
```

Cuando sea posible, añade archivos de forma selectiva:

```bash
git add src/card.php src/themes.php
```

También puedes añadir todos los archivos que pertenecen al mismo cambio:

```bash
git add .
```

Revisa el contenido que se incluirá en el commit:

```bash
git diff --cached
```

Comprueba especialmente que `.env`, tokens, archivos temporales, cachés y salidas generadas no se hayan incluido.

### 5. Crea un commit claro

Usa mensajes descriptivos con el formato Conventional Commits:

```bash
git commit -m "tipo: descripción breve"
```

| Prefijo | Cuándo usarlo | Ejemplo |
|---|---|---|
| `feat:` | Función nueva | `feat: add compact card layout` |
| `fix:` | Corrección de un error | `fix: handle current streak after midnight` |
| `docs:` | Documentación | `docs: clarify Docker deployment steps` |
| `test:` | Pruebas | `test: add excluded day streak cases` |
| `refactor:` | Reorganización sin cambio funcional | `refactor: simplify GraphQL response parsing` |
| `style:` | Formato o estilo sin cambio funcional | `style: format SVG markup` |
| `ci:` | Workflows y automatización | `ci: update static SVG workflow` |
| `chore:` | Mantenimiento o configuración interna | `chore: update Composer dependencies` |

Ejemplo:

```bash
git commit -m "feat: add custom contribution theme"
```

### 6. Sube tu rama

```bash
git push -u origin feat/nombre-del-cambio
```

GitHub mostrará un botón para abrir el pull request. También puedes crearlo desde la pestaña **Pull requests** de tu fork.

### 7. Abre el pull request

El pull request debe dirigirse desde tu rama hacia `master` en `alvar3zjos3/dev-readme-contributions`.

Incluye en la descripción:

- Qué problema resuelve o qué función incorpora.
- Qué archivos o componentes cambiaste.
- Cómo probaste el cambio.
- Una URL reproducible o captura, si modificaste el diseño.
- Una referencia al issue relacionado usando `Closes #NUMERO`, cuando corresponda.

Mantén cada pull request limitado a una mejora. Evita combinar refactors grandes, cambios visuales, traducciones y funciones nuevas si pueden separarse.

## Desarrollo con Docker

Docker es opcional, pero permite reproducir un entorno con Apache, PHP, la extensión `intl` e Inkscape.

Construye la imagen desde la raíz del proyecto:

```bash
docker build -t dev-readme-contributions .
```

Ejecuta el contenedor e inyecta las variables solo en tiempo de ejecución:

```bash
docker run --rm -p 8080:80 \
  -e TOKEN=tu_token_personal_de_github \
  -e WHITELIST=TU_USUARIO \
  dev-readme-contributions
```

Prueba la aplicación:

```text
http://localhost:8080/?user=TU_USUARIO
```

Para validar la salida PNG:

```text
http://localhost:8080/?user=TU_USUARIO&type=png
```

No introduzcas secretos mediante `COPY`, `ARG`, `ENV` ni commits en el `Dockerfile`.

## Reglas de seguridad

- Nunca publiques `TOKEN`, `GITHUB_TOKEN`, `ACTION_TOKEN` ni claves con prefijos como `ghs_`, `ghp_` o `github_pat_`.
- No hagas commit de `.env`, `DOCKER_ENV`, archivos de caché, credenciales ni resultados que contengan información privada.
- Usa `.env.example` solo como plantilla, con valores vacíos o ficticios.
- Configura secretos de producción desde Vercel, Docker Compose/tu proveedor o GitHub Actions; nunca desde archivos versionados.
- Si GitHub bloquea un push por detección de secretos, elimina el secreto del archivo y del historial antes de volver a subirlo. No desbloquees un secreto real solo para completar el push.

## Checklist de pull request

Antes de enviar un pull request, confirma lo siguiente:

- [ ] Leí esta guía y busqué issues o pull requests similares.
- [ ] Mi rama se centra en un único cambio principal.
- [ ] Actualicé mi copia local desde `upstream/master`.
- [ ] Probé el cambio localmente.
- [ ] Ejecuté `composer test`.
- [ ] Ejecuté `composer lint`.
- [ ] Añadí o actualicé pruebas si modifiqué lógica.
- [ ] Actualicé la documentación si cambié una opción, URL o comportamiento.
- [ ] Incluí una URL reproducible o una captura si modifiqué el diseño.
- [ ] No incluí `.env`, tokens, credenciales, cachés ni archivos generados accidentalmente.
- [ ] No rompí parámetros existentes ni respuestas JSON sin documentarlo.

## Cambios del proyecto original

`dev-readme-contributions` adapta la base de `DenverCoder1/github-readme-streak-stats`, pero mantiene su identidad, configuración, documentación y decisiones propias dentro del ecosistema `dev-readme-stats`.

Si quieres adaptar una mejora del proyecto original:

1. Abre un issue explicando qué mejora quieres portar.
2. Enlaza el commit, issue o pull request de origen.
3. Adapta el cambio al estilo, idioma y configuración de este repositorio.
4. Añade pruebas y documentación propias.
5. Envía un pull request independiente y centrado en ese cambio.

No sincronices directamente el repositorio original sobre `master`: podrías sobrescribir personalizaciones, documentación y decisiones específicas de `dev-readme-contributions`.

## Código de conducta

Al participar en el proyecto aceptas respetar el [Código de conducta](./CODE_OF_CONDUCT.md). Trata a las demás personas con respeto, ofrece críticas constructivas y centra las conversaciones en mejorar el proyecto.

## ¿Necesitas ayuda?

Si te atascas con Git, GitHub, PHP, Vercel, Docker o el flujo de contribución, abre un [issue](https://github.com/alvar3zjos3/dev-readme-contributions/issues/new/choose) con la información relevante. No incluyas secretos, tokens ni variables privadas.

¡Gracias por contribuir a `dev-readme-contributions`!
