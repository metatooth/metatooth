# frozen_string_literal: true

# Conveniences for reading API Keys
class ApiKeyRepo < ROM::Repository[:api_keys]
  def by_id(id)
    api_keys.by_pk(id).one!
  end

  def generate
    api_keys.command(:create).call({})
  end
end
