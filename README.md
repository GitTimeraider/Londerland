![OrganizrHeader](https://github.com/causefx/Organizr/raw/v2-develop/plugins/images/organizr/logo-wide.png)

[![Percentage of issues still open](http://isitmaintained.com/badge/open/causefx/Organizr.svg)](http://isitmaintained.com/project/causefx/Organizr "Percentage of issues still open")
[![Average time to resolve an issue](http://isitmaintained.com/badge/resolution/causefx/Organizr.svg)](http://isitmaintained.com/project/causefx/Organizr "Average time to resolve an issue")
[![GitHub stars](https://img.shields.io/github/stars/causefx/Organizr.svg)](https://github.com/causefx/Organizr/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/causefx/Organizr.svg)](https://github.com/causefx/Organizr/network)
[![Docker pulls](https://img.shields.io/docker/pulls/organizr/organizr.svg)](https://hub.docker.com/r/organizr/organizr)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://paypal.me/causefx)
[![Beerpay](https://beerpay.io/causefx/Organizr/badge.svg?style=beer-square)](https://beerpay.io/causefx/Organizr)
[![Beerpay](https://beerpay.io/causefx/Organizr/make-wish.svg?style=flat-square)](https://beerpay.io/causefx/Organizr?focus=wish)

![OrganizrAbout](https://user-images.githubusercontent.com/16184466/53614282-a91e9e00-3b96-11e9-9b3e-d249775ecaa1.png)

Do you have quite a bit of services running on your computer or server? Do you have a lot of bookmarks or have to memorize a bunch of ip's and ports? Well, Organizr is here to help with that. Organizr allows you to setup "Tabs" that will be loaded all in one webpage. You can then work on your server with ease. Want to give users access to some Tabs? No problem, just enable user support and have them make an account. Want guests to be able to visit too? Enable Guest support for those tabs.

![OrganizrInfo](https://user-images.githubusercontent.com/16184466/53614285-a9b73480-3b96-11e9-835e-9fadd045582b.png)

- PHP 7.2+
- [Official Site](https://organizr.app) - Will be refreshed soon!
- [Official Discord](https://organizr.app/discord)

- [See Wiki](https://docs.organizr.app/) - Will be updated soon!
- [Docker](https://hub.docker.com/r/organizr/organizr)

![OrganizrGallery](https://user-images.githubusercontent.com/16184466/53614284-a9b73480-3b96-11e9-9bea-d7a30b294267.png)

<img src="https://user-images.githubusercontent.com/16184466/53615855-35cc5a80-3b9d-11e9-882b-f09f3eb18173.png" width="23%"></img>
<img src="https://user-images.githubusercontent.com/16184466/53615856-35cc5a80-3b9d-11e9-8428-1f2ae05da2c9.png" width="23%"></img>
<img src="https://user-images.githubusercontent.com/16184466/53615857-35cc5a80-3b9d-11e9-82bf-91987c529e72.png" width="23%"></img>
<img src="https://user-images.githubusercontent.com/16184466/53615858-35cc5a80-3b9d-11e9-8149-01a7fcd9160a.png" width="23%"></img>

[![OrganizrOverview](https://img.youtube.com/vi/LZL4smFB6wU/0.jpg)](https://www.youtube.com/watch?v=LZL4smFB6wU)

![OrganizrFeat](https://user-images.githubusercontent.com/16184466/53614283-a9b73480-3b96-11e9-90ef-6e752e067884.png)

- 'Forgot Password' support [receive an email with your new password, prerequisites: mail server setup]
- Additional language support
- Custom tabs for your services
- Customise the top bar by adding your own site logo or site name
- Enable or disable iFrame for your tabs
- Fail2ban support ([see wiki](https://docs.organizr.app/features/fail2ban-integration))
- Fullscreen Support
- Gravatar Support
- Keyboard shortcut support (Check help tab in settings)
- Login with Plex/Emby/LDAP or sFTP credentials
- Mobile support
- Multiple login support
- Nginx Auth_Request support ([see wiki](https://docs.organizr.app/features/server-authentication))
- Organizr login log viewer
- Personalise any theme: Customise the look and feel of Organizr with access to the colour palette
- Pin/Unpin sidebar
- Protect new user account creation with registration password
- Quick access tabs (access your tabs quickly e.g. www.example.com/#Sonarr)
- Set default page on launch
- Theme-able
- Unlimited User Groups
- Upload new icons with ease
- User management support: Create, delete and promote users from the user management console
- Many more...

![OrganizrFeatReq](https://user-images.githubusercontent.com/16184466/53614286-a9b73480-3b96-11e9-8495-4944b85b1313.png)

[![Feature Requests]](https://vote.organizr.app/)

### Self-contained image (ghcr.io)

This repository builds its own image with `.github/workflows/docker.yml` and publishes it to `ghcr.io/gittimeraider/organizr`.
Everything (PHP extensions, Composer dependencies, fonts, cron) is baked into the image at build time, so the container does not download anything when it starts.
Frontend libraries (Bootstrap 5, jQuery 4, Font Awesome 7 and the rest) are installed from `package.json` with npm during the image build and served from `assets/vendor`.
Running from a git checkout without Docker? Run `npm ci && npm run build` in the repository folder first; it creates `assets/vendor` and the minified CSS/JS.

Two ready-to-use examples are in the repository root:

- [`docker-compose.yml`](docker-compose.yml) for Docker Compose
- [`docker-run.sh`](docker-run.sh) for plain `docker run`

**Docker Compose** (save as `docker-compose.yml` in an empty folder, then run `docker compose up -d` in that folder):

```yaml
services:
  organizr:
    image: ghcr.io/gittimeraider/organizr:latest
    container_name: organizr
    ports:
      - "80:80"
    environment:
      - TZ=Etc/UTC
    volumes:
      - ./organizr-data:/var/www/html/data
    restart: unless-stopped
```

**docker run** (run in the folder where the `organizr-data` folder should be created):

```bash
docker run -d \
  --name organizr \
  -p 80:80 \
  -e TZ=Etc/UTC \
  -v "$(pwd)/organizr-data:/var/www/html/data" \
  --restart unless-stopped \
  ghcr.io/gittimeraider/organizr:latest
```

Then open `http://<your-server-ip>` in a browser to start the setup wizard.

In the setup wizard, set the database location to a folder inside the volume, for example `/var/www/html/data/db/`.

![OrganizrDocker](https://user-images.githubusercontent.com/16184466/53667702-fcdcc600-3c2e-11e9-8828-860e531e8096.png)

[![Repository](https://img.shields.io/github/stars/organizr/docker-organizr?color=402885&style=for-the-badge&logo=github&logoColor=41add3&)](https://github.com/Organizr/docker-organizr)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/organizr/docker-organizr/Build%20Container?color=402885&style=for-the-badge&logo=github&logoColor=41add3)](https://github.com/organizr/docker-organizr/actions?query=workflow%3A%22Build+Container%22)
[![Docker Pulls](https://img.shields.io/docker/pulls/organizr/organizr?color=402885&style=for-the-badge&logo=docker&logoColor=41add3)](https://hub.docker.com/r/organizr/organizr/)

##### Settings

| Setting | Example | What it does |
|---|---|---|
| `-p` / `ports` | `80:80` | `<port on your machine>:<port in the container>`. Use `8080:80` to reach Organizr on port 8080 instead. |
| `-e TZ` / `environment` | `TZ=Europe/Amsterdam` | Timezone for logs, the calendar and scheduled jobs ([list of names](https://en.wikipedia.org/wiki/List_of_tz_database_time_zones)). Defaults to `UTC`. |
| `-v` / `volumes` | `./organizr-data:/var/www/html/data` | Where your settings, database, logs and uploaded images are kept. Keep this folder to keep your setup across updates. |

The image also has a built-in health check, so `docker ps` shows whether Organizr is `healthy`.

##### Updating

- Docker Compose: `docker compose pull && docker compose up -d`
- docker run: `docker pull ghcr.io/gittimeraider/organizr:latest`, then `docker rm -f organizr`, then run the `docker run` command again. Your data stays in the `organizr-data` folder.

##### Info

- Shell access whilst the container is running: `docker exec -it organizr /bin/bash`
- To monitor the logs of the container in realtime: `docker logs -f organizr`

![OrganizrSponsor](https://user-images.githubusercontent.com/16184466/53614287-a9b73480-3b96-11e9-9c8e-e32b4ae20c0d.png)

### AI Chat

Organizr has a built-in AI chat for logged in users. It works with any server that speaks the OpenAI API (`/v1/chat/completions`): OpenAI, Anthropic's OpenAI-compatible endpoint, Ollama, LM Studio, LiteLLM, OpenRouter, vLLM, LocalAI and others.

**Setting it up (as admin):**

1. In Organizr, open **Settings > Plugins > Inactive** and enable **AI Chat**.
2. Open **Settings > Plugins > Active**, click the settings icon of **AI Chat** and fill in:
   - **Connection:** the API Base URL up to and including `/v1` (for example `https://api.openai.com/v1`, `https://api.anthropic.com/v1/` or `http://ollama:11434/v1`) and the API key (leave it empty for servers without one).
   - **Models:** the default model, and optionally which models users may pick (`*` works as a wildcard) or extra model IDs the server does not list.
   - **Chat:** the minimum group that gets the chat (guests never do), a system prompt, temperature, answer length and how many earlier messages are sent along.
   - **Uploads:** whether users may add images and files, and the size limit.
3. Click **Save**, then **Test (save first)** to check the connection and see the available models.

**Using it:** logged in users get a chat button in the bottom left corner of every Organizr page. The chat offers:

- Answers that appear while they are written, with a stop button.
- Markdown with highlighted code blocks.
- A model picker per chat, and a star to make the current model your default.
- Image uploads for vision models, plus text, code and PDF files, which are sent as text. You can attach files with the paperclip button, by dragging them into the chat, or by pasting.
- Chat history with search, pin, rename, export (Markdown or JSON), delete and delete all.
- Editing a question, answering again, and copying.
- Personal instructions sent with every chat.
- A "Thinking" section for reasoning models.
- A full-screen layout on phones.

The API key stays on the Organizr server and is never sent to browsers. Chats and uploads are stored per user (uploads in `data/aichat`, readable only through Organizr's API).

### Seedboxes.cc 

[![Seedboxes.cc](https://user-images.githubusercontent.com/16184466/154811062-201be154-6868-4a24-ade6-a26278935415.png)](https://www.seedboxes.cc)

### BrowserStack for allowing us to use their platform for testing

[![BrowserStack](https://avatars2.githubusercontent.com/u/1119453?s=200&v=4g)](https://www.browserstack.com)

### This project is supported by

<img src="https://opensource.nyc3.cdn.digitaloceanspaces.com/attribution/assets/SVG/DO_Logo_horizontal_blue.svg" width="200px"></img>

## Contributors

<!-- ALL-CONTRIBUTORS-LIST:START - Do not remove or modify this section -->
<!-- prettier-ignore-start -->
<!-- markdownlint-disable -->
<table>
  <tbody>
    <tr>
      <td align="center" valign="top" width="14.28%"><a href="https://tronflix.app"><img src="https://avatars.githubusercontent.com/u/22502007?v=4?s=100" width="100px;" alt="Chris Yocum"/><br /><sub><b>Chris Yocum</b></sub></a><br /><a href="#test-tronyx" title="Tests">⚠️</a></td>
      <td align="center" valign="top" width="14.28%"><a href="http://roxedus.dev"><img src="https://avatars.githubusercontent.com/u/7110194?v=4?s=100" width="100px;" alt="Roxedus"/><br /><sub><b>Roxedus</b></sub></a><br /><a href="#test-Roxedus" title="Tests">⚠️</a></td>
      <td align="center" valign="top" width="14.28%"><a href="https://github.com/HalianElf"><img src="https://avatars.githubusercontent.com/u/28244771?v=4?s=100" width="100px;" alt="HalianElf"/><br /><sub><b>HalianElf</b></sub></a><br /><a href="#test-HalianElf" title="Tests">⚠️</a></td>
    </tr>
  </tbody>
</table>

<!-- markdownlint-restore -->
<!-- prettier-ignore-end -->

<!-- ALL-CONTRIBUTORS-LIST:END --
