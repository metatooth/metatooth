# frozen_string_literal: true

require 'securerandom'

class ApiKeys < ROM::Relation[:sql]
  schema do
    attribute :id, Types::Serial
    attribute :api_key, (Types::String.default { SecureRandom.hex })
    attribute :active, (Types::Bool.default { true })
    attribute :created_at, (Types::DateTime.default { DateTime.now })
    attribute :updated_at, (Types::DateTime.default { DateTime.now })

    primary_key :id

    associations do
      has_many :access_tokens
    end
  end

  auto_struct true
end
