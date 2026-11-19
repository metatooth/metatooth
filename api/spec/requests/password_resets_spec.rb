# frozen_string_literal: true

require_relative '../spec_helper'

RSpec.describe 'PasswordResets', type: :request do
  let(:user) { Factory[:user] }
  let(:john) { User.new(user.to_h) }
  let(:user_repo) { UserRepo.new(MAIN_CONTAINER) }

  describe 'POST /password_resets' do
    context 'with valid parameters' do
      let(:params) do
        {
          data: {
            email: john[:email],
            reset_password_redirect_url: 'http://example.com'
          }
        }
      end
      before { post '/password_resets', params }

      it 'returns 204' do
        expect(last_response.status).to eq 204
      end

      it 'sets no Location header' do
        expect(last_response.headers['Location']).to be_nil
      end

      # Here we check that all the fields have properly been updated
      it 'adds the reset password attributes to "john"' do
        puts "john #{john.inspect}"
        expect(john[:reset_password_token]).to eq nil
        expect(john[:reset_password_sent_at]).to eq nil
        expect(john[:reset_password_redirect_url]).to eq nil

        updated = user_repo.by_id(john[:id])

        expect(updated[:reset_password_token]).to_not eq nil
        expect(updated[:reset_password_sent_at]).to_not eq nil
        expect(updated[:reset_password_redirect_url]).to eq 'http://example.com'
      end
    end

    context 'with invalid parameters' do
      let(:params) { { data: { email: john[:email] } } }
      before { post '/password_resets', params }
      it 'returns HTTP status 422' do
        expect(last_response.status).to eq 422
      end

      it 'lists the validation messages' do
        expect(json_body['error']['invalid_params']).to eq(
          ['reset_password_redirect_url must be present']
        )
      end
    end

    context 'with nonexistent user' do
      let(:params) { { data: { email: 'fake@example.com' } } }
      before { post '/password_resets', params }
      it 'returns HTTP status 404' do
        expect(last_response.status).to eq 404
      end
    end
  end

  describe 'GET /password_resets/:token' do
    context 'with existing user (valid token)' do
      subject { get "/password_resets/#{john[:reset_password_token]}" }
      context 'with redirect URL containing parameters' do
        let(:user) { Factory[:user, :reset_password] }
        let(:john) { User.new(user.to_h) }

        it 'redirects to "http://example.com?some=params&reset_token=TOKEN"' do
          token = john[:reset_password_token]
          expect(subject).to redirect_to(
            "http://example.com?some=params&reset_token=#{token}"
          )
        end
      end
      context 'with redirect URL not containing any parameters' do
        let(:user) { Factory[:user, :reset_password_no_params] }
        let(:john) { User.new(user.to_h) }

        it 'redirects to "http://example.com?reset_token=TOKEN"' do
          expect(subject).to redirect_to(
            "http://example.com?reset_token=#{john[:reset_password_token]}"
          )
        end
      end
    end
    context 'with nonexistent user' do
      before { get '/password_resets/123' }
      it 'returns HTTP status 404' do
        expect(last_response.status).to eq 404
      end
    end
    context 'with an expired token' do
      let(:user) do
        Factory[:user, :reset_password,
                reset_password_sent_at: DateTime.now - 3]
      end
      it 'returns HTTP status 404 and does not redirect' do
        get "/password_resets/#{user.reset_password_token}"
        expect(last_response.status).to eq 404
      end
    end
  end

  describe 'PUT /password_resets/:token' do
    context 'with existing user (valid token)' do
      let(:user) { Factory[:user, :reset_password] }
      let(:john) { User.new(user.to_h) }
      before do
        put "/password_resets/#{john[:reset_password_token]}", params
      end
      context 'with valid parameters' do
        let(:params) { { data: { password: 'new_password' } } }
        it 'returns HTTP status 204' do
          expect(last_response.status).to eq 204
        end
        it 'updates the password' do
          expect(user_repo.by_id(john[:id]).authenticate('new_password')).to eq true
        end
      end
      context 'with invalid parameters' do
        let(:params) { { data: { password: '' } } }
        it 'returns HTTP status 422' do
          expect(last_response.status).to eq 422
        end
        it 'lists the validation messages' do
          expect(json_body['error']['invalid_params']).to eq(
            ['password must be present']
          )
        end
      end
    end
    context 'with an expired token' do
      let(:user) do
        Factory[:user, :reset_password,
                reset_password_sent_at: DateTime.now - 3]
      end
      before do
        put "/password_resets/#{user.reset_password_token}",
            data: { password: 'new_password' }
      end
      it 'returns HTTP status 404' do
        expect(last_response.status).to eq 404
      end
      it 'leaves the password unchanged' do
        expect(user_repo.by_id(user.id).authenticate('new_password')).to eq false
        expect(user_repo.by_id(user.id).authenticate('password')).to eq true
      end
    end
    context 'with a token but no recorded send time' do
      let(:user) do
        Factory[:user, :reset_password, reset_password_sent_at: nil]
      end
      it 'returns HTTP status 404' do
        put "/password_resets/#{user.reset_password_token}",
            data: { password: 'new_password' }
        expect(last_response.status).to eq 404
      end
    end
    context 'with nonexistent user' do
      before do
        put '/password_resets/123', data: { password: 'password' }
      end
      it 'returns HTTP status 404' do
        expect(last_response.status).to eq 404
      end
    end
  end
  # 'PUT /password_resets/:token'

  describe PasswordReset do
    before { user }

    it 'never looks a user up with a nil or empty token' do
      [nil, ''].each do |token|
        reset = PasswordReset.new(password: 'x')
        reset.reset_token = token
        expect { reset.update }.to raise_error(StandardError)
      end
    end

    it 'accepts a token issued within the lifetime' do
      fresh = Factory[:user, :reset_password,
                      reset_password_sent_at: DateTime.now - Rational(1, 24)]
      reset = PasswordReset.new(reset_token: fresh.reset_password_token)
      expect(reset.user[:id]).to eq fresh.id
    end
  end
end
