#!/bin/sh
# Adjust Laravel .env for Docker MySQL (run inside app container).
set -e
cd /var/www/html
if [ ! -f .env ]; then
  cp .env.example .env
fi
sed -i.bak \
  -e 's/^DB_CONNECTION=.*/DB_CONNECTION=mysql/' \
  -e 's/^DB_HOST=.*/DB_HOST=mysql/' \
  -e 's/^DB_PORT=.*/DB_PORT=3306/' \
  -e 's/^DB_DATABASE=.*/DB_DATABASE=check_domain/' \
  -e 's/^DB_USERNAME=.*/DB_USERNAME=laravel/' \
  -e 's/^DB_PASSWORD=.*/DB_PASSWORD=laravel/' \
  .env 2>/dev/null || true
sed -i.bak2 's|^APP_URL=.*|APP_URL=http://localhost:8080|' .env 2>/dev/null || true
grep -q '^VITE_API_URL=' .env || echo 'VITE_API_URL=http://localhost:8080/api' >> .env
rm -f .env.bak .env.bak2 2>/dev/null || true
