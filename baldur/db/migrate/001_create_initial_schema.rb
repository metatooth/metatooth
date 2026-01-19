Sequel.migration do
  change do
    create_table :series do
      primary_key :id
      String :name
      Text :description
    end

    create_table :settings do
      primary_key :id
      String :name
      String :value
    end

    create_table :assets do
      primary_key :id
      String :title
      String :year
      String :media
      Float :width
      Float :height
      String :s3_original
      String :s3_thumb
      String :s3_w300
      String :s3_w500
      Integer :weight
      TrueClass :deleted, default: false
      TrueClass :sold, default: false
      Integer :series_id
    end
  end
end
