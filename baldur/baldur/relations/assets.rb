# frozen_string_literal: true

# An asset represents a single image.
class Assets < ROM::Relation[:sql]
  schema do
    attribute :id, Types::Integer
    attribute :title, Types::String
    attribute :year, Types::String
    attribute :media, Types::String
    attribute :width, Types::Float
    attribute :height, Types::Float
    attribute :path_to_img, Types::String
    attribute :image_url, Types::String
    attribute :thumbnail_url, Types::String
    attribute :s3_filename, Types::String
    attribute :s3_original, Types::String
    attribute :s3_500, Types::String
    attribute :s3_thumbnail, Types::String
    attribute :s3_150, Types::String
    attribute :s3_300, Types::String
    attribute :s3_thumb, Types::String
    attribute :s3_w300, Types::String
    attribute :s3_w500, Types::String
    attribute :weight, Types::Integer
    attribute :deleted, Types::Bool, required: true, default: false
    attribute :sold, Types::Bool, required: true, default: false
    attribute :series_id, Types::Integer

    primary_key :id
  end

  attr_accessor :temp_filename

  def s3_bucket
    "http://s3.amazonaws.com/#{ENV['S3_BUCKET_NAME']}/"
  end

  def url
    s3_bucket + s3_original if s3_original
  end

  def url_w500
    s3_bucket + s3_w500 if s3_w500
  end

  def url_w300
    s3_bucket + s3_w300 if s3_w300
  end

  def url_thumb
    s3_bucket + s3_thumb if s3_thumb
  end

  def short_description_text
    format('%<title>s. %<height>dx%<width>d. %<year>s',
           title:,
           height: height_in,
           width: width_in,
           year:)
  end

  def sold_string
    sold ? 'SOLD' : ''
  end

  def alt_text
    format('%<title>s. %<dim>s. %<media>s. %<year>s. %<sold>s',
           title:,
           dim:,
           media:,
           year:,
           sold: sold_string)
  end

  def title_html
    format('<em>%<title>s</em>', title:)
  end

  def text_html
    format('%<title>s, %<year>s. %<media>s. %<sold>s',
           title: title_html,
           year:,
           media:,
           sold: sold_string)
  end

  def title_year_html
    format('<em>%<title>s</em>, %<year>s.', title:, year:)
  end

  def dim
    format('%<height>d x %<width>d inches', height: height_in, width: width_in)
  end

  def width_in
    if width.nil?
      0
    else
      width / 25.4
    end
  end

  def height_in
    if height.nil?
      0
    else
      height / 25.4
    end
  end

  def price
    20 * (width_in + height_in)
  end

  def price_text
    "$#{format('%<price>.0f', price:)}"
  end
end
