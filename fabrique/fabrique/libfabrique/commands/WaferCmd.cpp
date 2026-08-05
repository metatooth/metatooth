#include "WaferCmd.hpp" // class implemented

#include <iostream>
#include <sstream>
#include <stdexcept>

#include <nlohmann/json.hpp>

#include <libfabrique/Device.hpp>

using namespace fabrique;

const std::string WaferCmd::DEFAULT_MATERIAL = "Si";
const std::array<int, 3> WaferCmd::DEFAULT_MILLER = {1, 1, 1};

namespace {

  std::array<int, 3> parse_miller(std::string token) {
    if (!token.empty() && token.front() == '<') {
      token.erase(0, 1);
    }
    if (!token.empty() && token.back() == '>') {
      token.pop_back();
    }

    std::array<int, 3> miller{};
    std::stringstream stream(token);
    std::string part;
    for (int i = 0; i < 3; ++i) {
      if (!std::getline(stream, part, ',')) {
        throw std::invalid_argument("miller indices must have the form <h,k,l>");
      }
      miller[i] = std::stoi(part);
    }
    return miller;
  }

}

WaferCmd::WaferCmd(const std::string& material,
                    double width_nm,
                    double height_nm,
                    const std::array<int, 3>& miller) :
  _material(material),
  _width_nm(width_nm),
  _height_nm(height_nm),
  _miller(miller)
{
}

double
WaferCmd::parse_length_nm(const std::string& token)
{
  std::size_t pos = 0;
  double value = std::stod(token, &pos);

  if (value < 0) {
    throw std::invalid_argument("length must be non-negative: " + token);
  }

  std::string unit = token.substr(pos);
  if (unit.empty() || unit == "nm") {
    return value;
  }
  if (unit == "um") {
    return value * 1000.0;
  }

  throw std::invalid_argument("unrecognized length unit: " + unit);
}

WaferCmd
WaferCmd::parse(int argc, char* argv[])
{
  if (argc < 3) {
    throw std::invalid_argument(
      "usage: wafer <material> <width> <height> [miller]");
  }

  std::string material = argv[0];
  double width_nm = parse_length_nm(argv[1]);
  double height_nm = parse_length_nm(argv[2]);
  std::array<int, 3> miller = DEFAULT_MILLER;

  if (argc > 3) {
    miller = parse_miller(argv[3]);
  }

  return WaferCmd(material, width_nm, height_nm, miller);
}

void
WaferCmd::execute()
{
  Device device;
  device.width = _width_nm;
  device.height = _height_nm;
  device.materials.push_back(Material{
    _material,
    {{0, 0, _width_nm, 0, _width_nm, _height_nm, 0, _height_nm}}
  });

  nlohmann::json out = device;
  std::cout << out.dump() << std::endl;
}
