# frozen_string_literal: true

Factory.define(:address) do |f|
  f.sequence(:name) { |n| "name #{n}" }
  f.organization { 'Metatooth LLC' }
  f.address1 { '30 Forest Ave' }
  f.city { 'Swampscott' }
  f.state { 'MA' }
  f.zip5 { '01907' }
  f.zip4 { '2321' }
  f.timestamps
  f.association(:user)
end
