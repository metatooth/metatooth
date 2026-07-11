#include "WaferCmd.hpp" // class implemented

#include <iostream>
#include <sstream>
#include <stdexcept>

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
                    const std::string& width,
                    const std::array<int, 3>& miller) :
  _material(material),
  _width(width),
  _miller(miller)
{
}

WaferCmd
WaferCmd::parse(int argc, char* argv[])
{
  std::string material = DEFAULT_MATERIAL;
  std::string width;
  std::array<int, 3> miller = DEFAULT_MILLER;

  if (argc > 0) {
    material = argv[0];
  }
  if (argc > 1) {
    width = argv[1];
  }
  if (argc > 2) {
    miller = parse_miller(argv[2]);
  }

  return WaferCmd(material, width, miller);
}

void
WaferCmd::execute()
{
  std::cout << "wafer material=" << _material
            << " width=" << _width
            << " miller=<" << _miller[0] << "," << _miller[1] << "," << _miller[2] << ">"
            << std::endl;
}
