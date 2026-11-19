# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Authentication', type: :request do
  describe 'Client Authentication' do
    before { get '/products', nil, headers }

    context 'with invalid authentication scheme' do
      let(:headers) { { 'HTTP_AUTHORIZATION' => '' } }

      it 'gets HTTP status 401 Unauthorized' do
        expect(last_response.status).to eq 401
      end
    end

    context 'with valid authentication scheme' do
      let(:headers) do
        { 'HTTP_AUTHORIZATION' =>
          "Metaspace-Token api_key=#{key.id}:#{key.api_key}" }
      end

      context 'with invalid API Key' do
        let(:key) { Factory.structs[:api_key] }
        it 'gets HTTP status 401 Unauthorized' do
          expect(last_response.status).to eq 401
        end
      end

      context 'with disabled API Key' do
        let(:key) { Factory[:api_key, active: false] }
        it 'gets HTTP status 401 Unauthorized' do
          expect(last_response.status).to eq 401
        end
      end

      context 'with valid API Key' do
        let(:key) { Factory[:api_key] }
        it 'gets HTTP status 200' do
          expect(last_response.status).to eq 200
        end
      end
    end
  end
end
