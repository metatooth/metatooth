# frozen_string_literal: true

# Endpoints to create and delete access tokens
class App
  options '/access_tokens' do
    response['Access-Control-Allow-Origin'] = '*'
    response['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response['Access-Control-Allow-Methods'] = 'POST, DELETE'
  end

  post '/access_tokens' do
    login_params = params[:data]&.slice(:email, :password)
    user = user_repo.by_email(login_params[:email].downcase)

    if user.nil?
      status 404
    elsif user.authenticate(login_params[:password])
      previous = access_token_repo.query(user_id: user[:id],
                                         api_key_id: api_key[:id]).first
      access_token_repo.delete(previous[:id]) if previous

      access_token = access_token_repo.create(user_id: user[:id],
                                              api_key_id: api_key[:id])
      token = access_token_repo.generate(access_token[:id])

      status 201
      { data: { token: token, user: { id: user[:id] } }, status: :created }
        .to_json
    else
      status 422
      { error: { message: 'Invalid credentials.' } }.to_json
    end
  end

  delete '/access_tokens' do
    if access_token
      access_token_repo = AccessTokenRepo.new(MAIN_CONTAINER)
      access_token_repo.delete(access_token[:id])
      status 204
    else
      halt 401
    end
  end

  private

  def access_token_repo
    @access_token_repo ||= AccessTokenRepo.new(MAIN_CONTAINER)
  end

  def user_repo
    @user_repo ||= UserRepo.new(MAIN_CONTAINER)
  end
end
