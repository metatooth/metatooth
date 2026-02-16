# frozen_string_literal: true

Factory.define(:access_token) do |f|
  f.token { SecureRandom.hex(32) }
  f.association(:user)
  f.association(:api_key)
end
