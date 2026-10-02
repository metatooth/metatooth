# frozen_string_literal: true

require_relative '../models/authenticator'

# An authentication scheme
module Authentication
  AUTH_SCHEME = 'Metaspace-Token'

  private

  def access_token
    @access_token ||= authenticator.access_token
  end

  def authenticate_client
    unauthorized!('Client Realm') unless api_key
  end

  def authenticate_user
    unauthorized!('User Realm') unless access_token
  end

  def authenticator
    @authenticator ||= Authenticator.new(authorization_request)
  end

  def authorization_request
    @authorization_request ||= request.env['HTTP_AUTHORIZATION'].to_s
  end

  def api_key
    @api_key ||= authenticator.api_key
  end

  def current_user
    @current_user ||= UserRepo.new(MAIN_CONTAINER).by_id(access_token[:user_id])
  rescue StandardError
    nil
  end

  def unauthorized!(realm)
    headers['WWW-Authenticate'] = %(#{AUTH_SCHEME} realm="#{realm}")
    halt 401
  end

  def validate_auth_scheme
    return if authorization_request.match(/^#{AUTH_SCHEME} /)

    unauthorized!('Client Realm')
  end
end
