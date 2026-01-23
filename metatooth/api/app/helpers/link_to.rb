# frozen_string_literal: true

require_relative 'url_for'

helpers do
  def link_to(link_text, url, mode = :path_only)
    if url_for(url, mode)[0, 2] == '!!'
      trimmed_url = url_for(url, mode)[2..]
      "<a href=#{trimmed_url}> #{link_text}</a>"
    else
      "<a href=#{url_for(url, mode)}> #{link_text}</a>"
    end
  end
end
