# frozen_string_literal: true

##
# OrderRepo provides data access methods for Order records.
#
# This repository implements the Repository pattern on top of ROM (Ruby Object Mapper),
# providing high-level operations for creating, reading, updating, and deleting orders.
#
# @example Get an order by ID
#   repo = OrderRepo.new(container)
#   order = repo.by_id(123)
#
# @example Query orders for a user
#   repo = OrderRepo.new(container)
#   user_orders = repo.query(user_id: 456)
#
class OrderRepo < ROM::Repository[:orders]
  commands :create, update: :by_pk

  ##
  # Retrieve an order by its primary key ID.
  #
  # @param [Integer] id The order ID
  # @return [ROM::Struct::Order] The order record
  # @raise [ROM::TupleCountMismatchError] If the order doesn't exist
  #
  def by_id(id)
    orders.by_pk(id).one!
  end

  ##
  # Soft-delete an order by setting deleted flag and timestamp.
  #
  # @param [Integer] id The order ID
  # @return [ROM::Struct::Order] The updated order record
  #
  def delete(id)
    update(id, deleted: true, deleted_at: DateTime.now)
  end

  ##
  # Find orders for a specific user with associated user records.
  #
  # @param [Integer] uid The user ID
  # @return [ROM::Relation] Relation of orders for the user
  #
  def for_user(uid)
    orders.combine(:users).where(id: uid)
  end

  ##
  # Query orders by flexible conditions.
  #
  # @param [Hash] conditions Query conditions (e.g., { user_id: 123, status: 'pending' })
  # @return [Array<ROM::Struct::Order>] Array of matching order records
  #
  def query(conditions)
    orders.where(conditions).to_a
  end
end
