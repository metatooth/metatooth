# frozen_string_literal: true

require_relative '../spec_helper'

# Specification for the OrderItem model.
describe OrderItem, type: :model do
  context 'associations' do
    it { should belong_to(:order) }
    it { should belong_to(:product) }
  end

  context 'validations' do
    it { should validate_presence_of(:quantity) }

    context 'uniqueness' do
      before { create(:order_item) }

      it { should validate_uniqueness_of(:locator) }
    end
  end
end
