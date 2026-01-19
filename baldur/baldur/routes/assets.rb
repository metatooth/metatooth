# frozen_string_literal: true

# Asset routes - registered with App
class App
  get '/assets/:id' do
    assets_rel = MAIN_CONTAINER.relations[:assets]
    @asset = assets_rel.where(id: params[:id].to_i).first
    redirect '/assets' if @asset.nil?
    @full_url = "http://www.laramirandagoodman.com/paintings/view/#{@asset.id}"
    assets = assets_rel.where(deleted: false).order(:weight).to_a
    assets.each_with_index do |a, index|
      next unless a.id == @asset.id

      @prev_id = assets[index - 1].id if index.positive? && assets[index - 1]

      @next_id = assets[index + 1].id if (index < assets.size) && assets[index + 1]
    end

    if params[:edit] == 'on'
      @series = MAIN_CONTAINER.relations[:series].order(:name).reverse.to_a
      haml :asset_form
    else
      haml :asset
    end
  end

  post '/assets/:id' do
    assets_rel = MAIN_CONTAINER.relations[:assets]
    asset = assets_rel.where(id: params[:id].to_i).first
    if asset
      assets_rel.where(id: params[:id].to_i).update(
        title: params[:title],
        year: params[:year],
        media: params[:media],
        width: params[:width],
        height: params[:height],
        weight: params[:weight],
        deleted: params[:deleted] == 'on',
        sold: params[:sold] == 'on',
        series_id: params[:series_id]
      )
    end
    redirect "/assets/#{params[:id]}", 301
  end
end
