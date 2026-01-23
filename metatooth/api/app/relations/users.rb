# frozen_string_literal: true

class Users < ROM::Relation[:sql]
  schema(:users) do
    attribute :id, Types::Serial
    attribute :locator, (Types::String.default { SecureRandom.hex(2) })
    attribute :type, (Types::String.default { 'User' })
    attribute :email, Types::String
    attribute :password_digest, Types::String
    attribute :name, Types::String
    attribute :last_logged_in_at, Types::DateTime
    attribute :confirmation_token, (Types::String.default { SecureRandom.hex })
    attribute :confirmation_redirect_url, Types::String
    attribute :confirmed_at, Types::DateTime
    attribute :confirmation_sent_at, Types::DateTime
    attribute :reset_password_token, Types::String
    attribute :reset_password_redirect_url, Types::String
    attribute :reset_password_sent_at, Types::DateTime
    attribute :failed_attempts, Types::Integer
    attribute :created_at, (Types::DateTime.default { DateTime.now })
    attribute :updated_at, (Types::DateTime.default { DateTime.now })
    attribute :deleted, (Types::Bool.default { false })
    attribute :deleted_at, Types::DateTime

    primary_key :id

    associations do
      has_many :access_tokens
      has_many :addresses
      has_many :orders
    end
  end

  auto_struct(true)
end
