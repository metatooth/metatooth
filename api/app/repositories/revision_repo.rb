# frozen_string_literal: true

# Conveniences for a revision
class RevisionRepo < ROM::Repository[:revisions]
  commands :create, update: :by_pk

  def by_id(id)
    revisions.by_pk(id).one!
  end

  def by_locator(locator)
    revisions.where(locator: locator).one!
  end

  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end
end
