#!/bin/sh
# Для окремого сервісу Railway / іншого PaaS: черга + планувальник у одному контейнері.
set -e
php artisan queue:work database --sleep=3 --tries=2 --timeout=120 &
exec php artisan schedule:work
