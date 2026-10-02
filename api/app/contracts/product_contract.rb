# frozen_string_literal: true

# Validation of a product
class ProductContract < Dry::Validation::Contract
  params do
    required(:name).filled(:string)
  end
end
