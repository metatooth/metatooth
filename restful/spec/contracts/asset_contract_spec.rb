# frozen_string_literal: true

require_relative '../spec_helper'

# Specify validation for an Asset model.
describe AssetContract, type: :dry_validation do
  context 'validations' do
    it { is_expected.to validate(:url, :required).filled(:str) }
  end
end
