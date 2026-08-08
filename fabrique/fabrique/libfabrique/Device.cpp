#include "Device.hpp" // class implemented

void
fabrique::to_json(nlohmann::json& out, const Material& material)
{
  out = nlohmann::json{
    {"name", material.name},
    {"boundary", material.boundary}
  };
}

void
fabrique::to_json(nlohmann::json& out, const Device& device)
{
  out = nlohmann::json{
    {"width", device.width},
    {"height", device.height},
    {"materials", device.materials}
  };
}
