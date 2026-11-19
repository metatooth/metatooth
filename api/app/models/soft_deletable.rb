# frozen_string_literal: true

# Mixin for ROM relations whose table has a `deleted` flag. Public reads use
# `active` so soft-deleted rows are invisible.
module SoftDeletable
  def active
    where(self[:deleted].is(false))
  end
end
