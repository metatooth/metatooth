# frozen_string_literal: true

# Conveniences for the product repository.
class ProductRepo < ROM::Repository[:products]
  commands :create, update: :by_pk

  def by_id(id)
    products.by_pk(id).one!
  end

  def create(attributes)
    attrs = attributes.dup
    attrs[:locator] ||= SecureRandom.hex(8)
    products.command(:create).call(attrs)
  end

  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end

  def query(conditions)
    products.where(conditions).to_a
  end
end
