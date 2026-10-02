# frozen_string_literal: true

# Conveniences for the user repository.
class UserRepo < ROM::Repository[:users]
  commands :create, update: :by_pk

  def by_id(id)
    users.map_to(User).by_pk(id).one!
  end

  def by_email(email)
    users.map_to(User).where(email: email).one!
  end

  def by_token(token)
    users.map_to(User).where(reset_password_token: token).one!
  end

  def complete_password_reset(id, password)
    update(id,
           password: password,
           reset_password_token: nil,
           reset_password_sent_at: nil,
           reset_password_redirect_url: nil)
  end

  def confirm(id)
    update(id, confirmation_token: nil, confirmed_at: DateTime.now)
  end

  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end

  def init_password_reset(id, redirect_url)
    update(id,
           reset_password_token: SecureRandom.hex,
           reset_password_sent_at: DateTime.now,
           reset_password_redirect_url: redirect_url)
  end

  def user_with_addresses(id)
    users.where(id: id).combine(:addresses).one!
  end

  def query(conditions)
    users.where(conditions).to_a
  end
end
