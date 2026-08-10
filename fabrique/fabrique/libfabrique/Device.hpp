#pragma once

#include <string>
#include <vector>

#include <nlohmann/json.hpp>

namespace fabrique {

  /// A single non-intersecting boundary polygon: a flat list of coordinates.
  using Polygon = std::vector<double>;

  /**
   * @brief A material present in a Device, with the one or more
   * non-intersecting boundary polygons it occupies.
   */
  struct Material {
    std::string name;
    std::vector<Polygon> boundary;
  };

  /**
   * @brief A semiconductor device: overall width/height (in nanometers,
   * fabrique's fixed system unit) and the materials present in it.
   *
   * Matches the shape of device.schema.json. There is no `units` member;
   * nanometers is fabrique's fixed system unit, not a per-device setting.
   */
  struct Device {
    double width = 0;
    double height = 0;
    std::vector<Material> materials;
  };

  void to_json(nlohmann::json& out, const Material& material);
  void to_json(nlohmann::json& out, const Device& device);

}
