# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Plans', type: :request do
  let(:a) { Factory[:plan] }
  let(:b) { Factory[:plan] }
  let(:c) { Factory[:plan] }
  let(:plans) { [a, b, c] }
  let(:plan_repo) { PlanRepo.new(MAIN_CONTAINER) }

  before do
    plans
  end

  context 'with valid API Key' do
    let(:key) { Factory[:api_key] }
    let(:key_str) { "#{key.id}:#{key.api_key}" }

    let(:headers) do
      { 'HTTP_AUTHORIZATION' => "Metaspace-Token api_key=#{key_str}" }
    end

    describe 'GET /plans' do
      before { get '/plans', nil, headers }

      it 'receives HTTP status 200' do
        expect(last_response.status).to eq 200
      end

      it 'receives a json with the "data" root key' do
        expect(json_body['data']).to_not be nil
      end

      it 'receives all 3 plans' do
        expect(json_body['data'].size).to eq 3
      end
    end

    describe 'GET /plans/:id' do
      context 'with existing resource' do
        before { get "/plans/#{a.locator}", nil, headers }

        it 'receives HTTP status 200' do
          expect(last_response.status).to eq 200
        end

        it 'receives a json with the "data" root key' do
          expect(json_body['data']).to_not be nil
        end

        it 'receives plan' do
          expect(json_body['data']['locator']).to eq a.locator
          expect(json_body['data']['name']).to eq a.name
        end
      end

      context 'with nonexistent resource' do
        it 'gets HTTP status 404' do
          get '/plans/23456234', nil, headers do
            expect(last_response.status).to eq 404
          end
        end
      end
    end

    describe 'POST /plans' do
      before { post '/plans', params, headers }

      context 'with valid parameters' do
        let(:params) do
          { data: { name: 'Metatooth RSpec',
                    location: 'http://example.org/asset.json' } }
        end

        it 'gets HTTP status 201' do
          expect(last_response.status).to eq 201
        end

        it 'receives the newly created resource' do
          expect(json_body['data']['name']).to eq 'Metatooth RSpec'
        end

        plans = MAIN_CONTAINER.relations[:plans]

        it 'adds a record in the database' do
          expect(plans.to_a.length).to eq 4
        end

        it 'gets the new resource location in the Location header' do
          expect(last_response.headers['Location'])
            .to eq "http://example.org/plans/#{plans.to_a.last[:locator]}"
        end
      end

      context 'with invalid parameters' do
        let(:params) do
          { data: { name: '' } }
        end

        it 'returns HTTP status 422' do
          expect(last_response.status).to eq 422
        end

        it 'receives the error details' do
          expect(json_body['error']['invalid_params'])
            .to eq 'name' => ['name must be filled']
        end

        plans = MAIN_CONTAINER.relations[:plans]

        it 'does not create a record in the database' do
          expect(plans.to_a.length).to eq 3
        end
      end
    end

    describe 'PUT /plans/:id' do
      before { put "/plans/#{b.locator}", { data: params }, headers }

      context 'with valid parameters' do
        let(:params) do
          { name: 'Metatooth RSpec' }
        end

        it 'gets HTTP status 200' do
          expect(last_response.status).to eq 200
        end

        it 'receives the updated resource' do
          expect(json_body['data']['name']).to eq(
            'Metatooth RSpec'
          )
        end

        it 'updates the record in the database' do
          expect(plan_repo.by_id(b.id).name).to eq(
            'Metatooth RSpec'
          )
        end
      end

      context 'with invalid parameters' do
        let(:params) { { name: '' } }

        it 'gets HTTP status 422' do
          expect(last_response.status).to eq 422
        end

        it 'receives the error details' do
          expect(json_body['error']['invalid_params']).to eq(
            'name' =>
            ['name must be filled']
          )
        end

        it 'does not update a record in the database' do
          expect(plan_repo.by_id(b.id).name).to eq(
            b.name
          )
        end
      end
    end

    describe 'DELETE /plans/:id' do
      context 'with existing resource' do
        before { delete "/plans/#{b.locator}", nil, headers }
        it 'gets HTTP status 204' do
          expect(last_response.status).to eq 204
        end

        it 'deletes the plan from the database' do
          plans = MAIN_CONTAINER.relations[:plans]
          expect(plans.to_a.length).to eq 3
          expect(plans.where(deleted: false).to_a.length).to eq 2
        end
      end

      context 'with nonexisting resource' do
        it 'gets HTTP status 404' do
          delete '/plans/2345234', nil, headers
          expect(last_response.status).to eq 404
        end
      end
    end
  end

  context 'with invalid API Key' do
    describe 'GET /plans' do
      it 'returns HTTP status 401' do
        get '/plans'
        expect(last_response.status).to eq 401
      end
    end

    describe 'GET /plans/:id' do
      it 'returns HTTP status 401' do
        get "/plans/#{a.locator}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'POST /plans' do
      it 'returns HTTP status 401' do
        post '/plans'
        expect(last_response.status).to eq 401
      end
    end

    describe 'PUT /plans/:id' do
      it 'returns HTTP status 401' do
        put "/plans/#{a.locator}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'DELETE /plans/:id' do
      it 'returns HTTP status 401' do
        delete "/plans/#{a.locator}"
        expect(last_response.status).to eq 401
      end
    end
  end
end
