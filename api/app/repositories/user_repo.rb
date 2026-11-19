# frozen_string_literal: true

# Conveniences for the user repository.
class UserRepo < ROM::Repository[:users]
  commands :create, update: :by_pk

  def by_id(id)
    users.active.map_to(User).by_pk(id).one!
  end

  def by_email(email)
    users.active.map_to(User).where(email: email).one!
  end

  def by_token(token)
    users.active.map_to(User).where(reset_password_token: token).one!
  end

  def complete_password_reset(id, password)
    update(id,
           password_digest: BCrypt::Password.create(password),
           reset_password_token: nil,
           reset_password_sent_at: nil,
           reset_password_redirect_url: nil)
  end

  def confirm(id)
    update(id, confirmation_token: nil, confirmed_at: DateTime.now)
  end

  # Soft-deletes the user and revokes their access tokens atomically.
  def delete(id)
    now = DateTime.now
    transaction do
      access_tokens.where(user_id: id, deleted: false)
                   .command(:update).call(deleted: true, deleted_at: now)
      update(id, deleted: true, deleted_at: now)
    end
  end

  def init_password_reset(id, redirect_url)
    update(id,
           reset_password_token: SecureRandom.hex,
           reset_password_sent_at: DateTime.now,
           reset_password_redirect_url: redirect_url)
  end

  def user_with_addresses(id)
    users.active.where(id: id).combine(:addresses).one!
  end

  def query(conditions)
    users.active.where(conditions).to_a
  end
end
