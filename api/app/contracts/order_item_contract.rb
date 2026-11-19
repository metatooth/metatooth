# frozen_string_literal: true

# Validation of an order item
class OrderItemContract < Dry::Validation::Contract
  params do
    required(:quantity).filled(:int?)
  end
end
