---
applyTo: "**/*.conf,**/growherbert-com,**/lynngrown-com"
---

# Nginx Configuration Review Instructions

## Local Container Config (nginx.conf)
- Listen on port 80 inside container
- FastCGI pass to `wordpress:9000` (Docker service name)
- Include proper fastcgi_params
- Set `root /var/www/html`

## Balancer Site Config ({site}-com files)
- SSL termination with certificates at `/etc/letsencrypt/live/{domain}/`
- HTTP to HTTPS redirect on port 80
- Proxy to upstream on internal ports (3030 or 3300)
- Required proxy headers:
  - `X-Real-IP`
  - `X-Forwarded-For`
  - `X-Forwarded-Proto`
  - `Host`

## Port Assignments
- growherbert.com: port 3030
- lynngrown.com: port 3300
