# frozen_string_literal: true

require_relative '../spec_helper'

# Specification of the validation for an access token
describe AccessTokenContract, type: :dry_validation do
  context 'validations' do
    it { is_expected.to validate(:user_id, :required).filled(:int) }
    it { is_expected.to validate(:api_key_id, :required).filled(:int) }
  end
end
