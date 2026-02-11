# frozen_string_literal: true

Factory.define(:order) do |f|
  f.association(:user)
  f.status { 'pending' }
end
