# Baldur - Image Gallery Application

Baldur is a Sinatra-based Ruby web application for managing and displaying artist portfolios. It is built specifically for Lara Miranda Goodman's artwork gallery (laramirandagoodman.com).

## Technology Stack
- **Language**: Ruby 3.3
- **Framework**: Sinatra (web framework)
- **ORM**: ROM with PostgreSQL adapter
- **Templates**: HAML with SASS stylesheets
- **Server**: Puma (concurrent workers configurable via WEB_CONCURRENCY, threads via MAX_THREADS)
- **Storage**: AWS S3 for artwork images with automatic resizing
- **Image Processing**: MiniMagick
- **Port**: 4567 (default)

## Key Features
- Artist portfolio with artwork organization into series/collections
- Responsive gallery grid with lightbox modal and slideshow navigation
- Automatic image resizing to multiple dimensions (original, 500px, 300px, thumbnail)
- S3 integration for high-resolution image storage
- Price calculation based on artwork dimensions ($20 per inch of perimeter)
- Sold status and availability tracking
- Password-protected admin upload interface
- Artist CV, contact, and pricing pages
- SEO optimization with proper meta tags and alt text

## Directory Structure
```
baldur/
├── baldur/                          # Application code
│   ├── relations/                   # Database models (Assets, Series, Settings)
│   ├── routes/                      # Route handlers
│   ├── views/                       # HAML templates
│   ├── views/css/                   # SASS stylesheets
│   ├── app.rb                       # Main Sinatra application
│   └── helpers.rb                   # Helper methods (image processing, pagination, etc.)
├── config/                          # Configuration
│   ├── settings.yml                 # App settings (Redis, log levels)
│   ├── puma.rb                      # Puma server config
│   └── newrelic.yml                 # New Relic monitoring
├── Dockerfile                       # Ruby 3.3 Alpine container
├── docker-compose.yml               # PostgreSQL + Baldur services
├── Gemfile                          # Ruby dependencies
├── init.rb                          # Application initialization
├── config.ru                        # Rack configuration
└── Makefile                         # Build and deployment targets
```

## Build & Deployment

```bash
cd baldur

# Setup (initialize pre-commit hooks)
make setup

# Build Docker image
make docker

# Start Docker Compose stack
make up

# View logs
make logs

# Stop containers
make down

# Cleanup
make clean
```

## Environment Variables Required
- `AWS_ACCESS_KEY_ID` - AWS S3 credentials
- `AWS_SECRET_ACCESS_KEY` - AWS S3 credentials
- `S3_BUCKET_NAME` - S3 bucket for image storage
- `DATABASE_URL` - PostgreSQL connection string (defaults to docker-compose DB)
- `RACK_SECRET` - Session encryption key
- `UPLOAD_PASSWORD` - Password for upload endpoint
- `WEB_CONCURRENCY` - Number of Puma workers (default: 2)
- `MAX_THREADS` - Number of threads per worker (default: 5)

## Database Models
- **Assets** - Artwork records with dimensions, pricing, S3 URLs, sold status
- **Series** - Collections/groups of artwork
- **Settings** - Key-value configuration store

## Main Routes
| Route | Method | Purpose |
|-------|--------|---------|
| `/` | GET | Home page |
| `/gallery` | GET | Gallery view |
| `/paintings` | GET | Artwork catalog |
| `/paintings/:id` | GET | Individual artwork detail |
| `/pricelist` | GET | Pricing for available artwork |
| `/upload` | GET/POST | Password-protected upload interface |
| `/contact` | GET | Contact information |
| `/CV` | GET | Artist CV/resume |
| `/css/:stylesheet.css` | GET | Dynamic SASS compilation |
| `/assets/:id` | GET/POST | Asset detail and admin edit |
