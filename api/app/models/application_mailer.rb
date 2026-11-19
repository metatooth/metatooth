# frozen_string_literal: true

require 'erb'
require 'pony'

# A base class for e-mailing.
#
# Every delivery uses its own instance, so the values a template renders
# (+@user+, +@product+, ...) are never shared between concurrent requests.
class ApplicationMailer
  DEFAULT_FROM = 'noreply@metatooth.com'
  DEFAULT_DOMAIN = 'https://api.metatooth.com'
  VIEWS = File.expand_path('../views/mailers', __dir__)

  # Sender address for outgoing mail (override with MAIL_FROM).
  def self.from
    ENV.fetch('MAIL_FROM', DEFAULT_FROM)
  end

  # Canonical public origin used to build absolute links in e-mails
  # (override with PUBLIC_API_URL).
  def self.domain
    ENV.fetch('PUBLIC_API_URL', DEFAULT_DOMAIN).chomp('/')
  end

  # @param assigns [Hash] values exposed to the template as instance variables
  def initialize(**assigns)
    @domain = self.class.domain
    assigns.each { |name, value| instance_variable_set("@#{name}", value) }
  end

  def deliver(to:, subject:, template:)
    body = ERB.new(File.read("#{VIEWS}/#{template}.text.erb")).result(binding)
    Pony.mail(to: to, from: self.class.from, subject: subject, body: body)
  end
end
