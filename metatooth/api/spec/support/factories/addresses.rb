# frozen_string_literal: true

Factory.define(:address) do |f|
  f.sequence(:name) { |n| "name #{n}" }
  f.address1 { '30 Forest Ave' }
  f.city { 'Swampscott' }
  f.state { 'MA' }
  f.zip_code { '01907' }
  f.country { 'USA' }
  f.association(:user)
end
