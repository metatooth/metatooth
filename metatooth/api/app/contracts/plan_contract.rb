# frozen_string_literal: true

# Validation of a plan
class PlanContract < Dry::Validation::Contract
  params do
    required(:name).filled(:string)
  end
end
