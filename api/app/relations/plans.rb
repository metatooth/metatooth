# frozen_string_literal: true

class Plans < ROM::Relation[:sql]
  schema(:plans, infer: true) do
    attribute :locator, (Types::String.default { SecureRandom.hex(2) })
    attribute :created_at, (Types::DateTime.default { DateTime.now })
    attribute :updated_at, (Types::DateTime.default { DateTime.now })
    attribute :deleted, (Types::Bool.default { false })

    associations do
      has_many :revisions
    end
  end
end
