# frozen_string_literal: true

# A struct for assets
class Assets < ROM::Relation[:sql]
  include SoftDeletable

  schema(infer: true) do
    attribute :locator, (Types::String.default { SecureRandom.hex(2) })
  end
end
