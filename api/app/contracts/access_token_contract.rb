# frozen_string_literal: true

# Validation of an acccess token
class AccessTokenContract < Dry::Validation::Contract
  register_macro(:token_digest) do
    key.failure('must_be_a_valid_digest_token') if value.is_a?(String) &&
                                                   value.length == 256
  end

  params do
    required(:token_digest).filled
    required(:user_id).filled(:int?)
    required(:api_key_id).filled(:int?)
  end

  rule(:token_digest).validate(:token_digest)
end
