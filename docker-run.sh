#!/bin/sh
# Londerland with plain "docker run" (the same setup as docker-compose.yml).
#
# Start:   sh docker-run.sh
# Update:  docker pull ghcr.io/gittimeraider/londerland:latest, then docker rm -f londerland, then run this script again
# Logs:    docker logs -f londerland
#
# Everything Londerland needs is inside the image; the container downloads nothing when it starts.

# The container runs as user 99, group 100. The data folder must belong to that user.
# Remove the --user line (and this chown) to run as root instead; then also add
#   --cap-add=CHOWN --cap-add=DAC_READ_SEARCH --cap-add=SETUID --cap-add=SETGID
# Optional: only these IPs and subnets may log in (comma separated); everyone else sees "Login not allowed".
# Add to the command below, for example:
#   -e LONDERLAND_LOGIN_ALLOWED_IPS=192.168.1.0/24,10.8.0.0/24 \
# Behind a reverse proxy, also the proxy's IP or subnet so the visitor's real IP is used:
#   -e LONDERLAND_TRUSTED_PROXIES=172.18.0.0/16 \
mkdir -p londerland-data
[ "$(stat -c %u:%g londerland-data)" = "99:100" ] || sudo chown -R 99:100 londerland-data

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
