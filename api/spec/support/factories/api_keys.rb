# frozen_string_literal: true

Factory.define(:api_key) do |f|
  f.api_key { SecureRandom.hex }
  f.timestamps
end
