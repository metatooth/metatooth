# frozen_string_literal: true

# Conviences for plans
class PlanRepo < ROM::Repository[:plans]
  commands :create, update: :by_pk

  def by_id(id)
    plans.active.by_pk(id).one!
  end

  def by_locator(locator)
    plans.active.where(locator: locator).one!
  end

  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end

  def plan_with_revisions(locator)
    plans.active.where(locator: locator).combine(:revisions)
  end
end
