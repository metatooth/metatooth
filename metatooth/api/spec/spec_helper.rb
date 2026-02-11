# frozen_string_literal: true

ENV['RACK_ENV'] = 'test'

require 'database_cleaner'
require 'dry-validation-matchers'
require 'pry-remote'
require 'rack/test'
require 'rom-factory'
require 'rspec'
require 'uri'

require_relative '../init'

Factory = ROM::Factory.configure do |config|
  config.rom = MAIN_CONTAINER
end

Dir["#{File.dirname(__FILE__)}/support/factories/*.rb"].sort.each do |file|
  require file
end

# RSpec Helpers
module Helpers
  def json_body
    JSON.parse(last_response.body)
  end
end

# RSpec Mixins
module RSpecMixin
  include Rack::Test::Methods
  def app
    App
  end
end

RSpec::Matchers.define(:redirect_to) do |url|
  match do |response|
    location = response.headers['Location']
    uri = URI.parse(location) if location
    response.status.to_s[0] == '3' && uri.to_s == url
  end
end

RSpec.configure do |config|
  Pony.override_options = { via: :test }

  config.include Helpers
  config.include Rack::Test::Methods
  config.include RSpecMixin

  config.before(:each) do
    header 'Accept', 'application/vnd.metaspace.v1+json'
  end

  config.before(:suite) do
    # Clear all tables at the start of the test suite
    connection = MAIN_CONTAINER.gateways[:default].connection
    %w[access_tokens addresses api_keys assets order_items orders plans products revisions users].each do |table|
      connection.run("TRUNCATE TABLE #{table} CASCADE;")
      connection.run("ALTER SEQUENCE #{table}_id_seq RESTART WITH 1;")
    end
  end

  config.around(:each) do |example|
    example.run
    # Clear all tables after each test
    connection = MAIN_CONTAINER.gateways[:default].connection
    %w[access_tokens addresses api_keys assets order_items orders plans products revisions users].each do |table|
      connection.run("TRUNCATE TABLE #{table} CASCADE;")
      connection.run("ALTER SEQUENCE #{table}_id_seq RESTART WITH 1;")
    end
  end
end
