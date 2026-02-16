# frozen_string_literal: true

# A struct for an order
class Orders < ROM::Relation[:sql]
  schema(:orders, infer: true) do
    attribute :shipped_impression_kit_at, Types::DateTime

    associations do
      belongs_to :user
      belongs_to :address, as: :bill
      belongs_to :address, as: :ship
      has_many :order_items
    end
  end
end
