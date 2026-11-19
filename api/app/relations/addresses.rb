# frozen_string_literal: true

class Addresses < ROM::Relation[:sql]
  include SoftDeletable

  schema(infer: true) do
    associations do
      belongs_to :user
    end
  end
end
