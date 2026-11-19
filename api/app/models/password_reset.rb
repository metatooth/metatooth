# frozen_string_literal: true

# Helper object for password reset
class PasswordReset
  # How long a reset link stays usable after it is issued.
  TOKEN_LIFETIME = 2 * 60 * 60

  attr_accessor :email, :reset_password_redirect_url, :password,
                :reset_token, :updating

  def create
    (!user.nil? &&
       valid? &&
       user_repo.init_password_reset(user[:id], reset_password_redirect_url))
  end

  def errors
    errors = []
    if updating
      errors.append('password must be present') if blank?(password)
    else
      errors.append('email must be present') if blank?(email)
      errors.append('reset_password_redirect_url must be present') if blank?(reset_password_redirect_url)
    end

    errors
  end

  def initialize(params)
    return unless params

    self.email = params[:email]
    self.reset_password_redirect_url = params[:reset_password_redirect_url]
    self.reset_token = params[:reset_token]
    self.password = params[:password]
  end

  def redirect_url
    build_redirect_url
  end

  def update
    self.updating = true
    (!user.nil? &&
     valid? &&
     user_repo.complete_password_reset(user[:id], password))
  end

  def user
    @user ||= retrieve_user
  end

  def valid?
    if updating
      return false if blank?(password)
    else
      return false if blank?(email)
      return false if blank?(reset_password_redirect_url)
    end

    true
  end

  private

  def blank?(str)
    str.nil? || str.empty?
  end

  def retrieve_user
    user = if updating || reset_token
             user_with_token
           else
             user_with_email
           end

    raise StandardError unless user

    user
  end

  def user_with_email
    user_repo.by_email(email.downcase)
  rescue StandardError
    nil
  end

  # A blank token must never be looked up (`WHERE token IS NULL` would match
  # every user without a pending reset) and an old token is treated as unknown.
  def user_with_token
    return nil if blank?(reset_token)

    found = user_repo.by_token(reset_token)
    found if token_fresh?(found)
  rescue StandardError
    nil
  end

  def token_fresh?(found)
    sent_at = found[:reset_password_sent_at]
    !sent_at.nil? && (Time.now - sent_at.to_time) <= TOKEN_LIFETIME
  end

  def user_repo
    @user_repo ||= UserRepo.new(MAIN_CONTAINER)
  end

  def build_redirect_url
    url = user[:reset_password_redirect_url]
    query_params = Rack::Utils.parse_query(URI(url).query)
    if query_params.any?
      "#{url}&reset_token=#{reset_token}"
    else
      "#{url}?reset_token=#{reset_token}"
    end
  end
end
