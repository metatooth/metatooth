# frozen_string_literal: true

Factory.define(:revision) do |f|
  f.locator { SecureRandom.hex(2) }
  f.sequence(:version) { |n| n }
  f.sequence(:number) { |n| n }
  f.location { Faker::Address.full_address }
  f.association(:plan)
end
