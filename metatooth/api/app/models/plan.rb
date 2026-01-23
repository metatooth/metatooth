# frozen_string_literal: true

# A Plan model.
class Plan
  attr_reader :attributes

  def initialize(attributes)
    @attributes = attributes
  end

  def [](name)
    attributes[name]
  end

  def latest
    puts "PLAN ATTR #{attributes}"

    1
  end
end
