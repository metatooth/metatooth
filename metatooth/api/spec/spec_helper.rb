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
    DatabaseCleaner.clean_with(:truncation)
  end

  config.around(:each) do |example|
    DatabaseCleaner.cleaning do
      example.run
    end
  end
end
