# frozen_string_literal: true

require_relative '../spec_helper'

# Contract for a Revision model.
describe RevisionContract, type: :dry_validation do
  context 'validations' do
    it { is_expected.to validate(:plan_id, :required).filled(:int) }
    it { is_expected.to validate(:number, :required).filled(:int) }
    it { is_expected.to validate(:location, :required).filled(:str) }
  end
end
