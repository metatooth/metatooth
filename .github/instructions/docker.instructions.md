---
applyTo: "**/docker-compose.yml"
---

# Docker Compose Review Instructions

## Service Naming
- Service names must be unique across the monorepo
- growherbert uses: `wordpress-gh`, `nginx-gh`
- lynngrown uses: `wordpress-lg`, `nginx-lg`

## Image Versions
- Pin specific image versions, avoid `latest` tag
- Current versions: `wordpress:6.5.5-php8.3-fpm-alpine`, `nginx:1.27.0-alpine`

## Volume Configuration
- Use named volumes for persistent data
- Volume names should include site identifier (e.g., `gh-wordpress-data`, `lg-wordpress-data`)

## Network Configuration
- WordPress FPM exposes port 9000 internally
- Nginx container exposes to host: port 3030 (growherbert), port 3300 (lynngrown)

## Environment Variables
- Database credentials come from `.env` file
- Required vars: `WORDPRESS_DB_HOST`, `WORDPRESS_DB_USER`, `WORDPRESS_DB_PASSWORD`, `WORDPRESS_DB_NAME`
