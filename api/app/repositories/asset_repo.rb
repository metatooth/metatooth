# frozen_string_literal: true

# Conveniences for assets
class AssetRepo < ROM::Repository[:assets]
  commands :create, update: :by_pk

  def by_created_at(from, to)
    assets.active.where { (created_at > from) & (created_at < to) }.call
  end

  def by_id(id)
    assets.active.by_pk(id).one!
  end

  def by_locator(locator)
    assets.active.where(locator: locator).one!
  end

  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end
end
