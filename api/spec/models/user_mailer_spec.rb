# frozen_string_literal: true

require_relative '../spec_helper'
require_relative '../../app/models/user_mailer'

RSpec.describe UserMailer, type: :mailer do
  describe '#confirmation_email' do
    Pony.override_options = { via: :test }

    let(:user) { Factory[:user] }
    let(:mail) { UserMailer.confirmation_email(user) }

    it 'renders the headers' do
      expect(mail.subject).to eq('Confirm your account!')
      expect(mail.to).to eq([user.email])
      expect(mail.from).to eq(['noreply@metatooth.com'])
    end

    it 'renders the body' do
      expect(mail.body.encoded).to match('Hello')
    end

    it 'links to the confirmation page on the public API origin' do
      mail = UserMailer.confirmation_email(user)
      token = UserRepo.new(MAIN_CONTAINER).by_id(user.id)[:confirmation_token]
      expect(token).to_not be_nil
      expect(mail.body.encoded).to include(
        "https://api.metatooth.com/user_confirmations/#{token}"
      )
    end
  end

  describe '#reset_password' do
    let(:user) { Factory[:user] }
    let(:mail) { UserMailer.reset_password(user) }

    it 'renders the headers' do
      expect(mail.subject).to eq('Reset your password')
      expect(mail.to).to eq([user.email])
      expect(mail.from).to eq(['noreply@metatooth.com'])
    end

    it 'renders the body' do
      expect(mail.body.encoded).to match('Hello')
    end

    it 'links to the reset page on the public API origin' do
      resettable = Factory[:user, :reset_password]
      mail = UserMailer.reset_password(resettable)
      expect(mail.body.encoded).to match(
        %r{^https://api\.metatooth\.com/password_resets/123\r?$}
      )
    end
  end

  describe '#new_product_email' do
    let(:user) { Factory[:user] }
    let(:product) { Factory[:product] }

    let(:mail) { UserMailer.new_product(user, product) }

    it 'renders the headers' do
      expect(mail.subject).to eq('New product created')
      expect(mail.to).to eq([user.email])
      expect(mail.from).to eq(['noreply@metatooth.com'])
    end

    it 'renders the body' do
      expect(mail.body.encoded).to match('A new product has been created')
    end
  end

  describe 'configuration' do
    around do |example|
      saved = ENV.to_h.slice('MAIL_FROM', 'PUBLIC_API_URL')
      example.run
    ensure
      %w[MAIL_FROM PUBLIC_API_URL].each { |k| ENV.delete(k) }
      ENV.update(saved)
    end

    let(:user) { Factory[:user] }

    it 'uses MAIL_FROM and PUBLIC_API_URL when set' do
      ENV['MAIL_FROM'] = 'hello@example.org'
      ENV['PUBLIC_API_URL'] = 'http://localhost:9393/'
      mail = UserMailer.confirmation_email(user)
      expect(mail.from).to eq(['hello@example.org'])
      expect(mail.body.encoded).to include('http://localhost:9393/user_confirmations/')
    end
  end

  describe 'concurrent deliveries' do
    it 'never mixes one recipient\'s data into another\'s message' do
      users = Array.new(8) { Factory[:user] }
      Mail::TestMailer.deliveries.clear

      users.map do |u|
        Thread.new { 5.times { UserMailer.confirmation_email(u) } }
      end.each(&:join)

      expect(Mail::TestMailer.deliveries.size).to eq 40
      names = users.to_h { |u| [u.email, u.name] }
      Mail::TestMailer.deliveries.each do |m|
        expect(m.body.encoded).to include("Hello #{names[m.to.first]},")
      end
    end
  end
end
