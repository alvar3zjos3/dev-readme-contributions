<p align="center">
  <img src="https://raw.githubusercontent.com/alvar3zjos3/dev-readme-stats/master/icono dev readme stats.svg" width="88" alt="dev-readme-contributions" />
</p>

<h1 align="center">dev-readme-contributions</h1>

<p align="center">
  Tarjetas dinámicas y estáticas para mostrar tus contribuciones de GitHub,
  <br />
  tu racha actual y tu racha más larga directamente en un README.
</p>

<p align="center">
  <a href="https://github.com/alvar3zjos3/dev-readme-contributions/blob/main/LICENSE">
    <img src="https://img.shields.io/github/license/alvar3zjos3/dev-readme-contributions?style=for-the-badge" alt="Licencia" />
  </a>
  <a href="https://github.com/alvar3zjos3/dev-readme-contributions/stargazers">
    <img src="https://img.shields.io/github/stars/alvar3zjos3/dev-readme-contributions?style=for-the-badge" alt="Estrellas" />
  </a>
  <a href="https://github.com/alvar3zjos3/dev-readme-contributions/issues">
    <img src="https://img.shields.io/github/issues/alvar3zjos3/dev-readme-contributions?style=for-the-badge" alt="Issues" />
  </a>
</p>

<p align="center">
  Parte del ecosistema <a href="https://github.com/alvar3zjos3/dev-readme-stats"><strong>dev-readme-stats</strong></a>.
</p>

> [!IMPORTANT]
> La interfaz, las etiquetas y el formato regional están configurados en **español por defecto**. No necesitas añadir `locale=es` a la URL. Puedes usar el parámetro `locale` solo si quieres solicitar otro idioma.

## Contenido

- [Contenido](#contenido)
- [Qué muestra la tarjeta](#qué-muestra-la-tarjeta)
- [Inicio rápido](#inicio-rápido)
- [Despliegue en Vercel](#despliegue-en-vercel)
  - [Despliegue con un clic](#despliegue-con-un-clic)
  - [Despliegue desde el panel](#despliegue-desde-el-panel)
  - [Variables de Vercel](#variables-de-vercel)
  - [Crear el token de GitHub](#crear-el-token-de-github)
- [Uso en tu README](#uso-en-tu-readme)
  - [Uso mínimo](#uso-mínimo)
  - [Tarjeta enlazada al perfil](#tarjeta-enlazada-al-perfil)
  - [Diseño oscuro personalizado](#diseño-oscuro-personalizado)
  - [Ancho y alto personalizados](#ancho-y-alto-personalizados)
  - [Respuesta JSON](#respuesta-json)
- [SVG estático con GitHub Actions](#svg-estático-con-github-actions)
  - [1. Crea el workflow](#1-crea-el-workflow)
  - [2. Muestra el SVG estático](#2-muestra-el-svg-estático)
- [Opciones de personalización](#opciones-de-personalización)
  - [Ejemplos útiles](#ejemplos-útiles)
- [Cómo se calculan las rachas](#cómo-se-calculan-las-rachas)
- [Instalación local](#instalación-local)
  - [Requisitos](#requisitos)
  - [1. Clona el repositorio](#1-clona-el-repositorio)
  - [2. Instala dependencias](#2-instala-dependencias)
  - [3. Configura el entorno local](#3-configura-el-entorno-local)
  - [4. Inicia el servidor PHP](#4-inicia-el-servidor-php)
  - [Pruebas](#pruebas)
- [Seguridad y variables](#seguridad-y-variables)
- [Créditos y licencia](#créditos-y-licencia)

## Qué muestra la tarjeta

`dev-readme-contributions` consulta el grafo de contribuciones de GitHub y genera una tarjeta SVG con tres métricas principales:

| Métrica | Significado |
|---|---|
| **Contribuciones totales** | Suma de las contribuciones incluidas en el período consultado. |
| **Racha actual** | Número de días consecutivos con al menos una contribución hasta hoy; si hoy todavía no has contribuido, la cuenta llega hasta ayer. |
| **Racha más larga** | El mayor número histórico de días consecutivos con contribuciones. |

La tarjeta se genera como SVG, por lo que se ve nítida a cualquier tamaño y puede incrustarse con una imagen Markdown normal. También admite salida JSON para depuración y automatizaciones.

## Inicio rápido

La forma más rápida de usar el proyecto es desplegar tu propia instancia en Vercel y colocar su URL en tu perfil de GitHub.

1. Despliega el repositorio en Vercel siguiendo la guía de la siguiente sección.
2. Copia el dominio asignado, por ejemplo `dev-readme-contributions.vercel.app`.
3. Añade una imagen como esta a tu `README.md`:

```md
![Racha de contribuciones](https://TU-DOMINIO.vercel.app/?user=TU_USUARIO)
```

Ejemplo para el perfil `alvar3zjos3`:

```md
![Racha de contribuciones](https://TU-DOMINIO.vercel.app/?user=alvar3zjos3&theme=dark&hide_border=true)
```

> [!TIP]
> Sustituye siempre `TU-DOMINIO.vercel.app` por el dominio real de tu proyecto. No uses el endpoint público de otro proyecto si quieres controlar el diseño, los límites de uso y las variables de entorno.

## Despliegue en Vercel

Vercel es la plataforma recomendada para este proyecto. Detecta el proyecto PHP y sirve la tarjeta como una función web sin que tengas que mantener un servidor propio.

### Despliegue con un clic

Pulsa el siguiente botón para crear una copia del repositorio e importarla directamente en Vercel:

[![Desplegar en Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Falvar3zjos3%2Fdev-readme-contributions&env=TOKEN%2CWHITELIST%2CAPP_ENV&envDescription=Token%20personal%20de%20GitHub%20para%20consultas%20GraphQL%20y%20lista%20blanca%20opcional%20de%20usuarios&envLink=https%3A%2F%2Fgithub.com%2Falvar3zjos3%2Fdev-readme-contributions%23seguridad-y-variables&project-name=dev-readme-contributions&repository-name=dev-readme-contributions)

Durante el formulario de Vercel:

1. Elige el nombre de tu nuevo repositorio y crea la copia en tu cuenta de GitHub.
2. Introduce `TOKEN` como variable de entorno y pega tu token personal de GitHub.
3. Opcionalmente, define `WHITELIST` con los usuarios permitidos, separados por comas.
4. Define `APP_ENV=production` si quieres identificar explícitamente el entorno de producción.
5. Pulsa **Deploy**.

### Despliegue desde el panel

Si ya tienes este repositorio en GitHub, puedes conectarlo manualmente:

1. Entra en [Vercel](https://vercel.com/dashboard) y pulsa **Add New → Project**.
2. Importa `alvar3zjos3/dev-readme-contributions` o tu copia del repositorio.
3. En **Environment Variables**, añade las variables de la tabla siguiente.
4. Pulsa **Deploy** y espera a que Vercel asigne el dominio.
5. Abre `https://TU-DOMINIO.vercel.app/?user=TU_USUARIO` para comprobar que la tarjeta responde.

### Variables de Vercel

| Variable | Obligatoria | Ejemplo | Uso |
|---|:---:|---|---|
| `TOKEN` | Sí | `github_pat_...` | Token personal de GitHub usado para consultar el grafo de contribuciones mediante GraphQL. |
| `WHITELIST` | No | `alvar3zjos3,otro_usuario` | Restringe qué usuarios se pueden consultar desde tu instancia. |
| `APP_ENV` | No | `production` | Identifica el entorno de ejecución. |

Si tu instancia solo se va a utilizar en tu perfil, configura una lista blanca:

```env
WHITELIST=alvar3zjos3
```

Así evitas que terceros utilicen tu endpoint y consuman la cuota del token. Si no estableces `WHITELIST`, la tarjeta podrá consultar cualquier nombre de usuario de GitHub.

> [!NOTE]
> Después de crear o modificar una variable en Vercel, haz un nuevo despliegue desde el panel para que el cambio llegue a producción.

### Crear el token de GitHub

1. Abre la [página de creación de tokens personales](https://github.com/settings/tokens/new).
2. Escribe una descripción, por ejemplo `dev-readme-contributions en Vercel`.
3. Para estadísticas públicas no selecciones permisos adicionales.
4. Genera y copia el token.
5. Guárdalo exclusivamente en Vercel, dentro de **Project → Settings → Environment Variables**, con el nombre `TOKEN`.

Nunca añadas el token a `README.md`, `.env.example`, un commit, una captura ni un mensaje. El archivo `.env` local tampoco debe subirse al repositorio.

## Uso en tu README

La ruta principal es `/`. Añade `?user=` y el nombre de usuario de GitHub que quieres consultar.

### Uso mínimo

```md
![Racha de GitHub](https://TU-DOMINIO.vercel.app/?user=TU_USUARIO)
```

### Tarjeta enlazada al perfil

```html
<a href="https://github.com/TU_USUARIO">
  <img src="https://TU-DOMINIO.vercel.app/?user=TU_USUARIO" alt="Racha de contribuciones de TU_USUARIO" />
</a>
```

### Diseño oscuro personalizado

```md
![Racha de GitHub](https://TU-DOMINIO.vercel.app/?user=alvar3zjos3&theme=dark&hide_border=true&ring=58a6ff&fire=ff7b72)
```

### Ancho y alto personalizados

```md
![Racha de GitHub](https://TU-DOMINIO.vercel.app/?user=alvar3zjos3&card_width=600&card_height=210)
```

### Respuesta JSON

La respuesta JSON resulta útil si quieres comprobar los datos sin analizar el SVG o integrar el cálculo en otro proyecto:

```text
https://TU-DOMINIO.vercel.app/?user=alvar3zjos3&type=json
```

## SVG estático con GitHub Actions

La URL dinámica de Vercel es la opción más cómoda porque actualiza la tarjeta al cargarse. Sin embargo, puedes generar un SVG estático en tu repositorio de perfil si prefieres que tu README no dependa de que Vercel responda cada vez que GitHub renderiza la imagen.

Con este método, GitHub Actions consulta tu instancia de Vercel una vez al día, guarda el SVG resultante en tu repositorio de perfil y actualiza el archivo automáticamente.

> [!IMPORTANT]
> Este workflow se añade al repositorio especial de tu perfil de GitHub, es decir, `TU_USUARIO/TU_USUARIO`; no a este repositorio de la API.

### 1. Crea el workflow

En tu repositorio de perfil, crea el archivo `.github/workflows/update-contributions.yml`:

```yaml
name: Actualizar tarjeta de contribuciones

on:
  schedule:
    - cron: "15 2 * * *"
  workflow_dispatch:

permissions:
  contents: write

jobs:
  actualizar-svg:
    runs-on: ubuntu-latest

    steps:
      - name: Descargar repositorio de perfil
        uses: actions/checkout@v4

      - name: Descargar tarjeta SVG desde Vercel
        run: |
          mkdir -p profile
          curl --fail --silent --show-error \
            "https://TU-DOMINIO.vercel.app/?user=${{ github.repository_owner }}&theme=dark&hide_border=true" \
            --output profile/contributions.svg

      - name: Guardar cambios
        run: |
          git config user.name "github-actions[bot]"
          git config user.email "41898282+github-actions[bot]@users.noreply.github.com"
          git add profile/contributions.svg
          git diff --cached --quiet || git commit -m "ci: actualizar tarjeta de contribuciones"
          git push
```

Sustituye `TU-DOMINIO.vercel.app` por el dominio real de Vercel antes de guardar el workflow.

La programación `15 2 * * *` ejecuta la actualización una vez al día a las 02:15 UTC. También puedes lanzarla manualmente desde la pestaña **Actions** con el botón **Run workflow**.

### 2. Muestra el SVG estático

Tras la primera ejecución correcta, añade esto al `README.md` de tu perfil:

```md
![Racha de contribuciones](./profile/contributions.svg)
```

El SVG se actualizará con el horario definido. Si no hubo cambios de contribuciones, el workflow no crea un commit vacío.

> [!TIP]
> Para evitar que GitHub muestre una copia almacenada en caché durante demasiado tiempo, puedes añadir una versión basada en la fecha o actualizar periódicamente el archivo como hace el workflow anterior.

## Opciones de personalización

El único parámetro necesario es `user`. Todos los demás son opcionales. Los valores de color aceptan hexadecimal sin `#` o nombres de color CSS cuando el parámetro lo indica.

Cuando usas `theme`, los colores individuales que indiques después sobrescriben el color equivalente del tema.

| Parámetro | Descripción | Ejemplo |
|---|---|---|
| `user` | Usuario de GitHub del que se mostrarán datos | `alvar3zjos3` |
| `theme` | Tema visual de la tarjeta | `default`, `dark`, `radical` |
| `hide_border` | Oculta el borde | `true` o `false` |
| `border_radius` | Redondez de las esquinas | `0`, `4.5`, `20` |
| `background` | Fondo; acepta hex sin `#`, color CSS o degradado | `0d1117`, `black`, `45,0d1117,161b22` |
| `border` | Color del borde | `30363d` |
| `stroke` | Color de las líneas entre secciones | `58a6ff` |
| `ring` | Color del anillo de racha actual | `58a6ff` |
| `fire` | Color del fuego | `ff7b72` |
| `currStreakNum` | Color del número de racha actual | `58a6ff` |
| `sideNums` | Color de cifras laterales | `c9d1d9` |
| `currStreakLabel` | Color de la etiqueta central | `8b949e` |
| `sideLabels` | Color de las etiquetas laterales | `8b949e` |
| `dates` | Color del intervalo de fechas | `8b949e` |
| `excludeDaysLabel` | Color de la etiqueta de días excluidos | `8b949e` |
| `date_format` | Formato personalizado de fechas | `d/m[/Y]` |
| `locale` | Idioma y formato regional alternativos | `en`, `fr`, `pt_BR` |
| `timezone` | Zona horaria para determinar el día actual | `Europe/Madrid` |
| `short_numbers` | Abrevia cifras como `1.5k` | `true` o `false` |
| `type` | Formato de respuesta | `svg` o `json` |
| `mode` | Tipo de racha | `daily` o `weekly` |
| `exclude_days` | Días que no cuentan para la racha | `Sun,Sat` |
| `disable_animations` | Desactiva animaciones SVG | `true` o `false` |
| `card_width` | Ancho en píxeles | `495` |
| `card_height` | Alto en píxeles | `195` |
| `hide_total_contributions` | Oculta contribuciones totales | `true` o `false` |
| `hide_current_streak` | Oculta racha actual | `true` o `false` |
| `hide_longest_streak` | Oculta racha más larga | `true` o `false` |
| `starting_year` | Año inicial de contribuciones | `2017` |

### Ejemplos útiles

Tarjeta sin borde y con fondo oscuro:

```text
https://TU-DOMINIO.vercel.app/?user=alvar3zjos3&background=0d1117&border=30363d&hide_border=true
```

Racha semanal, excluyendo el fin de semana:

```text
https://TU-DOMINIO.vercel.app/?user=alvar3zjos3&mode=weekly&exclude_days=Sat,Sun
```

Tarjeta compacta sin el total de contribuciones:

```text
https://TU-DOMINIO.vercel.app/?user=alvar3zjos3&hide_total_contributions=true&card_width=360
```

## Cómo se calculan las rachas

La aplicación utiliza el grafo de contribuciones de GitHub para determinar en qué fechas existe actividad. GitHub considera contribuciones, entre otros eventos compatibles, commits, issues y pull requests creados en repositorios que cumplan sus criterios de contabilización.

- La **racha actual** es una secuencia de días con una o más contribuciones. Si ya contribuiste hoy, el conteo termina hoy; si no, termina ayer para evitar que la racha aparezca como cero antes de que termine el día.
- La **racha más larga** es el tramo histórico más largo de días consecutivos con actividad.
- En modo `daily`, necesitas contribuir cada día no excluido.
- En modo `weekly`, basta una contribución durante cada semana para mantener la secuencia.
- Con `exclude_days`, los días indicados no interrumpen ni aumentan la racha.

GitHub puede tardar hasta 24 horas en reflejar nuevas contribuciones en el gráfico. Si deseas mostrar contribuciones privadas, activa la opción de visualizarlas en tu perfil de GitHub; la visibilidad y los datos disponibles también dependen del token y de la configuración de tu cuenta.

## Instalación local

La producción se sirve desde Vercel, pero puedes ejecutar el proyecto en tu equipo para modificar el diseño, probar temas y comprobar cambios antes de hacer push.

### Requisitos

- PHP en una versión compatible con el proyecto.
- Composer para instalar las dependencias PHP.
- Git para clonar y gestionar el repositorio.
- Un token personal de GitHub para evitar límites de API durante las pruebas.

### 1. Clona el repositorio

```bash
git clone git@github.com:alvar3zjos3/dev-readme-contributions.git
cd dev-readme-contributions
```

### 2. Instala dependencias

```bash
composer install
```

### 3. Configura el entorno local

Crea tu archivo privado `.env` a partir de la plantilla:

```bash
cp .env.example .env
```

En Windows con Git Bash puedes usar el mismo comando. En PowerShell utiliza:

```powershell
Copy-Item .env.example .env
```

Abre `.env` y añade solo tu token local:

```env
TOKEN=tu_token_personal_de_github
APP_ENV=development
```

No añadas tokens reales a `.env.example`. Confirma también que `.env` está ignorado por Git:

```bash
git check-ignore -v .env
```

### 4. Inicia el servidor PHP

Desde la raíz del proyecto:

```bash
php -S localhost:8000
```

Abre esta dirección en el navegador:

```text
http://localhost:8000/?user=alvar3zjos3
```

Mientras el servidor esté activo, cualquier cambio que hagas en los archivos PHP se verá al recargar la página.

### Pruebas

El repositorio incluye pruebas con PHPUnit. Ejecútalas con:

```bash
composer test
```

Si ese script no existe en tu `composer.json`, utiliza:

```bash
./vendor/bin/phpunit
```

## Seguridad y variables

El archivo `.env.example` se sube al repositorio porque solo contiene nombres de variables y valores de ejemplo. El archivo `.env` contiene tu configuración privada y debe seguir ignorado.

Incluye estas reglas en `.gitignore`:

```gitignore
.env
.env.*
!.env.example
DOCKER_ENV
```

| Archivo o variable | ¿Se sube a GitHub? | Uso correcto |
|---|:---:|---|
| `.env.example` | Sí | Plantilla sin secretos reales. |
| `.env` | No | Configuración privada para desarrollo local. |
| `TOKEN` en Vercel | No | Variable de entorno configurada desde el panel de Vercel. |
| `WHITELIST` en Vercel | No | Restricción opcional de usuarios permitidos. |
| `GITHUB_TOKEN` de Actions | No | Token automático utilizado únicamente por un workflow. |

Nunca añadas tokens que comiencen por `ghs_`, `ghp_` o `github_pat_` a un commit. GitHub bloquea los pushes que contienen secretos detectados, incluso si el secreto está únicamente en un commit antiguo del historial.

## Créditos y licencia

`dev-readme-contributions` adapta la base de [DenverCoder1/github-readme-streak-stats](https://github.com/DenverCoder1/github-readme-streak-stats) para integrarla con el ecosistema de [dev-readme-stats](https://github.com/alvar3zjos3/dev-readme-stats).

Consulta [LICENSE](./LICENSE) para conocer las condiciones de uso y distribución aplicables.

---

Hecho con PHP, GitHub y Vercel.