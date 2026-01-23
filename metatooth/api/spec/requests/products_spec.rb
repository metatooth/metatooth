# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Products', type: :request do
  let(:a) { Factory[:product] }
  let(:b) { Factory[:product] }
  let(:c) { Factory[:product] }
  let(:products) { [a, b, c] }
  let(:user) { Factory[:user] }
  let(:product_repo) { ProductRepo.new(MAIN_CONTAINER) }

  before do
    products
    user
  end

  context 'with valid API Key' do
    let(:key) { Factory[:api_key] }
    let(:key_str) { "#{key.id}:#{key.api_key}" }

    context 'with valid access token' do
      let(:access_token) { Factory[:access_token, api_key_id: key.id, user_id: user.id] }
      access_token_repo = AccessTokenRepo.new(MAIN_CONTAINER)
      let(:token) { access_token_repo.generate(access_token.id) }
      let(:token_str) { "#{user.id}:#{token}" }
      let(:headers) do
        { 'HTTP_AUTHORIZATION' =>
        "Metaspace-Token api_key=#{key_str}, access_token=#{token_str}" }
      end

      describe 'POST /products' do
        before { post '/products', params, headers }

        context 'with valid parameters' do
          let(:params) do
            { data: { name: 'nightguard',
                      description: 'custom night guard' } }
          end

          it 'gets HTTPS status 201' do
            expect(last_response.status).to eq 201
          end
        end

        context 'with invalid parameters' do
          let(:params) { { data: { name: '' } } }
        end
      end

      describe 'PUT /products/:id' do
        before { put "/products/#{b.id}", params, headers }

        context 'with valid parameters' do
          let(:params) { { data: { name: 'Bobby' } } }

          it 'gets HTTP status 200' do
            expect(last_response.status).to eq 200
          end

          it 'receives the updated resource' do
            expect(json_body['data']['name']).to eq('Bobby')
          end

          it 'updates the record in the database' do
            expect(product_repo.by_id(b.id).name).to eq('Bobby')
          end
        end

        context 'with invalid parameters' do
          let(:params) { { data: { name: '' } } }

          it 'gets HTTP status 422' do
            expect(last_response.status).to eq 422
          end

          it 'receives the error details' do
            expect(json_body['error']['invalid_params']).to eq(
              'name' => ['name must be filled']
            )
          end

          it 'does not update a record in the database' do
            expect(product_repo.by_id(b.id).name).to eq b.name
          end
        end
      end

      describe 'DELETE /products/:id' do
        context 'with existing resource' do
          before { delete "/products/#{b.id}", nil, headers }
          it 'gets HTTP status 204' do
            expect(last_response.status).to eq 204
          end

          it 'deletes the product from the database' do
            products = MAIN_CONTAINER.relations[:products]
            expect(products.where(deleted: false).to_a.length).to eq 2
          end
        end

        context 'with nonexisting resource' do
          it 'gets HTTP status 404' do
            delete '/products/342523455', nil, headers
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

      describe 'POST /products' do
        it 'returns HTTP status 401' do
          post '/products', nil, headers
          expect(last_response.status).to eq 401
        end
      end

      describe 'PUT /products/:id' do
        it 'returns HTTP status 401' do
          put "/products/#{a.id}", nil, headers
          expect(last_response.status).to eq 401
        end
      end

      describe 'DELETE /products/:id' do
        it 'returns 401' do
          delete '/products/1', nil, headers
          expect(last_response.status).to eq 401
        end
      end
    end

    context 'without access token' do
      let(:headers) do
        { 'HTTP_AUTHORIZATION' => "Metaspace-Token api_key=#{key_str}" }
      end

      describe 'GET /products' do
        before { get '/products', nil, headers }

        it 'receives HTTP status 200' do
          expect(last_response.status).to eq 200
        end

        it 'receives a json with the "data" root key' do
          expect(json_body['data']).to_not be nil
        end

        it 'receives all 3 products' do
          expect(json_body['data'].size).to eq 3
        end
      end

      describe 'GET /products/:id' do
        context 'with existing resource' do
          before { get "/products/#{b.id}", nil, headers }

          it 'receives HTTP status 200' do
            expect(last_response.status).to eq 200
          end

          it 'receives a json with the "data" root key' do
            expect(json_body['data']).to_not be nil
          end

          it 'receives product' do
            expect(json_body['data']['name']).to eq b.name
          end
        end

        context 'with nonexistent resource' do
          it 'gets HTTP status 404' do
            get '/products/23456234', nil, headers do
              expect(last_response.status).to eq 404
            end
          end
        end
      end
    end
  end

  context 'with invalid API Key' do
    describe 'GET /products' do
      it 'returns HTTP status 401' do
        get '/products'
        expect(last_response.status).to eq 401
      end
    end

    describe 'GET /products/:id' do
      it 'returns HTTP status 401' do
        get "/products/#{a.id}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'POST /products' do
      it 'returns HTTP status 401' do
        post '/products'
        expect(last_response.status).to eq 401
      end
    end

    describe 'PUT /products/:id' do
      it 'returns HTTP status 401' do
        put "/products/#{a.id}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'DELETE /products/:id' do
      it 'returns HTTP status 401' do
        delete "/products/#{a.id}"
        expect(last_response.status).to eq 401
      end
    end
  end
end
