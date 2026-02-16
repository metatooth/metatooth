# frozen_string_literal: true

Factory.define(:order_item) do |f|
  f.quantity 1
  f.price { 99.99 }
end
