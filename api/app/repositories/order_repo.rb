# frozen_string_literal: true

# Conveniences for the order repository.
class OrderRepo < ROM::Repository[:orders]
  commands :create, update: :by_pk

  def by_id(id)
    orders.by_pk(id).one!
  end

  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end

  def for_user(uid)
    orders.combine(:users).where(id: uid)
  end

  def query(conditions)
    orders.where(conditions).to_a
  end
end
