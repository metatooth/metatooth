# frozen_string_literal: true

# Validations for a user
class UserContract < Dry::Validation::Contract
  register_macro(:email) do
    key.failure('must_be_a_valid_email') if value.is_a?(String) &&
                                            !value.match?(/\A[\w+\-.]+@[a-z\d\-]+(\.[a-z\d\-]+)*\.[a-z]+\z/i)
  end

  params do
    required(:email).filled(:string)
    required(:password_digest).filled(:string)
  end

  rule(:email).validate(:email)
end
