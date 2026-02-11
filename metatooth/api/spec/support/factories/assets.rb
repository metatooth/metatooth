# frozen_string_literal: true

Factory.define(:asset) do |f|
  f.locator { SecureRandom.hex(2) }
  f.sequence(:name) { |n| "logo#{n}" }
  f.sequence(:url) do |n|
    "https://metatooth-cabinet.s3.amazonaws.com/junk-drawer/logo#{n}.png"
  end
  f.asset_type { 'image/png' }
  f.mime_type { 'image/png' }
end
