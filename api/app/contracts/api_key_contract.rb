# frozen_string_literal: true

# Validation of an api key
class ApiKeyContract < Dry::Validation::Contract
  params do
    required(:api_key).filled(:string)
    required(:active).value(:bool)
  end
end
