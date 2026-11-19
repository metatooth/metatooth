# frozen_string_literal: true

# Helper class Authenticator for parsing header keys & tokens
class Authenticator
  def initialize(authorization)
    @authorization = authorization
  end

  def api_key
    return nil unless credentials_have_api_key?

    id, key = credentials['api_key'].split(':')

    return nil unless key

    api_key_repo = ApiKeyRepo.new(MAIN_CONTAINER)
    api_key = api_key_repo.by_id(id)

    return nil unless api_key[:active] == true

    return api_key if secure_compare_with_hashing(api_key[:api_key], key)
  rescue StandardError
    nil
  end

  def access_token
    return nil unless credentials_have_access_token?

    id, token = credentials['access_token'].split(':')
    return nil unless token

    user = UserRepo.new(MAIN_CONTAINER).by_id(id)

    return nil unless user && api_key

    access_token = access_token_repo
                   .query(user_id: user[:id], api_key_id: api_key[:id]).first

    check_access_token(access_token, token)
  rescue StandardError
    nil
  end

  private

  def access_token_repo
    @access_token_repo ||= AccessTokenRepo.new(MAIN_CONTAINER)
  end

  def check_access_token(access_token, token)
    return nil unless access_token

    return nil if access_token.expired? && access_token_repo.delete(access_token[:id])

    return access_token if access_token.authenticate(token)
  end

  def credentials
    @credentials ||= Hash[@authorization.scan(/(\w+)[:=] ?"?([\w|:]+)"?/)]
  end

  def credentials_have_api_key?
    !credentials['api_key'].nil? && !credentials['api_key'].empty?
  end

  def credentials_have_access_token?
    !credentials['access_token'].nil? && !credentials['access_token'].empty?
  end

  def secure_compare_with_hashing(aaa, bbb)
    Rack::Utils.secure_compare(Digest::SHA1.hexdigest(aaa),
                               Digest::SHA1.hexdigest(bbb))
  end
end
