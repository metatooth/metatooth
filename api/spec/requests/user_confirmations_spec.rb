# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'UserConfirmations', type: :request do
  describe 'GET /user_confirmations/:token' do
    context 'with existing token' do
      context 'with confirmation redirect url' do
        subject { get "/user_confirmations/#{john.confirmation_token}" }
        let(:john) { Factory[:user, :confirmation_redirect_url] }
        it 'redirects to http://google.com' do
          expect(subject).to redirect_to('http://google.com')
        end
      end
      context 'without confirmation redirect url' do
        let(:john) { Factory[:user, :confirmation_no_redirect_url] }
        before { get "/user_confirmations/#{john.confirmation_token}" }
        it 'returns HTTP status 200' do
          expect(last_response.status).to eq 200
        end
        it 'renders "You are now confirmed!"' do
          expect(last_response.body).to eq 'You are now confirmed!'
        end
      end
    end
    context 'with nonexistent token' do
      before { get '/user_confirmations/fake' }
      it 'returns HTTP status 404' do
        expect(last_response.status).to eq 404
      end
      it 'renders "Token not found"' do
        expect(last_response.body).to eq 'Token not found'
      end
    end
  end
end
