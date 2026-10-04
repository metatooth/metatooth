# frozen_string_literal: true

require 'erb'
require 'pony'

# A base class for e-mailing
class ApplicationMailer
  @from = 'noreply@metatooth.com'
  @layout = 'mailer'

  def self.mail(params)
    template = File.read("app/views/mailers/#{params[:template]}.text.erb")
    email_body = ERB.new(template).result(binding)
    Pony.mail(
      to: params[:to],
      from: @from,
      subject: params[:subject],
      body: email_body
    )
  end
end
