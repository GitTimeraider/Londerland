# Contributing to Londerland

Thanks for helping to make Londerland better.

## Reporting a problem

Open an issue on GitHub and include the Londerland version (Settings > System Settings > About), how you run it
(Docker image or your own web server) and the steps to see the problem. Error messages from the browser console
or from `docker logs londerland` help a lot.

## Working on the code

You need Git, Docker, Node.js and (for running without Docker) PHP 8.5 with Composer.

1. Fork the repository on GitHub and clone your fork.
2. In a terminal in the repository folder, install the dependencies:

   ```bash
   composer install --working-dir=api
   npm ci && npm run build
   ```

3. Build and start a local container from your checkout:

   ```bash
   docker build -t londerland:dev .
   docker run --rm -p 8080:80 -v "$(pwd)/dev-data:/var/www/html/data" londerland:dev
   ```

   Then open `http://localhost:8080`.

## Pull requests

- One feature or fix per pull request.
- Keep the style of the surrounding code (tabs in PHP, Prettier formatting in `js/functions.js`).
- Check your changes before opening the pull request: `php -l` on changed PHP files, `node --check` on changed JavaScript files,
  and a quick test in the browser.
- Describe what you changed and how you tested it.

By contributing you agree that your work is published under the [GNU General Public License v3.0](LICENSE).
