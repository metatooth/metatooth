# frozen_string_literal: true

require_relative 'application_mailer'

# Class to handle user mail
class UserMailer < ApplicationMailer
  def self.confirmation_email(user)
    user = UserRepo.new(MAIN_CONTAINER)
                   .update(user[:id], confirmation_sent_at: Time.now)
    new(user: user).deliver(to: user[:email],
                            subject: 'Confirm your account!',
                            template: 'user_confirmation_email')
  end

  def self.reset_password(user)
    user = UserRepo.new(MAIN_CONTAINER)
                   .update(user[:id], reset_password_sent_at: Time.now)
    new(user: user).deliver(to: user[:email],
                            subject: 'Reset your password',
                            template: 'user_reset_password')
  end

  def self.new_product(user, product)
    new(user: user, product: product)
      .deliver(to: user[:email],
               subject: 'New product created',
               template: 'user_new_product')
  end
end
