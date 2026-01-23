# frozen_string_literal: true

Factory.define(:user) do |f|
  f.sequence(:email) { |n| "user#{n}-#{Time.now.to_i}@example.com" }
  f.sequence(:name) { |n| "name #{n}" }
  f.type 'User'
  f.password_digest { BCrypt::Password.create('password') }
  f.timestamps

  f.trait :confirmation_redirect_url do |t|
    t.confirmation_token '123'
    t.confirmation_redirect_url 'http://google.com'
  end

  f.trait :confirmation_no_redirect_url do |t|
    t.confirmation_token '123'
    t.confirmation_redirect_url nil
  end

  f.trait :reset_password do |t|
    t.reset_password_token '123'
    t.reset_password_redirect_url 'http://example.com?some=params'
    t.reset_password_sent_at DateTime.now
  end

  f.trait :reset_password_no_params do |t|
    t.reset_password_token '123'
    t.reset_password_redirect_url 'http://example.com'
    t.reset_password_sent_at DateTime.now
  end
end
