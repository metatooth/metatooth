# frozen_string_literal: true

# Endpoints for user addresses
class App
  get '/users/:uid/addresses' do
    authenticate_user

    if selected_user.nil?
      resource_not_found
    else
      status 200
      { data: selected_user.addresses.to_a }.to_json
    end
  end

  get '/users/:uid/addresses/:id' do
    authenticate_user

    if selected_user.nil?
      resource_not_found
    else
      address = selected_user.addresses.find { |a| a.id == params[:id].to_i }

      if address.nil?
        resource_not_found
      else
        status 200
        { data: address.to_h }.to_json
      end
    end
  end

  post '/users/:uid/addresses' do
    authenticate_user

    if selected_user.nil?
      resource_not_found
    else
      address.user = selected_user

      if address.save
        path = "/users/#{selected_user.id}/addresses/#{address.id}"
        response.headers['Location'] =
          "#{request.scheme}://#{request.host}#{path}"
        status :created
        { data: address }.to_json
      else
        unprocessable_entity!(address)
      end
    end
  end

  put '/users/:uid/addresses/:id' do
    authenticate_user

    if selected_user.nil? ||
       !selected_user.addresses.find { |a| a.id == params[:id].to_i }
      resource_not_found
    else
      address_repo = AddressRepo.new(MAIN_CONTAINER)
      address = address_repo.by_id(params[:id])

      address_hash = address.to_h
      address_params.each do |k, v|
        address_hash[k.to_sym] = v
      end

      errors = AddressContract.new.call(address_hash).errors(full: true).to_h

      if errors.empty?
        updated_address = address_repo.update(address.id, address_hash)
        status :ok
        { data: updated_address.to_h }.to_json
      else
        unprocessable_entity!(errors)
      end
    end
  end

  delete '/users/:uid/addresses/:id' do
    authenticate_user

    if selected_user.nil? ||
       !selected_user.addresses.find { |a| a.id == params[:id].to_i }
      resource_not_found
    else
      address_repo = AddressRepo.new(MAIN_CONTAINER)
      address_repo.delete(params[:id])
      status :no_content
    end
  end

  private

  def selected_user
    user_repo = UserRepo.new(MAIN_CONTAINER)
    @selected_user ||= user_repo.user_with_addresses(params[:uid])
  end

  def address_repo
    @address_repo ||= AddressRepo.new(MAIN_CONTAINER)
  end

  def address_params
    params[:data]&.slice(:name,
                         :organization,
                         :address1,
                         :address2,
                         :city,
                         :state,
                         :zip5,
                         :zip4,
                         :postcode)
  end
end
