# api.metatooth.com

> A Sinatra.rb project

## Getting Started

### Prerequisites

#### Ubuntu 22.04

```
$ sudo apt-get update
$ sudo apt-get install libpq-dev libxml2-dev postgresql postgresql-server-dev-14 ruby-bundler ruby-dev
```

### get code & install dependencies

```bash
$ git clone https://github.com/metatooth/api.git
$ cd api
$ bundle config set --local path 'vendor/bundle'
$ bundle install
```

### initialize database & environment variables

#### Option 1: Docker

```bash
$ docker run -d --name metatooth-pg \
    -e POSTGRES_USER=metatooth -e POSTGRES_PASSWORD=metatooth \
    -e POSTGRES_DB=metatooth_development \
    -p 5432:5432 postgres:15
$ echo "DATABASE_URL=postgres://metatooth:metatooth@localhost/metatooth_development" > .env
```

Check that it is ready with `pg_isready -h localhost`. The database starts
empty; load the schema before starting the server:

```bash
$ docker exec -i metatooth-pg psql -U metatooth -d metatooth_development < db/setup.sql
```

#### Option 2: local PostgreSQL

```bash
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

```bash
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
