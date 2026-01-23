# frozen_string_literal: true

# Conveniences for access tokens
class AccessTokenRepo < ROM::Repository[:access_tokens]
  commands :create, update: :by_pk

  def active_access_tokens_with_user
    access_tokens.where(deleted: false).combine(:user)
  end

  def by_id(id)
    access_tokens.by_pk(id).one!
  end

  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end

  def generate(id)
    token = SecureRandom.hex
    update(id, token_digest: BCrypt::Password.create(token))
    token
  end

  def query(conditions)
    access_tokens
      .map_to(AccessToken)
      .where(deleted: false)
      .where(conditions)
      .to_a
  end
end
