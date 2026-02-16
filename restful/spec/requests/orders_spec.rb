# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Orders', type: :request do
  let(:user) { Factory[:user] }
  let(:address) { Factory[:address, user_id: user.id] }
  let(:a) { Factory[:order, user_id: user.id, bill_id: address.id, ship_id: address.id] }
  let(:b) { Factory[:order, user_id: user.id, bill_id: address.id, ship_id: address.id] }
  let(:c) { Factory[:order, user_id: user.id, bill_id: address.id, ship_id: address.id] }
  let(:orders) { [a, b, c] }
  let(:product) { Factory[:product] }
  let(:order_repo) { OrderRepo.new(MAIN_CONTAINER) }

  before do
    user
    address
    orders
    product

    Factory[:order_item, order_id: a.id, product_id: product.id]
    Factory[:order_item, order_id: b.id,  product_id: product.id]
    Factory[:order_item, order_id: c.id,  product_id: product.id]
  end

  context 'with valid API Key' do
    let(:key) { Factory[:api_key] }
    let(:key_str) { "#{key.id}:#{key.api_key}" }

    context 'with valid access token' do
      let(:access_token) do
        Factory[:access_token, api_key_id: key.id, user_id: user.id]
      end

      let(:token) do
        AccessTokenRepo.new(MAIN_CONTAINER).generate(access_token.id)
      end
      let(:token_str) { "#{user.id}:#{token}" }
      let(:headers) do
        { 'HTTP_AUTHORIZATION' =>
        "Metaspace-Token api_key=#{key_str}, access_token=#{token_str}" }
      end

      describe 'GET /orders' do
        before { get '/orders', nil, headers }

        it 'receives HTTP status 200' do
          expect(last_response.status).to eq 200
        end

        it 'receives a json with the "data" root key' do
          expect(json_body['data']).to_not be nil
        end

        it 'receives all 3 orders' do
          expect(json_body['data'].size).to eq 3
        end
      end

      describe 'GET /orders/:id' do
        context 'with existing resource' do
          before { get "/orders/#{a.id}", nil, headers }

          it 'receives HTTP status 200' do
            expect(last_response.status).to eq 200
          end

          it 'receives a json with the "data" root key' do
            expect(json_body['data']).to_not be nil
          end

          it 'receives order' do
            expect(json_body['data']['locator']).to eq a.locator
          end
        end

        context 'with nonexistent resource' do
          it 'gets HTTP status 404' do
            get '/orders/23456234', nil, headers do
              expect(last_response.status).to eq 404
            end
          end
        end
      end

      describe 'PUT /orders/:id' do
        before { put "/orders/#{b.id}", { data: params }, headers }

        context 'with valid parameters' do
          let(:params) do
            { shipped_impression_kit_at: '1974-06-21 00:00:00 -0400' }
          end

          it 'gets HTTP status 200' do
            expect(last_response.status).to eq 200
          end

          it 'receives the updated resource' do
            expect(json_body['data']['shipped_impression_kit_at']).to eq(
              '1974-06-21 00:00:00 -0400'
            )
          end

          it 'updates the record in the database' do
            updated_order = order_repo.by_id(b.id)
            expect(Time.new(1974, 6, 21, 0, 0, 0)).to eq(
              updated_order[:shipped_impression_kit_at]
            )
          end
        end

        context 'with invalid parameters' do
          let(:params) { { shipped_impression_kit_at: '' } }

          it 'gets HTTP status 422' do
            expect(last_response.status).to eq 422
          end

          it 'receives the error details' do
            expect(json_body['error']['invalid_params']).to eq(
              'shipped_impression_kit_at' =>
              ['shipped_impression_kit_at must_be_a_valid_date']
            )
          end

          it 'does not update a record in the database' do
            expect(order_repo.by_id(b.id).shipped_impression_kit_at).to eq(
              b.shipped_impression_kit_at
            )
          end
        end
      end

      describe 'DELETE /orders/:id' do
        context 'with existing resource' do
          before { delete "/orders/#{b.id}", nil, headers }
          it 'gets HTTP status 204' do
            expect(last_response.status).to eq 204
          end

          it 'deletes the order from the database' do
            orders = MAIN_CONTAINER.relations[:orders]
            expect(orders.to_a.length).to eq 3
            expect(orders.where(deleted: false).to_a.length).to eq 2
          end
        end

        context 'with nonexisting resource' do
          it 'gets HTTP status 404' do
            delete '/orders/2345234', nil, headers
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

      describe 'GET /orders' do
        it 'returns 401' do
          get '/orders', nil, headers
          expect(last_response.status).to eq 401
        end
      end

      describe 'GET /orders/:id' do
        it 'returns 401' do
          get '/orders/1', nil, headers
          expect(last_response.status).to eq 401
        end
      end

      describe 'DELETE /orders/:id' do
        it 'returns 401' do
          delete '/orders/1', nil, headers
          expect(last_response.status).to eq 401
        end
      end
    end

    context 'without access token' do
      let(:headers) do
        { 'HTTP_AUTHORIZATION' => "Metaspace-Token api_key=#{key_str}" }
      end

      describe 'GET /orders' do
        it 'returns 401' do
          get '/orders', nil, headers
          expect(last_response.status).to eq 401
        end
      end

      describe 'GET /orders/:id' do
        it 'returns 401' do
          get '/orders/1', nil, headers
          expect(last_response.status).to eq 401
        end
      end

      describe 'POST /orders' do
        before { post '/orders', { data: params }, headers }

        context 'with valid parameters' do
          let(:params) do
            { email: 'someone@example.com',
              name: 'Johnny',
              password: 'password' }
          end

          it 'gets HTTP status 401' do
            expect(last_response.status).to eq 401
          end
        end

        context 'with invalid parameters' do
          let(:params) do
            { email: '', name: '', password: 'password' }
          end

          it 'returns HTTP status 401' do
            expect(last_response.status).to eq 401
          end
        end
      end

      describe 'DELETE /orders/:id' do
        it 'returns 401' do
          delete '/orders/1', nil, headers
          expect(last_response.status).to eq 401
        end
      end
    end
  end

  context 'with invalid API Key' do
    describe 'GET /orders' do
      it 'returns HTTP status 401' do
        get '/orders'
        expect(last_response.status).to eq 401
      end
    end

    describe 'GET /orders/:id' do
      it 'returns HTTP status 401' do
        get "/orders/#{a.id}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'POST /orders' do
      it 'returns HTTP status 401' do
        post '/orders'
        expect(last_response.status).to eq 401
      end
    end

    describe 'PUT /orders/:id' do
      it 'returns HTTP status 401' do
        put "/orders/#{a.id}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'DELETE /orders/:id' do
      it 'returns HTTP status 401' do
        delete "/orders/#{a.id}"
        expect(last_response.status).to eq 401
      end
    end
  end
end
