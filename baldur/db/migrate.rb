#!/usr/bin/env ruby
# frozen_string_literal: true

require 'sequel'
require 'logger'

# Setup database connection
database_url = ENV['DATABASE_URL'] || 'sqlite::memory:'
db = Sequel.connect(database_url)
db.loggers << Logger.new($stdout)

# Run migrations from db/migrate directory
Sequel.extension :migration
Sequel::Migrator.run(db, './db/migrate')

puts "Migrations completed successfully!"
