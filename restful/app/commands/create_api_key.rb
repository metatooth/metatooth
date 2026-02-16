# frozen_string_literal: true

# Command to create an API Key
class CreateApiKey < ROM::Commands::Create[:sql]
  relation :api_keys
  register_as :create
  result :one

  use :timestamps
  timestamp :created_at, :updated_at
end
