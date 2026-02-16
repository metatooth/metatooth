# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'User Auth Flow', type: :request do
  Pony.override_options = { via: :test }

  def headers(user_id = nil, token = nil)
    key_str = "#{key.id}:#{key.api_key}"
    if user_id && token
      token_str = "#{user_id}:#{token}"
      { 'HTTP_AUTHORIZATION' =>
      "Metaspace-Token api_key=#{key_str}, access_token=#{token_str}" }
    else
      { 'HTTP_AUTHORIZATION' =>
        "Metaspace-Token api_key=#{key_str}" }
    end
  end

  let(:key) { Factory[:api_key] }
  let(:email) { 'john@gmail.com' }
  let(:password) { 'password' }
  let(:params) { { email: email, password: password, name: 'Johnny' } }

  it 'authenticate a new user' do
    # Step 1 - Create a user
    post '/users', { data: params }, headers
    expect(last_response.status).to eq 201
    id = json_body['data']['id']

    # Step 2 - Try to update name
    put "/users/#{id}", { data: { name: 'John' } }, headers
    expect(last_response.status).to eq 401

    # Step 3 - Login
    post '/access_tokens',
         { data: { email: email, password: password } },
         headers
    expect(last_response.status).to eq 201
  end
end
