# frozen_string_literal: true

# A struct for a product
class Products < ROM::Relation[:sql]
  schema(:products, infer: true)
end
