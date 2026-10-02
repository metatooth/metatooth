# frozen_string_literal: true

Factory.define(:access_token) do |f|
  f.token_digest { nil }
  f.accessed_at { DateTime.now }
  f.timestamps
  f.association(:user)
  f.association(:api_key)
end
