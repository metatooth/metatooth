# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Assets', type: :request do
  let(:a) { Factory[:asset] }
  let(:b) { Factory[:asset] }
  let(:c) { Factory[:asset] }
  let(:assets) { [a, b, c] }
  let(:asset_repo) { AssetRepo.new(MAIN_CONTAINER) }

  before do
    assets
  end

  context 'with valid API Key' do
    let(:key) { Factory[:api_key] }
    let(:key_str) { "#{key.id}:#{key.api_key}" }

    let(:headers) do
      { 'HTTP_AUTHORIZATION' => "Metaspace-Token api_key=#{key_str}" }
    end

    describe 'GET /assets' do
      before { get '/assets', nil, headers }

      it 'receives HTTP status 200' do
        expect(last_response.status).to eq 200
      end

      it 'receives a json with the "data" root key' do
        expect(json_body['data']).to_not be nil
      end

      it 'receives all 3 assets' do
        expect(json_body['data'].size).to eq 3
      end
    end

    describe 'GET /assets/:id' do
      context 'with existing resource' do
        before { get "/assets/#{a.locator}", nil, headers }

        it 'receives HTTP status 200' do
          expect(last_response.status).to eq 200
        end

        it 'receives a json with the "data" root key' do
          expect(json_body['data']).to_not be nil
        end

        it 'receives asset' do
          expect(json_body['data']['locator']).to eq a.locator
        end
      end

      context 'with nonexistent resource' do
        it 'gets HTTP status 404' do
          get '/assets/23456234', nil, headers do
            expect(last_response.status).to eq 404
          end
        end
      end
    end

    describe 'POST /assets' do
      before { post '/assets', params, headers }

      context 'with valid parameters' do
        let(:params) do
          { data: { url: 'http://example.com/path/to/asset.png' } }
        end

        it 'gets HTTP status 201' do
          expect(last_response.status).to eq 201
        end

        it 'receives the newly created resource' do
          expect(json_body['data']['url']).to eq 'http://example.com/path/to/asset.png'
        end

        assets = MAIN_CONTAINER.relations[:assets]

        it 'adds a record in the database' do
          expect(assets.to_a.length).to eq 4
        end

        it 'gets the new resource location in the Location header' do
          expect(last_response.headers['Location'])
            .to eq "http://example.org/assets/#{assets.to_a.last[:locator]}"
        end
      end

      context 'with invalid parameters' do
        let(:params) { { name: '' } }

        it 'gets HTTP status 422' do
          expect(last_response.status).to eq 422
        end

        it 'receives the error details' do
          expect(json_body['error']['invalid_params'])
            .to eq 'url' => ['url is missing']
        end

        it 'does not create a record in the database' do
          expect(assets.to_a.length).to eq 3
        end
      end
    end

    describe 'PUT /assets/:id' do
      before { put "/assets/#{b.locator}", { data: params }, headers }

      context 'with valid parameters' do
        let(:params) do
          { mime_type: 'model/glft+json' }
        end

        it 'gets HTTP status 200' do
          expect(last_response.status).to eq 200
        end

        it 'receives the updated resource' do
          expect(json_body['data']['mime_type']).to eq(
            'model/glft+json'
          )
        end

        it 'updates the record in the database' do
          expect(asset_repo.by_id(b.id).mime_type).to eq(
            'model/glft+json'
          )
        end
      end

      context 'with invalid parameters' do
        let(:params) { { url: '' } }

        it 'gets HTTP status 422' do
          expect(last_response.status).to eq 422
        end

        it 'receives the error details' do
          expect(json_body['error']['invalid_params']).to eq(
            'url' =>
            ['url must be filled']
          )
        end

        it 'does not update a record in the database' do
          expect(asset_repo.by_id(b.id).url).to eq(
            b.url
          )
        end
      end
    end

    describe 'DELETE /assets/:id' do
      context 'with existing resource' do
        before { delete "/assets/#{b.locator}", nil, headers }
        it 'gets HTTP status 204' do
          expect(last_response.status).to eq 204
        end

        assets = MAIN_CONTAINER.relations[:assets]

        it 'deletes the asset from the database' do
          expect(assets.to_a.length).to eq 3
          expect(assets.where(deleted: false).to_a.length).to eq 2
        end
      end

      context 'with nonexisting resource' do
        it 'gets HTTP status 404' do
          delete '/assets/2345234', nil, headers
          expect(last_response.status).to eq 404
        end
      end
    end
  end

  context 'with invalid API Key' do
    describe 'GET /assets' do
      it 'returns HTTP status 401' do
        get '/assets'
        expect(last_response.status).to eq 401
      end
    end

    describe 'GET /assets/:id' do
      it 'returns HTTP status 401' do
        get "/assets/#{a.locator}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'POST /assets' do
      it 'returns HTTP status 401' do
        post '/assets'
        expect(last_response.status).to eq 401
      end
    end

    describe 'PUT /assets/:id' do
      it 'returns HTTP status 401' do
        put "/assets/#{a.locator}"
        expect(last_response.status).to eq 401
      end
    end

    describe 'DELETE /assets/:id' do
      it 'returns HTTP status 401' do
        delete "/assets/#{a.locator}"
        expect(last_response.status).to eq 401
      end
    end
  end
end
