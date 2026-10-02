# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Password Reset Flow', type: :request do
  let(:john) { Factory[:user] }
  let(:key) { Factory[:api_key] }
  let(:headers) do
    { 'HTTP_AUTHORIZATION' =>
      "Alexandria-Token api_key=#{key.id}:#{key.api_key}" }
  end
  let(:create_params) do
    { email: john.email, reset_password_redirect_url: 'http://example.com' }
  end
  let(:update_params) { { password: 'new_password' } }
  let(:user_repo) { UserRepo.new(MAIN_CONTAINER) }

  it 'resets the password' do
    user = user_repo.by_email(john.email)
    expect(user.authenticate('password')).to_not be false
    # Step 1
    expect(UserMailer).to(receive(:reset_password).with(user.attributes))
    post 'password_resets', { data: create_params }, headers
    expect(last_response.status).to eq 204

    user = user_repo.by_email(john.email)
    puts "USER #{user.inspect}"

    reset_token = user.attributes[:reset_password_token]

    # Step 2
    sbj = get "/password_resets/#{reset_token}"
    expect(sbj).to redirect_to("http://example.com?reset_token=#{reset_token}")
    # Step 3
    put "/password_resets/#{reset_token}",
        { data: update_params }, headers
    expect(last_response.status).to eq 204

    user = user_repo.by_email(john.email)
    expect(user.authenticate('new_password')).equal? true
  end
end
