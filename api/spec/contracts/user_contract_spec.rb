# frozen_string_literal: true

require_relative '../spec_helper'

# Specification for the User model.
RSpec.describe UserContract, type: :dry_validation do
  context 'validations' do
    it { is_expected.to validate(:email, :required).macro_use?(:email) }
  end
end
