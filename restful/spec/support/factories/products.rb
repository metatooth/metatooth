# frozen_string_literal: true

Factory.define(:product) do |f|
  f.sequence(:name) { |n| "name #{n}" }
  f.price { 99.99 }
end
