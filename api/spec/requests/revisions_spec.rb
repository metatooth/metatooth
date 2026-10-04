# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Revisions', type: :request do
  let(:plan) { Factory[:plan] }
  let(:a) { Factory[:revision, plan_id: plan.id] }
  let(:b) { Factory[:revision, plan_id: plan.id] }
  let(:c) { Factory[:revision, plan_id: plan.id] }
  let(:revisions) { [a, b, c] }
  let(:revision_repo) { RevisionRepo.new(MAIN_CONTAINER) }

  before do
    plan
    revisions
  end

  context 'with valid API Key' do
    let(:key) { Factory[:api_key] }
    let(:key_str) { "#{key.id}:#{key.api_key}" }

    let(:headers) do
      { 'HTTP_AUTHORIZATION' => "Metaspace-Token api_key=#{key_str}" }
    end

    describe 'GET /plans/:pid/revisions' do
      before { get "/plans/#{plan.locator}/revisions", nil, headers }

      it 'receives HTTP status 200' do
        expect(last_response.status).to eq 200
      end

      it 'receives a json with the "data" root key' do
        expect(json_body['data']).to_not be nil
      end

      it 'receives all 3 revisions' do
        expect(json_body['data'].size).to eq 3
      end
    end

    describe 'GET /plans/:pid/revisions/:id' do
      context 'with existing resource' do
        before { get "/plans/#{plan.locator}/revisions/#{a.locator}", nil, headers }

        it 'receives HTTP status 200' do
          expect(last_response.status).to eq 200
        end

        it 'receives a json with the "data" root key' do
          expect(json_body['data']).to_not be nil
        end

        it 'receives revision' do
          expect(json_body['data']['locator']).to eq a.locator
        end
      end

      context 'with nonexistent resource' do
        it 'gets HTTP status 404' do
          get "/plans/#{plan.locator}/revisions/23456234", nil, headers do
            expect(last_response.status).to eq 404
          end
        end
      end
    end

    describe 'PUT /plans/:pid/revisions/:id' do
      before { put "/plans/#{plan.locator}/revisions/#{b.locator}", { data: params }, headers }

      context 'with valid parameters' do
        let(:params) do
          { description: 'Metatooth RSpec' }
        end

        it 'gets HTTP status 200' do
          expect(last_response.status).to eq 200
        end

        it 'receives the updated resource' do
          expect(json_body['data']['description']).to eq(
            'Metatooth RSpec'
          )
        end

        it 'updates the record in the database' do
          expect(revision_repo.by_id(b.id).description).to eq(
            'Metatooth RSpec'
          )
        end
      end

      context 'with invalid parameters' do
        let(:params) { { number: '' } }

        it 'gets HTTP status 422' do
          expect(last_response.status).to eq 422
        end

        it 'receives the error details' do
          expect(json_body['error']['invalid_params']).to eq(
            'number' =>
            ['number must be filled']
          )
        end

        it 'does not update a record in the database' do
          expect(revision_repo.by_id(b.id).number).to eq(
            b.number
          )
        end
      end
    end

    describe 'DELETE /plans/:pid/revisions/:id' do
      context 'with existing resource' do
        before { delete "/plans/#{plan.locator}/revisions/#{b.locator}", nil, headers }
        it 'gets HTTP status 204' do
          expect(last_response.status).to eq 204
        end

        revisions = MAIN_CONTAINER.relations[:revisions]

        it 'deletes the revision from the database' do
          expect(revisions.to_a.length).to eq 3
          expect(revisions.where(deleted: false).to_a.length).to eq 2
        end
      end

      context 'with nonexisting resource' do
        it 'gets HTTP status 404' do
          delete "/plans/#{plan.locator}/revisions/2345234", nil, headers
          expect(last_response.status).to eq 404
        end
      end
    end
  end

  context 'with invalid API Key' do
    describe '/plans/:pid/revisions' do
      it 'returns HTTP status 401' do
        get "/plans/#{plan.locator}/revisions"
        expect(last_response.status).to eq 401
      end
    end

    describe 'GET /plans/:pid/revisions/:id' do
      it 'returns HTTP status 401' do
        get "/plans/#{plan.locator}/revisions/#{a.locator}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'POST /plans/:pid/revisions' do
      it 'returns HTTP status 401' do
        post "/plans/#{plan.locator}/revisions"
        expect(last_response.status).to eq 401
      end
    end

    describe 'PUT /plans/:pid/revisions/:id' do
      it 'returns HTTP status 401' do
        put "/plans/#{plan.locator}/revisions/#{a.locator}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'DELETE /plans/:pid/revisions/:id' do
      it 'returns HTTP status 401' do
        delete "/plans/#{plan.locator}/revisions/#{a.locator}"
        expect(last_response.status).to eq 401
      end
    end
  end
end
