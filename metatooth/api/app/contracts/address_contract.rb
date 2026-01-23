# frozen_string_literal: true

# Validation of an api key
class AddressContract < Dry::Validation::Contract
  register_macro(:state) do
    key.failure('must_be_a_valid_state_code') if value.is_a?(String) &&
                                                 !value.match?(/^[A-Z][A-Z]$/)
  end

  register_macro(:zip) do |macro:|
    num = macro.args[0]
    key.failure('must_be_a_valid_zip{num}_code') if value.is_a?(String) &&
                                                    value.length == num &&
                                                    !value.match?(/^\d*$/)
  end

  params do
    required(:name).filled
    optional(:organization)
    required(:address1).filled
    optional(:address2)
    required(:city).filled
    required(:state).filled
    optional(:zip5)
    optional(:zip4)
    optional(:postcode)
    required(:user_id).filled(:int?)
  end

  rule(:state).validate(:state)
  rule(:zip5).validate(zip: 5)
  rule(:zip4).validate(zip: 4)
end
