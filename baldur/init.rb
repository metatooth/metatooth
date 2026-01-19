# frozen_string_literal: true

require 'rubygems'

require 'aws/s3'
require 'haml'
require 'logger'
require 'mini_magick'
require 'rom'
require 'rom-sql'
require 'sass'
require 'sinatra'

# Import Types for use in relation schemas
Types = ROM::SQL::Types

Dir.glob('./baldur/**/*.rb').sort.each do |f|
  require f
end

if ENV['AWS_ACCESS_KEY_ID'] && ENV['AWS_SECRET_ACCESS_KEY']
  AWS::S3::Base.establish_connection!(
    access_key_id: ENV['AWS_ACCESS_KEY_ID'],
    secret_access_key: ENV['AWS_SECRET_ACCESS_KEY']
  )
end

database_url = ENV['DATABASE_URL'] || 'sqlite::memory:'
configuration = ROM::Configuration.new(:sql, database_url)
configuration.register_relation(Assets, Series, Settings)

MAIN_CONTAINER = ROM.container(configuration)
