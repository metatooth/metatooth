# api.metatooth.com

> A RESTful API for managing dental order data using Sinatra, Ruby, and ROM

## Features

- Complete CRUD endpoints for dental order management
- Input validation with Dry::Validation
- Database abstraction with ROM (Ruby Object Mapper)
- PostgreSQL backend with proper migrations
- Comprehensive test suite (80%+ coverage)
- API key and token-based authentication
- CORS support
- Swagger/OpenAPI documentation

## Data Models

The API manages the following core entities:

- **users**: User accounts with authentication and confirmation workflows
- **orders**: Dental order records with status tracking and timestamps
- **order_items**: Line items within orders with quantity and pricing
- **products**: Available products/services for ordering
- **addresses**: Billing and shipping addresses
- **plans**: Treatment or subscription plans
- **revisions**: Versioning for plans
- **assets**: Files and media storage
- **api_keys**: API authentication keys
- **access_tokens**: Bearer tokens for API access

## API Endpoints

### Orders
- `GET /orders` - List all orders for current user
- `POST /orders` - Create a new order
- `GET /orders/:id` - Get a specific order
- `PUT /orders/:id` - Update an order
- `DELETE /orders/:id` - Delete (soft delete) an order

### Order Items
- `GET /orders/:order_id/items` - List items in an order
- `POST /orders/:order_id/items` - Add an item to an order
- `GET /orders/:order_id/items/:id` - Get a specific order item
- `PUT /orders/:order_id/items/:id` - Update an order item
- `DELETE /orders/:order_id/items/:id` - Remove an item from an order

### Products
- `GET /products` - List all products
- `POST /products` - Create a new product
- `GET /products/:id` - Get a specific product
- `PUT /products/:id` - Update a product
- `DELETE /products/:id` - Delete a product

### Users
- `POST /users` - Register a new user
- `GET /users` - List all users (admin only)
- `GET /users/:id` - Get a specific user
- `PUT /users/:id` - Update a user
- `DELETE /users/:id` - Delete a user

### Additional Endpoints
- `GET /plans` - List treatment plans
- `POST /plans` - Create a plan
- `PUT /orders/:order_id` - Update order status
- `GET /addresses` - List saved addresses
- `POST /addresses` - Save an address

## Authentication

All endpoints (except `/users` for registration) require authentication via:

```
Authorization: Metaspace-Token api_key=<id>:<key>, access_token=<user_id>:<token>
```

## Getting Started

### Prerequisites

#### Ubuntu 22.04

```
$ sudo apt-get update
$ sudo apt-get install libpq-dev libxml2-dev postgresql postgresql-server-dev-14 ruby-bundler ruby-dev
```

### get code & install dependencies

``` bash
$ git clone https://github.com/metatooth/api.git
$ cd api
$ bundle config set --local path 'vendor/bundle'
$ bundle install
```

### initialize database & environment variables

``` bash
$ sudo -u postgres psql
postgres=# create database metaspace_development;
CREATE DATABASE
postgres=# create user metaspace with password 'metaspace';
CREATE ROLE
postgres=# grant all privileges on database metaspace_development to metaspace;
GRANT
postgres=# \q
$ echo "DATABASE_URL=postgres://metaspace:metaspace@localhost/metaspace_development" > .env
```

### serve with hot reload at localhost:9393
```
bundle exec foreman run shotgun
```

### Pull a copy from Heroku

``` bash
$ sudo -u postgres psql
postgres=# alter user metaspace createdb;
ALTER ROLE
postgres=# drop database metaspace_development;
DROP DATABASE
postgres=# \q
$ heroku pg:pull SOURCE postgres://metaspace@localhost/metaspace_development
```

## License

Copyright 2020 Metatooth LLC

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
