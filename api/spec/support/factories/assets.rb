# frozen_string_literal: true

Factory.define(:asset) do |f|
  f.locator { SecureRandom.hex(2) }
  f.sequence(:url) do |n|
    "https://metatooth-cabinet.s3.amazonaws.com/junk-drawer/logo#{n}.png"
  end
  f.mime_type { 'image/png' }
  f.created_at { DateTime.now }
end
