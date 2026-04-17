# Slim4MVC Docker Rules

## Multi-Stage Dockerfile
The project uses a multi-stage Dockerfile at `docker/Dockerfile` (or `Dockerfile` in root).

### Stages
1. **builder** — installs Composer dependencies without dev packages.
2. **production** — runtime stage with PHP-FPM 8.4, opcache, and Redis extension.
3. **development** — extends production with Xdebug and dev Composer dependencies.

### Base Image
- `php:8.4-fpm-bookworm`

### Required PHP Extensions
- `pdo_mysql`
- `mbstring`
- `gd` (with jpeg and freetype)
- `zip`
- `intl`
- `opcache`
- `pcntl`
- `bcmath`
- `exif`
- `redis` (via pecl)

### Services (docker-compose.yml)
- **php**: PHP-FPM container, mounts codebase at `/var/www/html`.
- **nginx**: Nginx Alpine, proxies to PHP-FPM, serves static assets.
- **db**: MariaDB 11 with healthcheck.
- **redis**: Redis 7 Alpine with healthcheck.

### Rules
- Always add healthchecks for db and redis services.
- Use named volumes for `db_data` and `redis_data`.
- PHP container must own `storage/` directory (`www-data:www-data`) with `775` permissions.
- Copy optimized `opcache.ini` and custom `php.ini` from `docker/` directory.
- Use Composer 2.8 for deterministic builds.
