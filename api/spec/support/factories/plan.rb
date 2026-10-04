# frozen_string_literal: true

Factory.define(:plan) do |f|
  f.locator { SecureRandom.hex(2) }
  f.sequence(:name) { |n| "name #{n}" }
  f.timestamps
end
