# frozen_string_literal: true

require_relative 'init'

require 'rom/sql/rake_task'
require 'securerandom'

namespace :db do
  task :setup do
    configuration = ROM::Configuration.new(:sql, ENV['DATABASE_URL'])
    ROM::SQL::RakeSupport.env = configuration
  end
end

# tasks useful to administrators
namespace :admin do
  # creates an API KEY for clients to use
  task :generate_key do
    api_key_repo = ApiKeyRepo.new(MAIN_CONTAINER)
    key = api_key_repo.generate
    puts "#{key[:id]}:#{key[:api_key]}"
  end
end
