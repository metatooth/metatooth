# frozen_string_literal: true

Factory.define(:product) do |f|
  f.sequence(:name) { |n| "name #{n}" }
  f.timestamps
end
