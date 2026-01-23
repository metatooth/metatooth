# frozen_string_literal: true

# Conveniences for the address repository
class AddressRepo < ROM::Repository[:addresses]
  commands :create, update: :by_pk

  def by_id(id)
    addresses.by_pk(id).one!
  end

  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end
end
