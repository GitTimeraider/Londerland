#!/bin/sh
# Organizr with plain "docker run" (the same setup as docker-compose.yml).
#
# Start:   sh docker-run.sh
# Update:  docker pull ghcr.io/gittimeraider/organizr:latest, then docker rm -f organizr, then run this script again
# Logs:    docker logs -f organizr
#
# Everything Organizr needs is inside the image; the container downloads nothing when it starts.

docker run -d \
  --name organizr \
  -p 80:80 \
  -e TZ=Etc/UTC \
  -v "$(pwd)/organizr-data:/var/www/html/data" \
  --restart unless-stopped \
  ghcr.io/gittimeraider/organizr:latest
