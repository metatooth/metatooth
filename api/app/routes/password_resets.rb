# frozen_string_literal: true

require_relative '../models/user_mailer'
require_relative '../models/password_reset'

# Controller for password resets
class App
  get '/password_resets/:token' do
    reset = PasswordReset.new(reset_token: params[:token])
    redirect(reset.redirect_url, 303)
  end

  post '/password_resets' do
    if reset.create
      UserMailer.reset_password(reset.user.attributes)
      halt(204, location: reset.user)
    else
      unprocessable_entity!(reset)
    end
  end

  put '/password_resets/:token' do
    reset.reset_token = params[:token]
    if reset.update
      halt(204)
    else
      unprocessable_entity!(reset)
    end
  end

  private

  def reset
    @reset ||= PasswordReset.new(reset_params)
  end

  def reset_params
    params[:data]&.slice(:email, :reset_password_redirect_url, :password)
  end
end
