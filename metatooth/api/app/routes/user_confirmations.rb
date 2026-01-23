# frozen_string_literal: true

# Controller for user confirmations
class App
  get '/user_confirmations/:token' do
    user_repo = UserRepo.new(MAIN_CONTAINER)
    user = user_repo.query(confirmation_token: params[:token]).first
    halt(404, 'Token not found') unless user

    confirmed_user = user_repo.confirm(user.id)

    if confirmed_user.confirmation_redirect_url
      redirect(confirmed_user.confirmation_redirect_url, 303)
    else
      'You are now confirmed!'
    end
  end
end
