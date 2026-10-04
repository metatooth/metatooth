# frozen_string_literal: true

# The Products endpoints.
class App
  options '/products' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, POST'
  end

  options '/products/:id' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, PUT, DELETE'
  end

  get '/products' do
    status 200
    { data: products.to_a }.to_json
  end

  post '/products' do
    authenticate_user

    errors = ProductContract.new.call(product_params).errors(full: true).to_h

    if errors.empty?
      product_hash = {}
      product_params.each do |k, v|
        product_hash[k.to_sym] = v
      end

      product = product_repo.create(product_hash)

      UserMailer.new_product(current_user, product)
      response.headers['Location'] =
        "#{request.scheme}://#{request.host}/products/#{product.locator}"
      status :created
      { data: product.to_h }.to_json
    else
      unprocessable_entity!(errors)
    end
  end

  get '/products/:id' do
    if product
      status 200
      { data: product.to_h }.to_json
    else
      resource_not_found
    end
  end

  put '/products/:id' do
    authenticate_user

    if product.nil?
      resource_not_found
    else
      product_hash = product.to_h
      product_params.each do |k, v|
        product_hash[k.to_sym] = v
      end

      errors = ProductContract.new.call(product_hash).errors(full: true).to_h

      if errors.empty?
        updated_product = product_repo.update(product.id, product_hash)
        status :ok
        { data: updated_product.to_h }.to_json
      else
        unprocessable_entity!(errors)
      end
    end
  end

  delete '/products/:id' do
    authenticate_user

    if product.nil?
      resource_not_found
    else
      product_repo.delete(product.id)
      status :no_content
    end
  end

  private

  def product
    @product ||= product_repo.by_id(params[:id])
  rescue StandardError
    nil
  end

  def products
    @products ||= MAIN_CONTAINER.relations[:products].call
  end

  def product_params
    params[:data]&.slice(:name, :description)
  end

  def product_repo
    @product_repo ||= ProductRepo.new(MAIN_CONTAINER)
  end
end
