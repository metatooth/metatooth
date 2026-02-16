# frozen_string_literal: true

##
# OrderContract validates Order parameters using Dry::Validation.
#
# This contract ensures that all required fields are present and properly typed,
# and performs custom validation on optional fields.
#
# @example Validate order parameters
#   contract = OrderContract.new
#   result = contract.call(user_id: 123)
#   errors = result.errors(full: true)
#
class OrderContract < Dry::Validation::Contract
  params do
    ##
    # The user ID must be present and be an integer.
    required(:user_id).filled(:int?)

    ##
    # When provided, the shipped_impression_kit_at timestamp must be a valid date.
    optional(:shipped_impression_kit_at)
  end

  ##
  # Validate that shipped_impression_kit_at is not an empty string.
  #
  # @return [void]
  #
  rule(:shipped_impression_kit_at) do
    key.failure('must_be_a_valid_date') if value.is_a?(String) && value.empty?
  end
end
