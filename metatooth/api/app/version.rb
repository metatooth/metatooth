# frozen_string_literal: true

# The version.
class Version
  def self.string
    commit = if ENV['HEROKU_SLUG_COMMIT']
               ENV['HEROKU_SLUG_COMMIT'][0..6]
             else
               '1974'
             end
    "9.#{commit}"
  end
end
