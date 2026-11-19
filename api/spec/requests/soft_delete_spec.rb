# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'Soft deletion', type: :request do
  let(:key) { Factory[:api_key] }
  let(:key_str) { "#{key.id}:#{key.api_key}" }
  let(:token_repo) { AccessTokenRepo.new(MAIN_CONTAINER) }
  let(:user_repo) { UserRepo.new(MAIN_CONTAINER) }

  def headers_for(user)
    access = Factory[:access_token, api_key_id: key.id, user_id: user.id]
    token = token_repo.generate(access.id)
    { 'HTTP_AUTHORIZATION' =>
      "Metaspace-Token api_key=#{key_str}, access_token=#{user.id}:#{token}" }
  end

  describe 'users' do
    let(:manager) { Factory[:user, type: 'UserManager'] }
    let(:victim) { Factory[:user] }
    let!(:victim_access) do
      Factory[:access_token, api_key_id: key.id, user_id: victim.id]
    end
    let!(:victim_headers) do
      token = token_repo.generate(victim_access.id)
      { 'HTTP_AUTHORIZATION' =>
        "Metaspace-Token api_key=#{key_str}, access_token=#{victim.id}:#{token}" }
    end

    it 'accepts the victim token before deletion' do
      get "/users/#{victim.id}", nil, victim_headers
      expect(last_response.status).to eq 200
    end

    context 'after deletion' do
      before { user_repo.delete(victim.id) }

      it 'rejects the deleted user\'s existing access token' do
        get "/users/#{victim.id}", nil, victim_headers
        expect(last_response.status).to eq 401
      end

      it 'revokes the deleted user\'s access tokens' do
        expect(MAIN_CONTAINER.relations[:access_tokens]
          .where(user_id: victim.id, deleted: false).count).to eq 0
      end

      it 'does not look the user up by id, email or reset token' do
        expect { user_repo.by_id(victim.id) }
          .to raise_error(ROM::TupleCountMismatchError)
        expect { user_repo.by_email(victim.email) }
          .to raise_error(ROM::TupleCountMismatchError)
        expect { user_repo.by_token('123') }
          .to raise_error(ROM::TupleCountMismatchError)
      end

      it 'omits the user from GET /users' do
        get '/users', nil, headers_for(manager)
        ids = json_body['data'].map { |u| u['id'] }
        expect(ids).to include(manager.id)
        expect(ids).not_to include(victim.id)
      end

      it 'returns 404 for the user to a manager' do
        get "/users/#{victim.id}", nil, headers_for(manager)
        expect(last_response.status).to eq 404
      end

      it 'returns 404 for the user\'s address list to an admin' do
        admin = Factory[:user, type: 'Admin']
        get "/users/#{victim.id}/addresses", nil, headers_for(admin)
        expect(last_response.status).to eq 404
      end
    end
  end

  describe 'addresses' do
    let(:user) { Factory[:user] }
    let(:keep) { Factory[:address, user_id: user.id] }
    let(:gone) { Factory[:address, user_id: user.id] }

    before do
      keep
      gone
      AddressRepo.new(MAIN_CONTAINER).delete(gone.id)
    end

    it 'omits deleted addresses from the user\'s list' do
      get "/users/#{user.id}/addresses", nil, headers_for(user)
      expect(json_body['data'].map { |a| a['id'] }).to eq [keep.id]
    end

    it 'returns 404 for a deleted address' do
      headers = headers_for(user)
      get "/users/#{user.id}/addresses/#{gone.id}", nil, headers
      expect(last_response.status).to eq 404
      put "/users/#{user.id}/addresses/#{gone.id}",
          { data: { city: 'Nowhere' } }, headers
      expect(last_response.status).to eq 404
    end
  end

  describe 'orders' do
    let(:user) { Factory[:user] }
    let(:address) { Factory[:address, user_id: user.id] }
    let(:keep) do
      Factory[:order, user_id: user.id, bill_id: address.id, ship_id: address.id]
    end
    let(:gone) do
      Factory[:order, user_id: user.id, bill_id: address.id, ship_id: address.id]
    end

    before do
      keep
      gone
      OrderRepo.new(MAIN_CONTAINER).delete(gone.id)
    end

    it 'omits deleted orders from the list' do
      get '/orders', nil, headers_for(user)
      expect(json_body['data'].map { |o| o['id'] }).to eq [keep.id]
    end

    it 'returns 404 for a deleted order' do
      headers = headers_for(user)
      get "/orders/#{gone.id}", nil, headers
      expect(last_response.status).to eq 404
      put "/orders/#{gone.id}", { data: {} }, headers
      expect(last_response.status).to eq 404
    end
  end

  describe 'products' do
    let(:user) { Factory[:user] }
    let(:keep) { Factory[:product] }
    let(:gone) { Factory[:product] }

    before do
      keep
      gone
      ProductRepo.new(MAIN_CONTAINER).delete(gone.id)
    end

    it 'omits deleted products from the list' do
      get '/products', nil, headers_for(user)
      expect(json_body['data'].map { |p| p['id'] }).to eq [keep.id]
    end

    it 'returns 404 for a deleted product' do
      get "/products/#{gone.id}", nil, headers_for(user)
      expect(last_response.status).to eq 404
    end
  end

  describe 'plans, revisions and assets' do
    let(:headers) { headers_for(Factory[:user]) }
    let(:plan) { Factory[:plan] }
    let(:keep) { Factory[:revision, plan_id: plan.id, number: 0] }
    let(:gone) { Factory[:revision, plan_id: plan.id, number: 1] }

    before do
      keep
      gone
      RevisionRepo.new(MAIN_CONTAINER).delete(gone.id)
    end

    it 'omits deleted revisions from a plan\'s revisions' do
      get "/plans/#{plan.locator}/revisions", nil, headers
      expect(json_body['data'].map { |r| r['id'] }).to eq [keep.id]
    end

    it 'returns 404 for a deleted revision' do
      get "/plans/#{plan.locator}/revisions/#{gone.locator}", nil, headers
      expect(last_response.status).to eq 404
    end

    it 'never reuses a deleted revision\'s number' do
      expect(RevisionRepo.new(MAIN_CONTAINER).next_number(plan.id)).to eq 2
    end

    it 'returns 404 for a deleted plan and its revisions' do
      PlanRepo.new(MAIN_CONTAINER).delete(plan.id)
      get "/plans/#{plan.locator}/revisions", nil, headers
      expect(last_response.status).to eq 404
      get "/plans/#{plan.locator}", nil, headers
      expect(last_response.status).to eq 404
    end

    it 'returns 404 for a deleted asset and omits it from lookups' do
      asset = Factory[:asset]
      AssetRepo.new(MAIN_CONTAINER).delete(asset.id)
      get "/assets/#{asset.locator}", nil, headers
      expect(last_response.status).to eq 404
      expect { AssetRepo.new(MAIN_CONTAINER).by_locator(asset.locator) }
        .to raise_error(ROM::TupleCountMismatchError)
    end
  end
end
