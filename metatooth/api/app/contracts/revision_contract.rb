# frozen_string_literal: true

# Validation of a revision
class RevisionContract < Dry::Validation::Contract
  params do
    required(:plan_id).filled(:int?)
    required(:number).filled(:int?)
    required(:location).filled(:str?)
  end
end
