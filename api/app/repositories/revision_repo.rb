# frozen_string_literal: true

# Conveniences for a revision
class RevisionRepo < ROM::Repository[:revisions]
  commands :create, update: :by_pk

  def by_id(id)
    revisions.active.by_pk(id).one!
  end

  def by_locator(locator)
    revisions.active.where(locator: locator).one!
  end

  def by_plan_and_locator(plan_id, locator)
    revisions.active.where(plan_id: plan_id, locator: locator).one!
  end

  # Counts soft-deleted rows too so numbers are never reused.
  def next_number(plan_id)
    revisions.where(plan_id: plan_id).count
  end

  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end
end
