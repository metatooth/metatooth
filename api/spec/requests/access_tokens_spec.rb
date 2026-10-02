# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Access Tokens', type: :request do
  let(:john) { Factory[:user] }

  describe 'POST /access_tokens' do
    context 'with valid API key' do
      let(:key) { Factory[:api_key] }
      let(:key_str) { "#{key.id}:#{key.api_key}" }

      let(:headers) do
        { 'HTTP_AUTHORIZATION' =>
        "Metaspace-Token api_key=#{key_str}" }
      end

      before { post '/access_tokens', params, headers }

      context 'with existing user' do
        context 'with valid password' do
          let(:params) { { data: { email: john.email, password: 'password' } } }

          it 'gets HTTP status 201 Created' do
            expect(last_response.status).to eq 201
          end

          it 'receives an access token' do
            expect(json_body['data']['token']).to_not be nil
          end

          it 'receives the user embedded' do
            expect(json_body['data']['user']['id']).to eq john.id
          end
        end

        context 'with invalid password' do
          let(:params) { { data: { email: john.email, password: 'fake' } } }

          it 'returns 422 Unprocessable Entity' do
            expect(last_response.status).to eq 422
          end
        end
      end

      context 'with nonexistent user' do
        let(:params) { { data: { email: 'unknown', password: 'fake' } } }

        it 'gets HTTP status 404 Not Found' do
          expect(last_response.status).to eq 404
        end
      end
    end

    context 'with invalid API key' do
      it 'returns HTTP status 401 Forbidden' do
        post '/access_tokens', params: {}
        expect(last_response.status).to eq 401
      end
    end
  end

  describe 'DELETE /access_token' do
    context 'with valid API key' do
      let(:api_key) { Factory[:api_key] }
      let(:api_key_str) { "#{api_key.id}:#{api_key.api_key}" }

      before { delete '/access_tokens', nil, headers }

      context 'with valid access token' do
        let(:access_token) do
          Factory[:access_token, user_id: john.id, api_key_id: api_key.id]
        end
        access_token_repo = AccessTokenRepo.new(MAIN_CONTAINER)

        let(:token) { access_token_repo.generate(access_token.id) }
        let(:token_str) { "#{john.id}:#{token}" }
        let(:headers) do
          {
            'HTTP_AUTHORIZATION' =>
            "Metaspace-Token api_key=#{api_key_str}, access_token=#{token_str}"
          }
        end

        it 'returns 204 No Content' do
          expect(last_response.status).to eq 204
        end

        it 'destroys the access token' do
          tokens = access_token_repo.query(user_id: john.id)
          tokens.select! do |token|
            token[:deleted] == false
          end
          expect(tokens.size).to eq 0
        end
      end

      context 'with invalid access token' do
        let(:headers) do
          { 'HTTP_AUTHORIZATION' =>
            "Metaspace-Token api_key=#{api_key_str}, access_token=1:fake" }
        end

        it 'returns 401' do
          expect(last_response.status).to eq 401
        end
      end
    end

    context 'with invalid API key' do
      it 'returns HTTP status 401' do
        delete '/api/access_tokens', params: {}
        expect(last_response.status).to eq 401
      end
    end
  end
end
