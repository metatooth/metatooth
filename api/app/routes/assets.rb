# frozen_string_literal: true

# The assets endpoints.
class App
  options '/assets' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, POST'
  end

  options '/assets/:id' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'GET, PUT, DELETE'
  end

  get '/assets' do
    now = DateTime.now
    from = params[:from] ? DateTime.parse(params[:from]) : now - 30
    to = params[:to] ? DateTime.parse(params[:to]) - 1 : now

    asset_repo = AssetRepo.new(MAIN_CONTAINER)

    assets = asset_repo.by_created_at(from, to)

    status 200
    { data: assets.to_a }.to_json
  end

  post '/assets' do
    errors = AssetContract.new.call(asset_params).errors(full: true).to_h

    if errors.empty?
      asset_hash = {}
      asset_params.each do |k, v|
        asset_hash[k.to_sym] = v
      end

      new_asset = asset_repo.create(asset_hash)
      response.headers['Location'] =
        "#{request.scheme}://#{request.host}/assets/#{new_asset.locator}"
      status 201
      { data: new_asset.to_h }.to_json
    else
      unprocessable_entity!(errors)
    end
  end

  get '/assets/:id' do
    if asset
      status 200
      { data: asset.to_h }.to_json
    else
      resource_not_found
    end
  end

  put '/assets/:id' do
    if asset.nil?
      resource_not_found
    else
      asset_hash = asset.to_h
      asset_params.each do |k, v|
        asset_hash[k.to_sym] = v
      end

      errors = AssetContract.new.call(asset_hash).errors(full: true).to_h

      if errors.empty?
        updated_asset = asset_repo.update(asset.id, asset_hash)
        status :ok
        { data: updated_asset.to_h }.to_json
      else
        unprocessable_entity!(errors)
      end
    end
  end

  delete '/assets/:id' do
    if asset.nil?
      resource_not_found
    else
      asset_repo.delete(asset.id)
      status :no_content
    end
  end

  private

  def asset
    @asset ||= AssetRepo.new(MAIN_CONTAINER).by_locator(params[:id])
  rescue StandardError
    nil
  end

  def asset_repo
    @asset_repo ||= AssetRepo.new(MAIN_CONTAINER)
  end

  def asset_params
    return params[:data]&.slice(:url, :mime_type) unless params.empty?

    request.body.rewind
    check = JSON.parse(request.body.read)
    check['data']&.slice('url', 'mime_type', 'service', 'bucket', 's3key', 'etag')
  end
end
