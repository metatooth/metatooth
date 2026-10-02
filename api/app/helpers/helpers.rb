# frozen_string_literal: true

helpers do
  def http?(request)
    request.scheme == 'http' && request.port == 80
  end

  def https?(request)
    request.scheme == 'https' && request.port == 443
  end

  def http_or_https?(request)
    http?(request) || https?(request)
  end
end
