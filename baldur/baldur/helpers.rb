# frozen_string_literal: true

# Helper static methods
module Helpers
  # Generate HAML and escape HTML by default.
  def haml(template, options = {}, locals = {})
    options[:escape_html] = true unless options.include?(:escape_html)
    super(template, options, locals)
  end

  # Render a partial and pass local variables.
  #
  # Example:
  #   != partial :games, :players => @players
  def partial(template, locals = {})
    haml(template, { layout: false }, locals)
  end

  # Paginate the query
  def paginate(query)
    @page     = (params[:page] || 1).to_i
    @per_page = (params[:per_page] || 4).to_i

    @pages       = query.chunks_of(@per_page)
    @total_count = @pages.count
    @page_count  = @pages.length

    @pages[@page - 1]
  end

  def pagination_links(uri)
    [%(<ul class="paginator">),
     intermediate_links(uri).join("\n"),
     '</ul>'].join
  end

  def intermediate_links(uri)
    (1..@page_count).map do |page|
      if @page == page
        sb = '['
        eb = ']'
      end
      "<li>#{sb}<a href=\"/#{uri}?page=#{page}\">#{page}</a>#{eb}</li>"
    end
  end

  # A not very good random string
  def poor_random
    (0...16).map { rand(97..122).chr }.join
  end

  # Resize and push to AWS S3. Returns the fkey
  def process(fname, sizing, ext, _attribute_sym)
    image = MiniMagick::Image.open(fname)
    image.resize(sizing)
    image.write(fname)

    fkey = poor_random + ext

    AWS::S3::S3Object.store(fkey, File.open(fname), ENV['S3_BUCKET_NAME'])

    fkey
  end

  # Push file to AWS S3.
  #
  # Returns the fkey
  def store_on_s3(tempfile, filename)
    ext = File.extname(filename)
    fkey = poor_random + ext

    AWS::S3::S3Object.store(fkey, tempfile, ENV['S3_BUCKET_NAME'])

    process(tempfile.path, '300x1000', ext, :s3_w300)
    process(tempfile.path, '500x2000', ext, :s3_w500)
    process(tempfile.path, '150x1000', ext, :s3_thumb)

    fkey
  end
end
