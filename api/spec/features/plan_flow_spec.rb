# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Plan Flow', type: :request do
  Pony.override_options = { via: :test }

  let(:key) { Factory[:api_key] }
  let(:key_str) { "#{key.id}:#{key.api_key}" }

  let(:headers) do
    { 'HTTP_AUTHORIZATION' => "Metaspace-Token api_key=#{key_str}" }
  end

  let(:params) do
    { name: 'Metatooth RSPec', location: 'http://ex.org/path/to/asset.txt' }
  end

  it 'creates a new plan and saves a 2nd revision' do
    # Step 1 - Create a user
    post '/plans', { data: params }, headers
    expect(last_response.status).to eq 201
    locator = json_body['data']['locator']

    params[:location] = 'http://ex.org/path/to/asset2.txt'

    # Step 2 - Create a new revision
    post "/plans/#{locator}/revisions", { data: params }, headers
    expect(last_response.status).to eq 201
  end
end
