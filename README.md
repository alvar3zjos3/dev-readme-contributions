<p align="center">
  <h1 align="center">dev-readme-contributions</h1>
</p>

<p align="center">
  Tarjetas dinámicas y estáticas para mostrar contribuciones totales,
  <br />
  racha actual y racha más larga de GitHub en cualquier README.
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
> Las etiquetas de la tarjeta están en **español por defecto**. No necesitas añadir `locale=es` a tus URLs. Usa el parámetro `locale` solamente si deseas solicitar la tarjeta en otro idioma.

## Contenido

- [Contenido](#contenido)
- [Características](#características)
- [Inicio rápido](#inicio-rápido)
- [Despliegue en Vercel](#despliegue-en-vercel)
  - [Despliegue con un clic](#despliegue-con-un-clic)
  - [Despliegue desde el panel](#despliegue-desde-el-panel)
  - [Variables de entorno](#variables-de-entorno)
  - [Token de GitHub](#token-de-github)
- [Despliegue con Docker](#despliegue-con-docker)
  - [Inicio con Docker](#inicio-con-docker)
  - [Docker Compose](#docker-compose)
- [Uso en el README](#uso-en-el-readme)
  - [Uso mínimo](#uso-mínimo)
  - [Imagen enlazada al perfil](#imagen-enlazada-al-perfil)
  - [Tema y colores personalizados](#tema-y-colores-personalizados)
  - [Tamaño personalizado](#tamaño-personalizado)
  - [Respuesta JSON](#respuesta-json)
- [SVG estático con GitHub Actions](#svg-estático-con-github-actions)
  - [1. Crea el workflow](#1-crea-el-workflow)
  - [2. Inserta el SVG generado](#2-inserta-el-svg-generado)
- [Opciones de personalización](#opciones-de-personalización)
  - [Ejemplos adicionales](#ejemplos-adicionales)
- [Cómo se calculan las rachas](#cómo-se-calculan-las-rachas)
- [Seguridad y variables](#seguridad-y-variables)
- [Desarrollo y contribución](#desarrollo-y-contribución)
- [Créditos y licencia](#créditos-y-licencia)

## Características

`dev-readme-contributions` genera una tarjeta SVG con la actividad de un perfil de GitHub. La aplicación consulta el grafo de contribuciones y devuelve una imagen lista para incrustar en un README, una web o cualquier sitio que admita imágenes remotas.

| Métrica | Qué representa |
|---|---|
| **Contribuciones totales** | Suma de las contribuciones incluidas en el período consultado. |
| **Racha actual** | Días consecutivos con al menos una contribución; cuenta hasta hoy o hasta ayer cuando aún no hay actividad hoy. |
| **Racha más larga** | Mayor secuencia histórica de días consecutivos con una o más contribuciones. |

Características principales:

- Tarjetas dinámicas en SVG, nítidas a cualquier tamaño.
- Salida JSON para inspeccionar o reutilizar las métricas en otros proyectos.
- Temas predefinidos y colores individuales personalizables.
- Español como idioma predeterminado, con soporte opcional para otros idiomas.
- Rachas diarias y semanales.
- Exclusión de días concretos del cálculo, como sábados y domingos.
- Despliegue público en Vercel o autoalojamiento mediante Docker.
- Generación de SVG estático mediante GitHub Actions para perfiles que no quieran depender de una solicitud dinámica.
- Lista blanca opcional para limitar los perfiles que pueden consultar tu instancia.

## Inicio rápido

Despliega una instancia propia con Vercel o Docker. Cuando tengas una URL pública, añade esta imagen al `README.md` de tu perfil de GitHub:

```md
![Racha de contribuciones](https://TU-DOMINIO/?user=TU_USUARIO)
```

Ejemplo con tema oscuro:

```md
![Racha de contribuciones](https://TU-DOMINIO/?user=alvar3zjos3&theme=dark&hide_border=true)
```

`TU-DOMINIO` puede ser un dominio de Vercel, como `dev-readme-contributions.vercel.app`, o el dominio/IP de tu servidor Docker. El parámetro `user` es el único obligatorio.

## Despliegue en Vercel

Vercel es la forma más sencilla de crear una URL pública sin mantener un servidor. Importas el repositorio, defines las variables de entorno y Vercel crea un despliegue nuevo cuando actualizas la rama de producción conectada.

### Despliegue con un clic

[![Desplegar en Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Falvar3zjos3%2Fdev-readme-contributions&env=TOKEN%2CWHITELIST%2CAPP_ENV&envDescription=Token%20de%20GitHub%20para%20consultas%20GraphQL%20y%20lista%20blanca%20opcional&envLink=https%3A%2F%2Fgithub.com%2Falvar3zjos3%2Fdev-readme-contributions%23seguridad-y-variables&project-name=dev-readme-contributions&repository-name=dev-readme-contributions)

El botón crea una copia del repositorio en tu cuenta de GitHub y abre el formulario de importación de Vercel.

1. Elige el nombre de tu repositorio y confirma su creación.
2. Introduce las variables de entorno de la tabla siguiente.
3. Pulsa **Deploy**.
4. Copia el dominio que Vercel asigne al proyecto y pruébalo con `?user=TU_USUARIO`.

### Despliegue desde el panel

1. Abre el [panel de Vercel](https://vercel.com/dashboard).
2. Pulsa **Add New → Project**.
3. Importa `alvar3zjos3/dev-readme-contributions` o una copia propia del repositorio.
4. Añade las variables de entorno antes de desplegar.
5. Pulsa **Deploy**.

### Variables de entorno

| Variable | Obligatoria | Ejemplo | Uso |
|---|:---:|---|---|
| `TOKEN` | Sí | `github_pat_...` | Token personal de GitHub usado para consultar la API GraphQL. |
| `WHITELIST` | No | `alvar3zjos3,otro_usuario` | Usuarios autorizados, separados por comas. |
| `APP_ENV` | No | `production` | Identifica el entorno de producción. |

Para una instancia personal, limita las consultas a tu propio usuario:

```env
WHITELIST=alvar3zjos3
```

Si no defines `WHITELIST`, cualquier persona que conozca la URL podrá solicitar tarjetas para otros perfiles. Esto puede consumir la cuota del token configurado en tu proyecto.

### Token de GitHub

1. Abre la [página de creación de tokens personales de GitHub](https://github.com/settings/tokens/new).
2. Añade una descripción reconocible, por ejemplo `dev-readme-contributions`.
3. Para consultar contribuciones públicas, no selecciones permisos adicionales.
4. Genera y copia el token.
5. En Vercel, abre **Project → Settings → Environment Variables** y crea la variable `TOKEN` con ese valor.
6. Si añadiste o modificaste la variable después de desplegar, crea un redeploy desde Vercel.

> [!NOTE]
> Vercel no instala Inkscape. En Vercel usa `type=svg` —el formato predeterminado— o `type=json`. Para usar `type=png`, despliega mediante Docker o en un servidor con Inkscape disponible.

## Despliegue con Docker

Docker es una alternativa si necesitas controlar el entorno de ejecución, alojar la aplicación en un VPS, NAS o servidor propio, o generar tarjetas PNG. El `Dockerfile` incluye PHP 8.3, Apache, Composer, la extensión `intl`, fuentes DejaVu e Inkscape.

### Inicio con Docker

Desde la raíz del repositorio, construye la imagen:

```bash
docker build -t dev-readme-contributions .
```

Inicia el contenedor. Sustituye los valores de ejemplo sin compartir tu token real:

```bash
docker run -d \
  --name dev-readme-contributions \
  --restart unless-stopped \
  -p 8080:80 \
  -e TOKEN=tu_token_personal_de_github \
  -e WHITELIST=alvar3zjos3 \
  dev-readme-contributions
```

Comprueba que responde:

```text
http://localhost:8080/?user=alvar3zjos3
```

La imagen Docker incluye Inkscape, por lo que también admite salida PNG:

```text
http://localhost:8080/?user=alvar3zjos3&type=png
```

### Docker Compose

Si prefieres Docker Compose, crea un archivo local `compose.yaml`:

```yaml
services:
  dev-readme-contributions:
    build: .
    container_name: dev-readme-contributions
    restart: unless-stopped
    ports:
      - "8080:80"
    environment:
      TOKEN: ${TOKEN}
      WHITELIST: ${WHITELIST:-}
```

Crea también un `.env` local que no subas al repositorio:

```env
TOKEN=tu_token_personal_de_github
WHITELIST=alvar3zjos3
```

Inicia el servicio:

```bash
docker compose up -d --build
```

Para consultar registros:

```bash
docker compose logs -f
```

> [!WARNING]
> No pongas un token real en `Dockerfile`, `compose.yaml` versionado, capturas, comandos compartidos ni documentación pública. Usa secretos de tu plataforma o un archivo `.env` que Git ignore.

## Uso en el README

La tarjeta se sirve desde la ruta principal. Indica siempre el perfil mediante `user`.

### Uso mínimo

```md
![Racha de GitHub](https://TU-DOMINIO/?user=TU_USUARIO)
```

### Imagen enlazada al perfil

```html
<a href="https://github.com/TU_USUARIO">
  <img src="https://TU-DOMINIO/?user=TU_USUARIO" alt="Racha de contribuciones de TU_USUARIO" />
</a>
```

### Tema y colores personalizados

```md
![Racha de GitHub](https://TU-DOMINIO/?user=alvar3zjos3&theme=dark&hide_border=true&ring=58a6ff&fire=ff7b72)
```

### Tamaño personalizado

```md
![Racha de GitHub](https://TU-DOMINIO/?user=alvar3zjos3&card_width=600&card_height=210)
```

### Respuesta JSON

Puedes solicitar datos en JSON para comprobar los resultados o integrarlos en otra aplicación:

```text
https://TU-DOMINIO/?user=alvar3zjos3&type=json
```

## SVG estático con GitHub Actions

La tarjeta dinámica se actualiza cuando GitHub solicita la imagen a Vercel o Docker. Si prefieres mostrar un archivo local en tu README y actualizarlo solo una vez al día, usa GitHub Actions para descargar y guardar un SVG estático.

Este enfoque crea o actualiza `profile/contributions.svg` en tu repositorio de perfil. El workflow hará un commit solo cuando el SVG resultante haya cambiado.

> [!IMPORTANT]
> Crea este workflow en tu repositorio de perfil de GitHub, es decir, `TU_USUARIO/TU_USUARIO`; no en el repositorio de la API. Por ejemplo, para tu perfil debería estar en `alvar3zjos3/alvar3zjos3`.

### 1. Crea el workflow

Crea el archivo `.github/workflows/update-contributions.yml` en tu repositorio de perfil:

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

      - name: Descargar SVG desde tu instancia
        run: |
          mkdir -p profile
          curl --fail --silent --show-error \
            "https://TU-DOMINIO/?user=${{ github.repository_owner }}&theme=dark&hide_border=true" \
            --output profile/contributions.svg

      - name: Guardar cambios si existen
        run: |
          git config user.name "github-actions[bot]"
          git config user.email "41898282+github-actions[bot]@users.noreply.github.com"
          git add profile/contributions.svg
          git diff --cached --quiet || git commit -m "ci: actualizar tarjeta de contribuciones"
          git push
```

Sustituye `TU-DOMINIO` por el dominio de Vercel o del servidor Docker. Por ejemplo:

```yaml
"https://dev-readme-contributions.vercel.app/?user=${{ github.repository_owner }}&theme=dark&hide_border=true"
```

`${{ github.repository_owner }}` se reemplaza automáticamente por el propietario del repositorio. Para `alvar3zjos3/alvar3zjos3`, GitHub enviará `alvar3zjos3` como valor de `user`.

### 2. Inserta el SVG generado

Después de ejecutar el workflow una primera vez, añade esto al `README.md` de tu perfil:

```md
![Racha de contribuciones](./profile/contributions.svg)
```

El horario `15 2 * * *` intenta ejecutar la actualización cada día a las 02:15 UTC. También puedes abrir la pestaña **Actions**, seleccionar el workflow y pulsar **Run workflow** para iniciarlo manualmente.

> [!TIP]
> Si configuraste `WHITELIST`, incluye al propietario del repositorio de perfil. Si no lo haces, la descarga puede devolver un error en lugar de un SVG.

## Opciones de personalización

El parámetro `user` es obligatorio; todos los demás son opcionales. Si usas `theme` y además proporcionas un color individual, el color individual sobrescribe el valor del tema.

| Parámetro | Descripción | Ejemplo |
|---|---|---|
| `user` | Usuario de GitHub que se consultará | `alvar3zjos3` |
| `theme` | Tema visual de la tarjeta | `default`, `dark`, `radical` |
| `hide_border` | Oculta el borde | `true` o `false` |
| `border_radius` | Redondez de las esquinas | `0`, `4.5`, `20` |
| `background` | Fondo: hex sin `#`, color CSS o degradado | `0d1117`, `black`, `45,0d1117,161b22` |
| `border` | Color del borde | `30363d` |
| `stroke` | Color de las líneas entre secciones | `58a6ff` |
| `ring` | Color del anillo de la racha actual | `58a6ff` |
| `fire` | Color del fuego | `ff7b72` |
| `currStreakNum` | Color del número de racha actual | `58a6ff` |
| `sideNums` | Color de las cifras laterales | `c9d1d9` |
| `currStreakLabel` | Color de la etiqueta central | `8b949e` |
| `sideLabels` | Color de las etiquetas laterales | `8b949e` |
| `dates` | Color del intervalo de fechas | `8b949e` |
| `excludeDaysLabel` | Color de la etiqueta de días excluidos | `8b949e` |
| `date_format` | Patrón personalizado de fecha | `d/m[/Y]` |
| `locale` | Idioma alternativo y formato regional | `en`, `fr`, `pt_BR` |
| `timezone` | Zona horaria usada para determinar el día actual | `Europe/Madrid` |
| `short_numbers` | Abrevia cifras como `1.5k` | `true` o `false` |
| `type` | Formato de salida | `svg`, `png` o `json` |
| `mode` | Tipo de racha | `daily` o `weekly` |
| `exclude_days` | Días excluidos de la racha | `Sun,Sat` |
| `disable_animations` | Desactiva las animaciones SVG | `true` o `false` |
| `card_width` | Ancho de tarjeta en píxeles | `495` |
| `card_height` | Alto de tarjeta en píxeles | `195` |
| `hide_total_contributions` | Oculta las contribuciones totales | `true` o `false` |
| `hide_current_streak` | Oculta la racha actual | `true` o `false` |
| `hide_longest_streak` | Oculta la racha más larga | `true` o `false` |
| `starting_year` | Año inicial que se contabiliza | `2017` |

### Ejemplos adicionales

Racha semanal que excluye sábado y domingo:

```text
https://TU-DOMINIO/?user=alvar3zjos3&mode=weekly&exclude_days=Sat,Sun
```

Tarjeta compacta que no muestra las contribuciones totales:

```text
https://TU-DOMINIO/?user=alvar3zjos3&hide_total_contributions=true&card_width=360
```

Tarjeta sin animaciones:

```text
https://TU-DOMINIO/?user=alvar3zjos3&disable_animations=true
```

## Cómo se calculan las rachas

La aplicación utiliza el grafo de contribuciones de GitHub para identificar los días con actividad.

- Una contribución puede proceder de eventos que GitHub contabiliza, como commits, issues y pull requests que cumplan sus criterios de visibilidad.
- La **racha actual** agrupa los días consecutivos con al menos una contribución. Si ya contribuiste hoy, termina hoy; si no has contribuido todavía, termina ayer para no mostrar una racha de cero antes de que acabe el día.
- La **racha más larga** es el tramo histórico más largo de días consecutivos con actividad.
- En `mode=daily`, necesitas al menos una contribución por cada día no excluido.
- En `mode=weekly`, una contribución dentro de cada semana permite continuar la racha.
- `exclude_days` evita que los días indicados aumenten o interrumpan la racha.

GitHub puede tardar hasta 24 horas en reflejar contribuciones nuevas en el grafo. Para incluir contribuciones privadas, activa la visualización de contribuciones privadas en tu perfil y utiliza una configuración de token adecuada para los datos que necesites consultar.

## Seguridad y variables

La tarjeta utiliza un token de GitHub para consultar la API GraphQL de forma fiable. Trátalo como una contraseña: no debe aparecer en el repositorio, URLs, capturas, logs públicos ni mensajes.

| Elemento | ¿Se sube al repositorio? | Uso correcto |
|---|:---:|---|
| `.env.example` | Sí | Plantilla pública sin secretos. |
| `.env` | No | Configuración privada para desarrollo local o Docker Compose. |
| `TOKEN` en Vercel | No | Variable protegida desde el panel de Vercel. |
| `TOKEN` en Docker | No | Variable entregada al iniciar el contenedor o gestionada como secreto. |
| `WHITELIST` | Opcional | Limita qué perfiles pueden usar la instancia. |
| `GITHUB_TOKEN` de Actions | No | Token temporal disponible solo durante el workflow. |

Asegúrate de que `.gitignore` incluye estas reglas:

```gitignore
.env
.env.*
!.env.example
DOCKER_ENV
```

No subas ni compartas valores que comiencen por `ghs_`, `ghp_` o `github_pat_`. GitHub puede bloquear un push si detecta secretos en los archivos actuales o en commits antiguos.

## Desarrollo y contribución

La configuración local, requisitos de PHP/Composer para Windows y Linux, pruebas PHPUnit, formato con Prettier, convenciones de commits y proceso de pull request se documentan en [CONTRIBUTING.md](./CONTRIBUTING.md).

Si quieres informar de un error, proponer una opción o colaborar con código, abre un [issue](https://github.com/alvar3zjos3/dev-readme-contributions/issues) o envía un pull request siguiendo esa guía.

## Créditos y licencia

`dev-readme-contributions` adapta la base de [DenverCoder1/github-readme-streak-stats](https://github.com/DenverCoder1/github-readme-streak-stats) para integrarla con el ecosistema de [dev-readme-stats](https://github.com/alvar3zjos3/dev-readme-stats).

Consulta [LICENSE](./LICENSE) para conocer las condiciones aplicables de uso, modificación y distribución.

---

Hecho con PHP, GitHub, Vercel y Docker.