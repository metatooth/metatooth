# frozen_string_literal: true

# A series is a collection of assets.
class Series < ROM::Relation[:sql]
  schema do
    attribute :id, Types::Integer
    attribute :name, Types::String
    attribute :description, Types::String

    primary_key :id
  end
end
