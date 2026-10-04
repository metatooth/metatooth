# frozen_string_literal: true

# Validation of an asset
class AssetContract < Dry::Validation::Contract
  params do
    required(:url).filled(:string)
  end
end
