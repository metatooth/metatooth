# frozen_string_literal: true

# Access Token allows User access to API
class AccessToken
  attr_reader :attributes

  def initialize(attributes = {})
    @attributes = attributes
  end

  def [](name)
    attributes[name]
  end

  def []=(name, value)
    attributes[name] = value
  end

  def authenticate(unencrypted_token)
    BCrypt::Password.new(attributes[:token_digest])
                    .is_password?(unencrypted_token)
  end

  def expired?
    (attributes[:created_at] + 14 * 24 * 60 * 60) < Time.now
  end

  def generate_token
    token = SecureRandom.hex
    attributes[:token_digest] = BCrypt::Password.create(token)
    token
  end
end
