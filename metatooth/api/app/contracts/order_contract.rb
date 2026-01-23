# frozen_string_literal: true

# Validation of an order
class OrderContract < Dry::Validation::Contract
  params do
    required(:user_id).filled(:int?)
    optional(:shipped_impression_kit_at)
  end

  rule(:shipped_impression_kit_at) do
    key.failure('must_be_a_valid_date') if value.is_a?(String) && value.empty?
  end
end
