require 'sinatra/base'
require './event'

class App < Sinatra::Base

  get '/' do
    redirect to('/1')
  end

  get '/favicon.ico' do
    return ""
  end

  get '/:id' do
    @events = Event.make

    @sets = (@events.size / 2).enum_for(:times).collect{|x| x + 1}

    e1 = nil
    e2 = nil

    @eid = params[:id].to_i

    @events.each do |e|
      if e[0] == params[:id] and e1.nil?
        e1 = e
      elsif e[0] == params[:id] and e2.nil?
        e2 = e
      end
    end

    @initial = Date.strptime(e1[1], "%Y-%m-%d")
    @next = Date.strptime(e2[1], "%Y-%m-%d")
    now = Date.today()
    final = @next + (@next - @initial)
    days_remaining = (final - now).to_i

    @initial_url = e1[2]
    @next_url = e2[2]

    now_string = now.strftime("%Y-%m-%d")
    final_string = final.strftime("%Y-%m-%d")

    @description = "Today is #{now_string}. "

    if days_remaining > 0
      @description << "Until #{final_string}, we are closer in time to the monument than the monument is to the event it commemerates. "
      @description << "This will be true for #{days_remaining} days."
    elsif days_remaining < 0
      @description << "Since #{final_string}, the monument is closer in time to the event it commemerates than we are to the monument. "

      days_since = (now - final).to_i

      @description << "This has been true for #{days_since} days."
    else
      @description << "WE ARE EQUITEMPORAL BETWEEN THE TWO EVENTS!"
    end

    haml :index
  end

end
