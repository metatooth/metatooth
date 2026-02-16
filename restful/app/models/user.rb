# frozen_string_literal: true

# A User model.
class User
  attr_reader :attributes

  def initialize(attributes)
    @attributes = attributes
  end

  def [](name)
    attributes[name]
  end

  def admin?
    false
  end

  def authenticate(password)
    return false if attributes[:password_digest].nil?

    BCrypt::Password.new(attributes[:password_digest]).is_password?(password)
  end

  def password=(new_password)
    if new_password.nil?
      attributes[:password_digest] = nil
    elsif !new_password.empty?
      attributes[:password_digest] = BCrypt::Password.create(new_password)
    end
  end

  def user_manager?
    false
  end
end

# UserManager role can CRUD user records
class UserManager < User
  def user_manager?
    true
  end
end

# Admin role can CRUD all records
class Admin < UserManager
  def admin?
    true
  end
end
