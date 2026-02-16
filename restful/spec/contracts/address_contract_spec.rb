# frozen_string_literal: true

require_relative '../spec_helper'

# Specification for the validation of an Address model.
describe AddressContract, type: :dry_validation do
  context 'validations' do
    it { is_expected.to validate(:name, :required).filled }
    it { is_expected.to validate(:address1, :required).filled }
    it { is_expected.to validate(:city, :required).filled }

    it { is_expected.to validate(:state, :required).macro_use?(:state) }
    it { is_expected.to validate(:zip5, :optional).macro_use?(zip: 5) }
    it { is_expected.to validate(:zip4, :optional).macro_use?(zip: 4) }

    it { is_expected.to validate(:user_id, :required).filled(:int) }
  end
end
