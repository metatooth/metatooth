# frozen_string_literal: true

require './baldur/helpers'

# A single application
class App < Sinatra::Base
  use Rack::Session::Cookie, key: 'rack.session', secret: ENV['RACK_SECRET']
  enable :methodoverride
  helpers Helpers

  set :logging, Logger::DEBUG

  get '/gallery' do
    @series = MAIN_CONTAINER.relations[:series].reverse(:id).to_a
    haml :gallery
  end

  get '/slideshow' do
    @assets = MAIN_CONTAINER.relations[:assets].where(deleted: false).order(:weight).to_a
    haml :slideshow
  end

  get '/pricelist' do
    @assets = MAIN_CONTAINER.relations[:assets].where(deleted: false, sold: false).order(:weight).to_a
    haml :pricelist
  end

  get '/' do
    @home = '1'
    @full_url = 'https://www.laramirandagoodman.com'
    @series = MAIN_CONTAINER.relations[:series].reverse(:id).to_a
    @assets = MAIN_CONTAINER.relations[:assets].where(deleted: false).to_a
    haml :home
  end

  get '/css/:stylesheet.css' do
    content_type 'text/css', charset: 'UTF-8'
    sass :"css/#{params[:stylesheet]}"
  end

  get '/paintings' do
    @uri = 'paintings'
    @assets = MAIN_CONTAINER.relations[:assets].where(deleted: false).order(:weight).to_a
    haml :paintings
  end

  get '/paintings/:id' do
    asset = MAIN_CONTAINER.relations[:assets].where(id: params[:id]).first
    if !asset.nil?
      @assets = [asset]
      @uri = asset.title
      haml :asset, {}, { asset: }
    else
      halt 400
    end
  end

  get '/CV' do
    @assets = []
    haml :cv
  end

  get '/contact' do
    @assets = []
    @address = 'artist@laramirandagoodman.com'
    haml :contact
  end

  get '/upload' do
    @assets = []
    haml :upload
  end

  post '/upload' do
    if params['password'] == ENV['UPLOAD_PASSWORD']
      series_rel = MAIN_CONTAINER.relations[:series]
      series = series_rel.where(name: params[:series]).first
      series ||= series_rel.insert(name: params[:series]).first

      assets_rel = MAIN_CONTAINER.relations[:assets]
      heaviest = assets_rel.where(deleted: false).order(:weight).reverse.first
      weight = heaviest.weight unless heaviest.nil?
      weight ||= 0

      asset = assets_rel.insert(title: params[:title],
                                year: params[:year],
                                media: params[:media],
                                width: params[:width].to_i * 25.4,
                                height: params[:height].to_i * 25.4,
                                series_id: series.id,
                                weight: weight + 10,
                                deleted: false,
                                sold: false).first

      s3_original = store_on_s3(params['myfile'][:tempfile],
                                params['myfile'][:filename])
      assets_rel.where(id: asset.id).update(s3_original: s3_original)
    end

    @assets = []
    haml :upload
  end
end
