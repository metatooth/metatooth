# frozen_string_literal: true

# The orders endpoints.
class App
  options '/orders' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, POST'
  end

  options '/orders/:id' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, PUT, DELETE'
  end

  get '/orders' do
    authenticate_user

    now = Time.now
    from = params[:from] ? Time.parse(params[:from]) : now - (30 * 24 * 60 * 60)
    to = params[:to] ? Time.parse(params[:to]) : now + (1 * 24 * 60 * 60)

    all_orders = orders.to_a
    all_orders.select! { |v| v[:created_at] > from && v[:created_at] < to }

    status 200
    { data: all_orders }.to_json
  end

  post '/orders' do
    authenticate_user

    order_hash = { user_id: current_user.id }
    order_params.each do |k, v|
      order_hash[k.to_sym] = v
    end

    errors = OrderContract.new.call(order_hash).errors(full: true).to_h

    if errors.empty?
      new_order = order_repo.create(order_hash)

      status 200
      { data: new_order.to_h }.to_json
    else
      unprocessable_entity!(errors)
    end
  end

  get '/orders/:id' do
    authenticate_user

    if order
      status 200
      { data: order.to_h }.to_json
    else
      resource_not_found
    end
  end

  put '/orders/:id' do
    authenticate_user

    if order.nil?
      resource_not_found
    else
      order_hash = order.to_h
      order_params.each do |k, v|
        order_hash[k.to_sym] = v
      end

      errors = OrderContract.new.call(order_hash).errors(full: true).to_h

      if errors.empty?
        updated_order = order_repo.update(order.id, order_hash)
        status :ok
        { data: updated_order.to_h }.to_json
      else
        unprocessable_entity!(errors)
      end
    end
  end

  delete '/orders/:id' do
    authenticate_user

    if order.nil?
      resource_not_found
    else
      order_repo.delete(order.id)
      status :no_content
    end
  end

  private

  def order
    @order ||= order_repo.by_id(params[:id])
  rescue StandardError
    nil
  end

  def order_params
    params[:data]&.slice(:shipped_impression_kit_at,
                         :received_impression_kit_at,
                         :shipped_custom_night_guard_at)
  end

  def order_repo
    @order_repo ||= OrderRepo.new(MAIN_CONTAINER)
  end

  def orders
    @orders ||= MAIN_CONTAINER.relations[:orders].call
  end
end
