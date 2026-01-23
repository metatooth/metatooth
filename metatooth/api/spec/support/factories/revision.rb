# frozen_string_literal: true

Factory.define(:revision) do |f|
  f.locator { SecureRandom.hex(2) }
  f.sequence(:number) { |n| n }
  f.location { 'example.org' }
  f.timestamps
  f.association(:plan)
end
