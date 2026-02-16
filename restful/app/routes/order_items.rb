# frozen_string_literal: true

# The order items endpoints.
class App
  options '/orders/:order_id/items' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, POST'
  end

  options '/orders/:order_id/items/:id' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, PUT, DELETE'
  end

  get '/orders/:order_id/items' do
    authenticate_user

    order = order_repo.by_id(params[:order_id])
    return resource_not_found if order.nil?

    items = order_item_repo.query(order_id: order[:id])

    status 200
    { data: items }.to_json
  end

  post '/orders/:order_id/items' do
    authenticate_user

    order = order_repo.by_id(params[:order_id])
    return resource_not_found if order.nil?

    item_hash = { order_id: order[:id] }
    order_item_params.each do |k, v|
      item_hash[k.to_sym] = v
    end

    errors = OrderItemContract.new.call(item_hash).errors(full: true).to_h

    if errors.empty?
      new_item = order_item_repo.create(item_hash)

      status 201
      { data: new_item.to_h }.to_json
    else
      unprocessable_entity!(errors)
    end
  end

  get '/orders/:order_id/items/:id' do
    authenticate_user

    order = order_repo.by_id(params[:order_id])
    return resource_not_found if order.nil?

    if order_item
      status 200
      { data: order_item.to_h }.to_json
    else
      resource_not_found
    end
  end

  put '/orders/:order_id/items/:id' do
    authenticate_user

    order = order_repo.by_id(params[:order_id])
    return resource_not_found if order.nil?

    if order_item.nil?
      resource_not_found
    else
      item_hash = order_item.to_h
      order_item_params.each do |k, v|
        item_hash[k.to_sym] = v
      end

      errors = OrderItemContract.new.call(item_hash).errors(full: true).to_h

      if errors.empty?
        updated_item = order_item_repo.update(order_item.id, item_hash)
        status :ok
        { data: updated_item.to_h }.to_json
      else
        unprocessable_entity!(errors)
      end
    end
  end

  delete '/orders/:order_id/items/:id' do
    authenticate_user

    order = order_repo.by_id(params[:order_id])
    return resource_not_found if order.nil?

    if order_item.nil?
      resource_not_found
    else
      order_item_repo.delete(order_item.id)
      status :no_content
    end
  end

  private

  def order_item
    @order_item ||= order_item_repo.by_id(params[:id])
  rescue StandardError
    nil
  end

  def order_item_params
    params[:data]&.slice(:product_id, :quantity, :price)
  end

  def order_item_repo
    @order_item_repo ||= OrderItemRepo.new(MAIN_CONTAINER)
  end

  def order_repo
    @order_repo ||= OrderRepo.new(MAIN_CONTAINER)
  end
end
