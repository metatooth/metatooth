# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Users', type: :request do
  let(:user_repo) { UserRepo.new(MAIN_CONTAINER) }

  let(:a) { Factory[:user] }
  let(:b) { Factory[:user] }
  let(:c) { Factory[:user] }
  let(:users) { [a, b, c] }

  before do
    user_repo
    users
  end

  context 'with valid API Key' do
    let(:key) { Factory[:api_key] }
    let(:key_str) { "#{key.id}:#{key.api_key}" }

    context 'with valid access token' do
      let(:access_token) do
        Factory[:access_token, api_key_id: key.id, user_id: a.id]
      end

      let(:token) do
        AccessTokenRepo.new(MAIN_CONTAINER).generate(access_token.id)
      end
      let(:token_str) { "#{a.id}:#{token}" }
      let(:headers) do
        { 'HTTP_AUTHORIZATION' =>
        "Metaspace-Token api_key=#{key_str}, access_token=#{token_str}" }
      end

      describe 'GET /users' do
        before { get '/users', nil, headers }

        it 'receives HTTP status 200' do
          expect(last_response.status).to eq 200
        end

        it 'receives a json with the "data" root key' do
          expect(json_body['data']).to_not be nil
        end

        it 'receives all 3 users' do
          expect(json_body['data'].size).to eq 3
        end
      end

      describe 'GET /users/:id' do
        context 'with existing resource' do
          before { get "/users/#{b.id}", nil, headers }

          it 'receives HTTP status 200' do
            expect(last_response.status).to eq 200
          end

          it 'receives a json with the "data" root key' do
            expect(json_body['data']).to_not be nil
          end

          it 'receives user' do
            expect(json_body['data']['email']).to eq b.email
          end
        end

        context 'with nonexistent resource' do
          it 'gets HTTP status 404' do
            get '/users/23456234', nil, headers do
              expect(last_response.status).to eq 404
            end
          end
        end
      end

      describe 'PUT /users/:id' do
        before { put "/users/#{b.id}", { data: params }, headers }

        context 'with valid parameters' do
          let(:params) { { name: 'Bobby' } }

          it 'gets HTTP status 200' do
            expect(last_response.status).to eq 200
          end

          it 'receives the updated resource' do
            expect(json_body['data']['name']).to eq('Bobby')
          end

          it 'updates the record in the database' do
            expect(user_repo.by_id(b.id)[:name]).to eq('Bobby')
          end
        end

        context 'with invalid parameters' do
          let(:params) { { email: '' } }

          it 'gets HTTP status 422' do
            expect(last_response.status).to eq 422
          end

          it 'receives the error details' do
            expect(json_body['error']['invalid_params']).to eq(
              'email' => ['email must be filled']
            )
          end

          it 'does not update a record in the database' do
            expect(user_repo.by_id(b.id)[:email]).to eq b.email
          end
        end
      end

      describe 'DELETE /users/:id' do
        context 'with existing resource' do
          before { delete "/users/#{b.id}", nil, headers }
          it 'gets HTTP status 204' do
            expect(last_response.status).to eq 204
          end

          it 'deletes the user from the database' do
            users = MAIN_CONTAINER.relations[:users]
            expect(users.to_a.length).to eq 3
            expect(users.where(deleted: false).to_a.length).to eq 2
          end
        end

        context 'with nonexisting resource' do
          it 'gets HTTP status 404' do
            delete '/users/342523455', nil, headers
            expect(last_response.status).to eq 404
          end
        end
      end
    end

    context 'with invalid access token' do
      let(:headers) do
        { 'HTTP_AUTHORIZATION' =>
        "Metaspace-Token api_key=#{key_str}, access_token=1:fake" }
      end

      describe 'GET /users' do
        it 'returns 401' do
          get '/users', nil, headers
          expect(last_response.status).to eq 401
        end
      end

      describe 'GET /users/:id' do
        it 'returns 401' do
          get '/users/1', nil, headers
          expect(last_response.status).to eq 401
        end
      end

      describe 'DELETE /users/:id' do
        it 'returns 401' do
          delete '/users/1', nil, headers
          expect(last_response.status).to eq 401
        end
      end
    end

    context 'without access token' do
      let(:headers) do
        { 'HTTP_AUTHORIZATION' => "Metaspace-Token api_key=#{key_str}" }
      end

      describe 'GET /users' do
        it 'returns 401' do
          get '/users', nil, headers
          expect(last_response.status).to eq 401
        end
      end

      describe 'GET /users/:id' do
        it 'returns 401' do
          get '/users/1', nil, headers
          expect(last_response.status).to eq 401
        end
      end

      describe 'POST /users' do
        before { post '/users', params, headers }

        context 'with valid parameters' do
          let(:params) do
            { data: { email: 'someone@example.com',
                      name: 'Johnny',
                      password: 'password' } }
          end

          it 'gets HTTP status 201' do
            expect(last_response.status).to eq 201
          end

          it 'receives the newly created resource' do
            expect(json_body['data']['email']).to eq 'someone@example.com'
          end

          users = MAIN_CONTAINER.relations[:users]

          it 'adds a record in the database' do
            expect(users.to_a.length).to eq 4
          end

          it 'gets the new resource location in the Location header' do
            expect(last_response.headers['Location'])
              .to eq "http://example.org/users/#{users.to_a.last[:id]}"
          end
        end

        context 'with invalid parameters' do
          let(:params) do
            { data: { email: '', name: '', password: 'password' } }
          end

          it 'returns HTTP status 422' do
            expect(last_response.status).to eq 422
          end

          it 'receives the error details' do
            expect(json_body['error']['invalid_params']).to eq(
              'email' => ['email must be filled']
            )
          end
        end
      end

      describe 'DELETE /users/:id' do
        it 'returns 401' do
          delete '/users/1', nil, headers
          expect(last_response.status).to eq 401
        end
      end
    end
  end

  context 'with invalid API Key' do
    describe 'GET /users' do
      it 'returns HTTP status 401' do
        get '/users'
        expect(last_response.status).to eq 401
      end
    end

    describe 'GET /users/:id' do
      it 'returns HTTP status 401' do
        get "/users/#{a.id}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'POST /users' do
      it 'returns HTTP status 401' do
        post '/users'
        expect(last_response.status).to eq 401
      end
    end

    describe 'PUT /users/:id' do
      it 'returns HTTP status 401' do
        put "/users/#{a.id}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'DELETE /users/:id' do
      it 'returns HTTP status 401' do
        delete "/users/#{a.id}"
        expect(last_response.status).to eq 401
      end
    end
  end
end
