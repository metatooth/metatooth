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

  def each
    super do |row|
      yield Asset.new(row)
    end
  end
end
