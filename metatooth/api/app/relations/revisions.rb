# frozen_string_literal: true

class Revisions < ROM::Relation[:sql]
  schema(:revisions, infer: true) do
    attribute :locator, (Types::String.default { SecureRandom.hex(2) })
    attribute :created_at, (Types::DateTime.default { DateTime.now })
    attribute :updated_at, (Types::DateTime.default { DateTime.now })
    attribute :deleted, (Types::Bool.default { false })

    associations do
      belongs_to :plan
    end
  end
end
