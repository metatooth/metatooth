# frozen_string_literal: true

helpers do
  def http?(request)
    request.scheme == 'http' && request.port == 80
  end

  def https?(request)
    request.scheme == 'https' && request.port == 443
  end

  # Construct a link to +url_fragment+, which should be given relative to
  # the base of this Sinatra app.  The mode should be either
  # <code>:path_only</code>, which will generate an absolute path within
  # the current domain (the default), or <code>:full_url</code>, which will
  # include the site name and port number.  The latter is typically necessary
  # for links in RSS feeds.  Example usage:
  #
  #   link_to "/foo" # Returns "http://example.com/myapp/foo"
  #
  #--
  # Thanks to cypher23 on #mephisto and the folks on #rack for pointing me
  # in the right direction.
  #
  # Thanks to emk for this url_for segment.
  # I'm making link_to act a little more like rails link_to, but the url_for
  # which is emk's link_to works great for not creating the <a> tags.
  def url_for(url_fragment, mode = :path_only)
    case mode
    when :path_only
      base = request.script_name
    when :full_url
      port = http?(request) || https?(request) ? '' : ":#{request.port}"
      base = "#{request.scheme}://#{request.host}#{port}#{request.script_name}"
    else
      raise "Unknown script_url mode #{mode}"
    end
    "#{base}#{url_fragment}"
  end
end
