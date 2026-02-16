# frozen_string_literal: true

# Access Token allows User access to API
class AccessToken
  def initialize(attributes = {})
    @attributes = attributes
  end

  def [](name)
    # Handle ROM::Struct by using method call
    if @attributes.respond_to?(name)
      @attributes.public_send(name)
    else
      @attributes[name.to_sym] || @attributes[name.to_s]
    end
  end

  def []=(name, value)
    @attributes[name.to_sym] = value
  end

  def authenticate(unencrypted_token)
    token = self[:token]
    return false unless token
    Rack::Utils.secure_compare(token.to_s, unencrypted_token)
  end

  def expired?
    created_at = self[:created_at]
    return false unless created_at
    (created_at + 14 * 24 * 60 * 60) < Time.now
  end

  def generate_token
    token = SecureRandom.hex(32)
    self[:token] = token
    token
  end
end
