# frozen_string_literal: true

class OrderItems < ROM::Relation[:sql]
  include SoftDeletable

  schema(infer: true) do
    associations do
      belongs_to :order
      belongs_to :product
    end
  end
end
