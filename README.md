<p align="center"><img src="https://github.com/GitTimeraider/Assets/blob/main/Londerland/img/Londerland_1.png?raw=true" alt="Londerland" width="420"></p>

#### Disclaimer: 
While guided and checked, AI is responsible for half of the coding. Development is also 100% for own personal use, no promises.
__________________________________

**Londerland** is a self-hosted web portal you can put on the internet as your own website:
one address with your own name, logo and look, where visitors see public pages and members log in
for more. It works just as well as the private start page for your home network, but it is built to feel like a
website, not like a wall of server tiles.

- **Public when you want it.** Guests (visitors who are not logged in) get their own tabs and their own start page,
  so the same address serves a public site for everyone and private pages for members.
- **Your site, your style.** Set the page title and description that browsers and search engines show, your logo
  and favicon, a theme and colours, a login page with your own wallpaper, a splash screen, and your own CSS and JavaScript.
- **Pages, not just links.** Every tab shows a web page inside the site, or opens it in a new window, and each group
  can have its own start tab. Ctrl+Alt+click a website tab in the side menu to show it next to the open tab
  (split view), with its own reload, new browser tab, main view and close buttons and a divider to resize it.
- **Members and groups.** Every tab is shown only to the groups you choose.
- **AI Chat** for logged in members, with any OpenAI-compatible server.

## Features

- **Tabs** for pages and web apps, shown inside Londerland (iFrame) or opened in a new window, sorted into categories,
  with a start tab per group.
- **Visitors and members**: guest access, local accounts, e-mail (PHPMailer), groups that decide who sees what,
  login lockout after failed attempts and e-mails about new device logins.
- **Sign in** with a local account, LDAP, FTP or OpenID Connect (Authentik, Keycloak, PocketID, Zitadel),
  with optional two-factor authentication, and reverse proxy authentication (Nginx `auth_request`, Traefik/Caddy forward auth)
  using `api/v2/auth`.
- **Login only from your own networks** if you want ([details below](#login-only-from-your-own-networks)).
- **Look and feel**: themes, colours, title, description, logo, favicon, login page and splash screen, custom CSS and JavaScript.
- **Website**: an announcement bar, and search engine and link preview settings with robots.txt and sitemap.xml
  ([details below](#website-announcement-and-seo)).
- **Command palette**: press Ctrl+K (Cmd+K on a Mac) to jump to any tab, bookmark, settings page or action ([details below](#command-palette)).
- **AI Chat** with web search, reading web pages, image generation and read-only public links to a chat ([details below](#ai-chat)).
- **Achievements and My Stats**: badges, XP, levels and daily quests for using Londerland, and a page with statistics about your own account ([details below](#achievements-and-my-stats)).
- **Bookmarks**: a page with your favourite links in categories, with icons, colours, search, and import and export of browser bookmark files ([details below](#bookmarks)).
- **Easter eggs**: a few hidden surprises for visitors and members to find ([details below](#easter-eggs)).
- **Works on phones and tablets.**
- **Admin tools**: scheduled backups, a log viewer, an audit log of who changed what ([details below](#audit-log)), an image manager
  and a built-in API with documentation (`/docs`).

## Using it as a public website

1. Install it (see below) and finish the setup wizard.
2. Put it behind a reverse proxy with HTTPS (for example Nginx Proxy Manager, Caddy or Traefik) and point your domain at it.
   Do not expose the container's port to the internet without HTTPS.
3. In Londerland, open **Settings > Customize** and set the title, description, logo, favicon, theme and login page.
4. Add the pages visitors should see as tabs under **Settings > Tab Editor** and set their group to **Guest**.
   Mark one of them as the default tab: that is the page visitors land on. Everything else stays for members only.
5. Under **Settings > Customize > Website > SEO & Sharing**, fill in the public address of the site so link previews,
   the canonical link and the sitemap use it ([details below](#website-announcement-and-seo)).

Visitors who are not logged in can only use the parts of the API that the site itself needs for them: the login page and
its sign-in methods, the tabs open to guests (and, for a Bookmarks tab, its bookmarks), reverse proxy auth (`api/v2/auth`),
`robots.txt`, the sitemap and shared AI chats. Everything else under `/api/v2/` (status, plugin list, settings and so on)
answers `401 Not Authorized` unless you are logged in or send the API key. The API documentation (`/docs`) is for admins only.

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
| `-e TZ` / `environment:` | `TZ=Europe/Amsterdam` | Time zone for logs and scheduled jobs ([list of names](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones)). Defaults to `UTC`. |
| `-e LONDERLAND_PORT` | `8080` | The port Londerland listens on **inside** the container (default `80`). Only needed with `--network host`, rootless Docker or Docker older than 20.10; then also change the right side of `-p`. |
| `-e LONDERLAND_LOGIN_ALLOWED_IPS` | `192.168.1.0/24,10.8.0.0/24` | Only these IPs and subnets (IPv4 or IPv6, comma separated) may log in. See [Login only from your own networks](#login-only-from-your-own-networks). Not set = login from anywhere. |
| `-e LONDERLAND_TRUSTED_PROXIES` | `172.18.0.0/16` | Your reverse proxy's IP or subnet. Only from these addresses is the `X-Forwarded-For` header believed. |
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

### Login only from your own networks

Set `LONDERLAND_LOGIN_ALLOWED_IPS` to make logging in possible only from your home network, a VPN or a few fixed addresses:

```yaml
    environment:
      - LONDERLAND_LOGIN_ALLOWED_IPS=192.168.1.0/24,10.8.0.0/24,203.0.113.7
```

Visitors from any other address see a **Login not allowed** page (with the address Londerland sees for them) instead of the
login form. Every way of logging in is refused for them: username and password, OpenID Connect,
reverse proxy header login, registering, password recovery and the setup wizard. Pages and tabs meant for guests stay visible,
so the public part of your site keeps working. People who are already logged in stay logged in until their login expires.
Each refused attempt is written to Londerland's log.

**Behind a reverse proxy** (Nginx Proxy Manager, Traefik, Caddy, Cloudflare Tunnel, ...), every visitor connects through the proxy,
so Londerland only sees the proxy's address. Also set `LONDERLAND_TRUSTED_PROXIES` to the proxy's IP or Docker subnet:

```yaml
      - LONDERLAND_TRUSTED_PROXIES=172.18.0.0/16
```

Londerland then uses the visitor's address from the `X-Forwarded-For` header, but only for requests that come from those
proxies, so nobody can get in by sending that header themselves. Find the proxy's address with
`docker network inspect <network name>` (on the Docker host), or open the login page from outside: the blocked page shows the
address Londerland sees. If that is your proxy's address, `LONDERLAND_TRUSTED_PROXIES` is missing or wrong.
Do not put the proxy's address in `LONDERLAND_LOGIN_ALLOWED_IPS`, as that would allow everyone who comes through the proxy.

After changing these variables, recreate the container: `docker compose up -d` (Compose) or `docker rm -f londerland` and the
`docker run` command again.

### OIDC is down while Auto-Redirect to OIDC is on

With **Auto-Redirect to OIDC** turned on, the login page sends everyone straight to your OIDC provider (Authentik, Keycloak, ...).
If that provider is down, nobody can reach the username/password form that way. People who are already logged in stay logged in.
Two ways back in:

1. **Add `#noredirect` to the address**, for example `https://your-site/#noredirect`. The login page then shows the normal
   username/password form. This works best when guests have no tabs: otherwise Londerland opens the first guest tab and
   replaces `#noredirect` in the address, so open the login right away or use option 2.
2. **Turn the redirect off on the server** (always works). On the Docker host, run:

   ```bash
   docker exec londerland sed -i "s/'oidcAutoRedirect' => true/'oidcAutoRedirect' => false/" /var/www/html/data/config/config.php
   ```

   Reload the login page; no restart is needed. When OIDC works again, turn it back on under
   **Settings > System Settings > SSO > OIDC Global > Auto-Redirect to OIDC**.

### Lost authenticator (2FA bypass code)

If a user with two-factor authentication (Google Authenticator) loses their authenticator, they log in with their username and
password and click **Get a one-time code from your admin** under the 2FA field. Londerland then writes a code to the
container log, never to the page:

```bash
docker logs londerland 2>&1 | grep "2FA bypass"
```

The user enters that code (like `ABCD-EFGH`) in the 2FA field. It works once, for 15 minutes, and does not turn 2FA off;
the user can do that afterwards in **Account Settings**. A new code can be requested once a minute.

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
- Logging in with Plex, Emby or Jellyfin, Plex and Jellyfin single sign-on, and importing users from them are not available.
  If Organizr used one of them as its login backend, Londerland switches to its own passwords (their settings and saved Plex
  tokens are removed). Users who only ever logged in that way need a Londerland password: an admin sets one under
  **Settings > User Management > Manage Users**, or, when e-mail (PHPMailer) is set up, they use **Forgot pwd?** on the login page.
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

`/robots.txt` and `/sitemap.xml` are made by Londerland. With Apache, add these lines inside the `<Directory>` block of
the Londerland folder in the site's configuration (the Docker image already has them); with Nginx, add the `rewrite`
line to the `server` block:

```apache
RewriteEngine On
RewriteRule ^(robots\.txt|sitemap\.xml)$ api/v2/index.php [L]
```

```nginx
rewrite ^/(robots\.txt|sitemap\.xml)$ /api/v2/index.php last;
```

## Website: announcement and SEO

In Londerland, open **Settings > Customize > Website**. It has two parts; click **Save** after a change.

**Announcement** puts a coloured bar with a message under the top bar.

- **Show the announcement** switches it on. **Visitors can close it**: a closed bar stays closed in that browser until you change the message.
- **Message**: plain text. `**text**` makes text bold, `[text](https://address)` makes a link, and addresses starting with `https://` become links by themselves.
- **Colour**, **Shown to** (empty = everyone, guests included), and optional **Show from** / **Show until** times in the container's time zone (`TZ`).
- Without an announcement to show, nothing extra is sent to the browser, so it costs no loading time.

**SEO & Sharing** sets what search engines and link previews (chat apps, social media) see.

- **Public address of the site** (for example `https://example.com/`): used for the canonical link, `og:url` and the sitemap.
- **Search engines**: allow the site in search results, or keep it out (robots meta tag, `X-Robots-Tag` header and robots.txt).
- **Author**, **Link preview image** (1200 x 630 pixels works everywhere), preview size and an X / Twitter account.
- Verification codes for **Google Search Console** and **Bing Webmaster Tools** (only the code from `content="..."`).
- **Structured data (JSON-LD)** and **sitemap.xml** (served at `/sitemap.xml`).
- **Own robots.txt**: leave empty for a generated one, served at `/robots.txt`.

## Command palette

Press **Ctrl+K** (**Cmd+K** on a Mac) anywhere in Londerland, or click the magnifying glass in the top bar, and type:

- your **tabs** (Enter opens it, **Shift+Enter** opens it in a new browser tab),
- **bookmarks** of your group (when the Bookmarks plugin is on),
- **actions**: reload or close the open tab, full screen, account settings, My Stats, lock screen, log out,
- **settings pages** (admins), for example "website", "users" or "audit".

Use the arrow keys and Enter, or click. What you picked recently is at the top. The palette is only downloaded the first
time you open it, so it adds nothing to the normal loading time. Inside a tab that shows another website, click Londerland's
top bar first: keys pressed inside another site stay in that site.

## Easter eggs

Londerland hides a few surprises. Try **↑ ↑ ↓ ↓ ← → ← → B A** on your keyboard (not while typing in a field), click
the logo ten times, or open the browser console (F12) for a hint. Some words typed anywhere on the page and some phrases
in the command palette (Ctrl+K, which also works on phones and tablets) do something too. The rest is up to you to find.

- With the **Achievements** plugin on, every egg found is a secret badge, and finding eight of them gives one more.
- To switch the effects off, open **Settings > Customize > Appearance > Options**, turn off **Show Easter Eggs** and click
  **Save**. The badges can still be earned then.
- The eggs are only downloaded the first time one is found, so they add nothing to the normal loading time.
  Visitors whose device asks for less motion do not get the spinning effect.

## Audit Log

In Londerland, open **Settings > System Settings > Audit Log** (admins only; co-admins do not see it). It lists who did
what and when, with their IP address:

- sign-ins, failed sign-ins, wrong two-factor codes, lock-outs and sign-outs,
- changed settings (only the names of the settings, never their values, which can be passwords or API keys),
- added, changed, sorted and deleted tabs, categories, users and groups, locked and unlocked users, passwords reset by e-mail,
- plugins switched on or off, uploaded and deleted images, created and deleted backups, imported bookmarks, reset achievements
  and chat links stopped by an admin.

Search and filter by type at the top, page through older entries, **Export CSV** to download them, or **Clear** the log.
Choose how many days entries are kept (365 by default, 0 = forever) or switch the log off.

## AI Chat

Londerland has a built-in AI chat for logged in users. It works with any server that speaks the OpenAI API (`/v1/chat/completions`):
OpenAI, Anthropic's OpenAI-compatible endpoint, Ollama, LM Studio, LiteLLM, OpenRouter, vLLM, LocalAI and others.

**Setting it up (as admin):**

1. In Londerland, open **Settings > Plugins > Inactive** and enable **AI Chat**.
2. Open **Settings > Plugins > Active**, click the settings icon of **AI Chat** and fill in:
   - **Groups:** switch AI Chat on or off per group (guests never get it), and optionally limit the models a group may use
     (comma separated, `*` works as a wildcard; empty = every allowed model).
   - **Connection:** the API Base URL up to and including `/v1` (for example `https://api.openai.com/v1`, `https://api.anthropic.com/v1/` or `http://ollama:11434/v1`) and the API key (leave it empty for servers without one).
   - **Models:** the default model, and optionally which models users may pick (`*` works as a wildcard) or extra model IDs the server does not list.
   - **Chat:** a system prompt, temperature, answer length, how many earlier messages are sent along (and whether the dropped ones are summarized), and whether chats get a name automatically.
     **Delete Chats After** removes chats nobody wrote in for that many days (0 = never). Users can mark a chat as **Keep** to save it permanently.
     The check runs every hour through `cron.php` (the Docker image runs it by itself) and whenever a user opens the chat list.
   - **Web Search (optional):** a provider (SearXNG, Brave, Tavily or DuckDuckGo) with its address or API key. SearXNG needs `json` in `search: formats` in its `settings.yml`. Optionally let tool-calling models search and read pages by themselves; **Tool Steps per Answer** (default 6) is how often they may do that before they have to answer.
   - **Images (optional):** turn on image generation through an OpenAI-compatible `/images/generations` endpoint (for example `https://api.openai.com/v1` with `gpt-image-1`). Leave URL and key empty to use the chat server. Claude cannot create images, so use a different server here when chatting with Claude. Optionally let tool-calling models create images by themselves.
   - **Uploads:** whether users may add images and files, the size limit, and how large and how many earlier images are sent along.
   - **Sharing (optional, off by default):** **Allow public links** lets users make a read-only link to one of their chats,
     and **Groups that may share** limits who may (empty = every group with AI Chat). **Shared chats** lists every link
     with its views, and **Stop** turns one off.
   - **Chat Button:** a short name under "AI" on the chat button and its colour.
   - **Usage Limits (optional, [OmniRoute](https://github.com/diegosouzapw/OmniRoute) only):** show the used percentage of each OmniRoute connection (for example the 5 hour and weekly limits of Claude Code) to admins or to everyone. In the OmniRoute dashboard, under **API Manager**, open the API key Londerland uses and turn on **Own Cost and Token Usage** and **Shared Account Quota** (under Self-Service Visibility). A key with the `manage` scope also works and shows the connection names. Fill in the OmniRoute address only if the API Base URL is not OmniRoute (for example `http://omniroute:20128`), and `claude` under Providers to Show to list only Claude connections.
3. Click **Save**, then **Test (save first)** to check the connection and see the available models. **Test Search** checks the search provider.

**Using it:** logged in users get a large **AI** chat bubble in the bottom right corner of every Londerland page. The chat offers:

- Answers that appear while they are written, with a stop button.
- Markdown with highlighted code blocks.
- A model picker per chat, and a star to make the current model your default.
- Image uploads for vision models, plus text, code and PDF files, which are sent as text. Attach files with the paperclip button, by dragging them into the chat, or by pasting.
- Chat history with search, pin, rename, export (Markdown or JSON), delete and delete all.
- Folders in the chat history: create, rename and delete them (their chats stay), and move chats in with drag and drop or **Move to folder**.
- **Keep permanently** per chat when the admin set a retention period. A chat moved into a folder is kept automatically.
- Editing a question, answering again, and copying. When the AI fails or gives an empty answer, the chat asks once more by itself; if that fails too, the error stays in the chat with an **Answer again** button, and the unanswered question is not sent along with your next one.
- Personal instructions sent with every chat.
- Usage limits of the OmniRoute connections under **Settings**, with a bar per limit and when it resets (if the admin turned this on).
- A "Thinking" section for reasoning models.
- **Search** button (globe): the web is searched first. The answer cites its sources as clickable [1], [2] links, with a list of sources under it.
- **Image** button: your message becomes a picture, shown in the chat with a download button. "Answer again" creates a new version.
- A full-screen layout on phones (also when held sideways). On tablets the chat window replaces the chat bubble and moves above the on-screen keyboard.
- **Share** (in the chat's ⋮ menu, when the admin allows it): makes a link anyone can open without logging in. Choose how long
  it works (1 day to 1 year, or until you stop it), then copy it. The link shows the questions and answers as they are
  when you make it: no attached files or pictures, no custom instructions, no reasoning and not your name. **Update with
  new messages** makes a new link with the chat as it is now (the old link stops), and **Stop sharing** turns it off.
  Deleting the chat also stops the link. Search engines are asked not to index shared chats.

The API key stays on the Londerland server and is never sent to browsers. Chats and uploads are stored per user
(uploads in `data/aichat`, readable only through Londerland's API).

## Achievements and My Stats

Logged in users collect badges by using Londerland, and see statistics about their own account.

**Setting it up (as admin):** in Londerland, open **Settings > Plugins > Inactive** and enable **Achievements**.
Then open **Settings > Plugins > Active** and click its settings icon to choose:

- **Options:** which groups collect badges (guests have no account, so they never do), **XP, levels and daily quests**
  (on by default), whether a message pops up when a badge is unlocked, a quest is done or a level is reached, whether
  My Stats shows a leaderboard (top 10 by XP, or by badges while XP is off), and badges to switch off.
- **Users:** the level, XP, badges, active days and opened tabs of each user, and a button that resets everything.

**Using it:** open the user menu (your name, top right) and click **My Stats**. The page shows:

- Account: account age, sign-in method (standard or OpenID Connect, with or without two-factor), signed-in devices, previous visit.
- Activity: visits, active days, current and best streak (days in a row), tabs opened, different tabs, AI chats and messages (when AI Chat is enabled).
- Charts of when you use Londerland (hour of the day, day of the week) and your most opened tabs.
- Your **level** and title (from Intern and Sysadmin up to Root and Uptime Legend) with a bar showing the XP to the next level.
- **Daily quests**: three small tasks that change every day at midnight (your own time), for example "Open 3 different
  tabs", "Use split view once" or "Open the command palette twice". Each gives XP, and finishing all three gives a bonus.
- All badges with their progress and XP, for example Uptime 99.9% (30 active days), Port Scanner (5 different tabs),
  Night Shift (a visit after midnight), Thank Your Sysadmin (SysAdmin Day), Perfect Day (all quests in one day) and a few secret ones.

XP comes from badges (50, more for secret and long-term ones), from the first visit of every day (10, plus 2 for every
day of your streak, up to 30) and from daily quests. Accounts that had badges before XP existed start with the XP of
their badges and active days.

The browser only reports small events (a visit, a tab that was opened); counting and unlocking happens on the server, per user.

## Bookmarks

A page with your favourite links, sorted into categories, with an icon and colours per link and a search box.

**Setting it up (as admin):** in Londerland, open **Settings > Plugins > Inactive** and enable **Bookmarks**.
Then open **Settings > Plugins > Active**, click its settings icon and click **Set up**: this creates the **Bookmarks** tab
and a first category. Reload Londerland (F5) to see the tab. Under **Options** you choose whether links open in a new
browser tab, whether there is a search box, and the layout (tiles, a list with the addresses, or small tiles).

**Adding and changing bookmarks (as admin):** open the **Bookmarks** tab and click **Edit** at the top. Then:

- **+ Category** adds a category; the pencil next to a category renames it or makes it the default category.
- **+** next to a category adds a bookmark: name, address, category, the group that sees it (and all higher groups),
  an icon (search Londerland's icons or images, or type an image address), colours, and on/off.
- Click a bookmark to change or delete it, and drag bookmarks (also to another category) or categories to sort them.
- Click **Done** when you are finished.

**Importing bookmarks from your browser (as admin):** first export them from the browser, which gives an `.html` file:
Chrome and Edge: Bookmarks manager (Ctrl+Shift+O) > ⋮ > **Export bookmarks**; Firefox: Bookmarks > Manage bookmarks
(Ctrl+Shift+O) > Import and Backup > **Export Bookmarks to HTML**; Safari: File > **Export Bookmarks**. Then open the
**Bookmarks** tab, click **Edit** and **Import**, choose the file and:

- **Visible for**: the group that sees the imported bookmarks (and all higher groups).
- **Categories**: one category per browser folder (folders with the same name as an existing category go into it), or all in one category.
- **Skip links that are already a bookmark** (on by default). `javascript:` and `data:` links are always skipped.

**Exporting:** the **Export** button downloads the bookmarks you can see as an `.html` file (admins get all of them, one
folder per category) that every browser can import.

**Using it:** everyone who may open the Bookmarks tab sees the bookmarks of their group. Type in the search box to
filter (press `/` to jump there, Enter opens the first result), and click a category name to fold it away.

Bookmarks from the earlier Bookmark plugin are kept: it uses the same database tables and the same tab address.

## License

Londerland is free software under the [GNU General Public License v3.0](LICENSE).

It is a modified version of Organizr (GPL-3.0), renamed and changed in 2026:
new Docker image, frontend libraries, AI Chat, non-root container support, and removal of the marketplaces, updater and
donation features. The original copyright notices are kept as the license requires.
