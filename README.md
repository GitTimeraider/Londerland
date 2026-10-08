<p align="center"><img src="https://github.com/GitTimeraider/Assets/blob/main/Londerland/img/Londerland_1.png?raw=true alt="Londerland" width="420"></p>

**Londerland** puts all of your self-hosted web apps on one page, behind one login.
Add your services (Sonarr, Radarr, Plex, Home Assistant, a router page, anything with a web interface) as tabs,
choose who may see which tab, and open them all from a single place instead of remembering IP addresses and ports.

## Features

- **Tabs** for every web app, shown inside Londerland (iFrame) or opened in a new window.
- **Users and groups**: decide per tab which group may open it; guests can get their own tabs.
- **Homepage** with live items for Plex, Emby, Jellyfin, Sonarr, Radarr, Lidarr, SABnzbd, qBittorrent, Deluge, Transmission, Ombi, Overseerr, Tautulli, a calendar and more.
- **Sign in** with a local account, Plex, Emby/Jellyfin, LDAP, FTP or OpenID Connect (Authentik, Keycloak, PocketID, Zitadel), with optional two-factor authentication.
- **Single sign-on** for supported apps, and reverse proxy authentication (Nginx `auth_request`, Traefik/Caddy forward auth) using `api/v2/auth`.
- **AI Chat** for logged in users with any OpenAI-compatible server, including web search and image generation ([details below](#ai-chat)).
- **Plugins**: invites, e-mail (PHPMailer), health checks, speed test, chat, bookmarks and more.
- **Look and feel**: themes, colours, your own logo and title, custom CSS and JavaScript, login page and splash screen.
- **Backups** on a schedule, a log viewer, an image manager and a built-in API with documentation (`/docs`).
- Works on phones and tablets, and in many languages.

## Install with Docker

The image is built by this repository and published to the GitHub Container Registry as
`ghcr.io/gittimeraider/londerland:latest`.
Everything Londerland needs is inside the image: the container downloads nothing when it is built or when it starts.

Ready-to-use examples are in the repository root:

- [`docker-compose.yml`](docker-compose.yml) for Docker Compose
- [`docker-run.sh`](docker-run.sh) for plain `docker run`

### Docker Compose

Run these commands in a terminal on your Docker host (for example an SSH session), in an empty folder:

1. Create the data folder and give it to the user the container will run as (here user `99`, group `100`):

   ```bash
   mkdir -p londerland-data
   sudo chown -R 99:100 londerland-data
   ```

2. Save this as `docker-compose.yml` in the same folder:

   ```yaml
   services:
     londerland:
       image: ghcr.io/gittimeraider/londerland:latest
       container_name: londerland
       user: "99:100"
       security_opt:
         - no-new-privileges:true
       cap_drop:
         - ALL
       ports:
         - "80:80"
       environment:
         - TZ=Etc/UTC
       volumes:
         - ./londerland-data:/var/www/html/data
       restart: unless-stopped
   ```

3. Start it:

   ```bash
   docker compose up -d
   ```

### docker run

In a terminal on your Docker host, in the folder where the `londerland-data` folder should be:

```bash
mkdir -p londerland-data
sudo chown -R 99:100 londerland-data

docker run -d \
  --name londerland \
  --user 99:100 \
  --security-opt=no-new-privileges:true \
  --cap-drop=ALL \
  -p 80:80 \
  -e TZ=Etc/UTC \
  -v "$(pwd)/londerland-data:/var/www/html/data" \
  --restart unless-stopped \
  ghcr.io/gittimeraider/londerland:latest
```

### First start

Open `http://<your-server-ip>` in a browser. The setup wizard starts. In the database step, use a folder inside the data volume,
for example `/var/www/html/data/db/`, so the database is kept when the container is replaced.

### Settings

| Setting | Example | What it does |
|---|---|---|
| `--user` / `user:` | `99:100` | The user and group the container runs as. Everything in the data folder gets this owner. Leave it out to run as root (see below). |
| `--security-opt` / `security_opt:` | `no-new-privileges:true` | Stops processes in the container from gaining extra rights. Works with and without `--user`. |
| `--cap-drop` / `cap_drop:` | `ALL` | Removes all Linux capabilities. Works as it is with `--user`; as root, add back the four listed below. |
| `-p` / `ports:` | `8080:80` | `<port on your machine>:<port in the container>`. |
| `-e TZ` / `environment:` | `TZ=Europe/Amsterdam` | Time zone for logs, the calendar and scheduled jobs ([list of names](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones)). Defaults to `UTC`. |
| `-e LONDERLAND_PORT` | `8080` | The port Londerland listens on **inside** the container (default `80`). Only needed with `--network host`, rootless Docker or Docker older than 20.10; then also change the right side of `-p`. |
| `-v` / `volumes:` | `./londerland-data:/var/www/html/data` | Your settings, database, logs, backups and uploaded images. Keep this folder to keep your setup. |

**Running as a user (`--user`)**: the data folder on your host must belong to that user before the container starts
(`sudo chown -R 99:100 londerland-data`). If it does not, the container stops and its log (`docker logs londerland`) tells you the
exact `chown` command to run. Nothing in the container needs root in this mode.

**Running as root (no `--user`)**: at start the container gives the data folder to its own web user (`www-data`, uid 33),
and the web server and scheduled jobs run as that user.

**Capabilities (`--cap-drop=ALL`)**:

| How you run it | Capabilities to add back |
|---|---|
| With `--user` (for example `99:100`) | None. `--cap-drop=ALL` works as it is. |
| As root (no `--user`) | `--cap-add=CHOWN --cap-add=DAC_READ_SEARCH --cap-add=SETUID --cap-add=SETGID` |

As root, `CHOWN` and `DAC_READ_SEARCH` let the container give the data folder (also folders in it that belong to someone else)
to `www-data`, and `SETUID` and `SETGID` let the web server and the scheduled jobs switch to that user.
In Docker Compose, put them under `cap_add:` (one per line, without `--cap-add=`).
Port 80 needs no capability on Docker 20.10 or newer; on older Docker, set `LONDERLAND_PORT` to a port above 1023.
If a capability is missing, the container stops and `docker logs londerland` says which ones to add.

The image has a health check, so `docker ps` shows whether Londerland is `healthy`.

### Updating

Londerland is updated by replacing the container with a newer image; your data stays in the `londerland-data` folder.

- Docker Compose (in the folder with `docker-compose.yml`): `docker compose pull && docker compose up -d`
- docker run: `docker pull ghcr.io/gittimeraider/londerland:latest`, then `docker rm -f londerland`, then run the `docker run` command again.

### Useful commands

- Logs: `docker logs -f londerland`
- A shell inside the running container: `docker exec -it londerland /bin/bash`

### Moving from Organizr

Londerland is based on Organizr and can take over an existing Organizr data folder:

1. Stop the Organizr container and make a copy of its `data` folder (the folder that holds `config/config.php`).
2. Start Londerland with that copy as its data folder (see above; with `--user`, `chown` the copy first).
3. Log in again. Londerland converts the configuration once and keeps the original file as `data/config/config.before-londerland.php`.

Things that change for existing setups:

- Everybody has to log in once more, because the login cookies have new names.
- Reverse proxy authentication sends `X-Londerland-User`, `X-Londerland-Email` and `X-Londerland-Group` instead of the old `X-Organizr-*` headers, and the `organizr-auth` route is now `londerland-auth`. Update your proxy configuration if you use them.
- Plugins and themes downloaded from the Organizr marketplace are not supported. The marketplaces and the in-app updater are gone; update by pulling a new image.

## Run without Docker

You need PHP 8.5 with the `pdo_sqlite`, `sqlite3`, `curl`, `zip`, `ldap`, `mbstring` and `openssl` extensions, a web server
(Apache with `mod_rewrite`, or Nginx), Composer and Node.js.

In a terminal, in the repository folder:

```bash
composer install --no-dev --working-dir=api
npm ci && npm run build
```

`npm run build` copies the frontend libraries to `assets/vendor` and creates the minified CSS/JS.
Point the web server at the repository folder and make the `data` folder writable for the web server user.
Run `php cron.php` every minute (for example with cron) for scheduled jobs such as backups.

## AI Chat

Londerland has a built-in AI chat for logged in users. It works with any server that speaks the OpenAI API (`/v1/chat/completions`):
OpenAI, Anthropic's OpenAI-compatible endpoint, Ollama, LM Studio, LiteLLM, OpenRouter, vLLM, LocalAI and others.

**Setting it up (as admin):**

1. In Londerland, open **Settings > Plugins > Inactive** and enable **AI Chat**.
2. Open **Settings > Plugins > Active**, click the settings icon of **AI Chat** and fill in:
   - **Connection:** the API Base URL up to and including `/v1` (for example `https://api.openai.com/v1`, `https://api.anthropic.com/v1/` or `http://ollama:11434/v1`) and the API key (leave it empty for servers without one).
   - **Models:** the default model, and optionally which models users may pick (`*` works as a wildcard) or extra model IDs the server does not list.
   - **Chat:** the minimum group that gets the chat (guests never do), a system prompt, temperature, answer length and how many earlier messages are sent along.
   - **Web Search (optional):** a provider (SearXNG, Brave, Tavily or DuckDuckGo) with its address or API key. SearXNG needs `json` in `search: formats` in its `settings.yml`. Optionally let tool-calling models search by themselves.
   - **Images (optional):** turn on image generation through an OpenAI-compatible `/images/generations` endpoint (for example `https://api.openai.com/v1` with `gpt-image-1`). Leave URL and key empty to use the chat server. Claude cannot create images, so use a different server here when chatting with Claude. Optionally let tool-calling models create images by themselves.
   - **Uploads:** whether users may add images and files, and the size limit.
3. Click **Save**, then **Test (save first)** to check the connection and see the available models. **Test Search** checks the search provider.

**Using it:** logged in users get a large **AI** chat bubble in the bottom right corner of every Londerland page. The chat offers:

- Answers that appear while they are written, with a stop button.
- Markdown with highlighted code blocks.
- A model picker per chat, and a star to make the current model your default.
- Image uploads for vision models, plus text, code and PDF files, which are sent as text. Attach files with the paperclip button, by dragging them into the chat, or by pasting.
- Chat history with search, pin, rename, export (Markdown or JSON), delete and delete all.
- Editing a question, answering again, and copying.
- Personal instructions sent with every chat.
- A "Thinking" section for reasoning models.
- **Search** button (globe): the web is searched first. The answer cites its sources as clickable [1], [2] links, with a list of sources under it.
- **Image** button: your message becomes a picture, shown in the chat with a download button. "Answer again" creates a new version.
- A full-screen layout on phones.

The API key stays on the Londerland server and is never sent to browsers. Chats and uploads are stored per user
(uploads in `data/aichat`, readable only through Londerland's API).

## License

Londerland is free software under the [GNU General Public License v3.0](LICENSE).

It is a modified version of Organizr (GPL-3.0), renamed and changed in 2026:
new Docker image, frontend libraries, AI Chat, non-root container support, and removal of the marketplaces, updater and
donation features. The original copyright notices are kept as the license requires.
