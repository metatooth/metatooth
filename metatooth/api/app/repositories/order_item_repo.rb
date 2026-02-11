# frozen_string_literal: true

# Conveniences for order item repository.
class OrderItemRepo < ROM::Repository[:order_items]
  commands :create, update: :by_pk

  def by_id(id)
    order_items.by_pk(id).one!
  end

  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end

  def query(conditions)
    order_items.where(deleted: false).where(conditions).to_a
  end
end
