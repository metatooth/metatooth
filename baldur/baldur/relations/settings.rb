# frozen_string_literal: true

# Name-value pairs to configure things.
class Settings < ROM::Relation[:sql]
  schema do
    attribute :id, Types::Integer
    attribute :name, Types::String
    attribute :value, Types::String

    primary_key :id
  end
end
