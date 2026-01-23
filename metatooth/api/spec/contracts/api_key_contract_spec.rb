# frozen_string_literal: true

require_relative '../spec_helper'

# Specification for the ApiKey model.
describe ApiKeyContract, type: :dry_validation do
  context 'validations' do
    it { is_expected.to validate(:api_key, :required).filled }
    it { is_expected.to validate(:active, :required) }
  end
end
