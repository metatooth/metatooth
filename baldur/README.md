# Baldur

An image gallery using Sinatra.rb + ROM
Powered by puma

## Getting Started

```
$ git clone https://github.com/terryg/baldur.git
$ cd baldur
$ bundle install --path vendor/bundle
$ bundle exec foreman run puma
```

### Environment Variables

When using foreman, the following entries are needed in .env

AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
DATABASE_URL
RACK_SECRET
S3_BUCKET_NAME
