# frozen_string_literal: true

require_relative '../spec_helper'

# Validation for a Plan model.
describe PlanContract, type: :dry_validation do
  context 'validations' do
    it { is_expected.to validate(:name, :required).filled(:str) }
  end
end
