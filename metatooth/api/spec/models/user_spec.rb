# frozen_string_literal: true

require_relative '../spec_helper'

# Specification for the User model.
RSpec.describe User do
  let(:user_struct) { Factory[:user] }
  let(:user) { User.new(user_struct.to_h) }

  context 'type' do
    it 'should not be an admin or user manager' do
      expect(user.admin?).to eq false
      expect(user.user_manager?).to eq false
    end
  end

  context 'password' do
    it 'should set a password digest' do
      user.password = 'secret'
      expect(user[:password_digest]).to_not be_nil
    end

    it 'should authenticate successfully' do
      user.password = 'secret'
      expect(user.authenticate('secret')).to eq true
    end
  end
end
