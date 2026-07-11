#pragma once

#include <array>
#include <string>

namespace fabrique {

  /**
   * @brief Command to define a wafer: material, simulation width, and
   * crystal orientation (Miller indices).
   *
   * Usage: wafer [material] [width] [miller]
   *   e.g. wafer Si 500nm <1,1,1>
   */
  class WaferCmd {
  public:
    static const std::string DEFAULT_MATERIAL;
    static const std::array<int, 3> DEFAULT_MILLER;

    WaferCmd(const std::string& material = DEFAULT_MATERIAL,
             const std::string& width = "",
             const std::array<int, 3>& miller = DEFAULT_MILLER);

    /// Parse a wafer command from CLI arguments (excluding the "wafer" token itself).
    static WaferCmd parse(int argc, char* argv[]);

    void execute();

    const std::string& material() const { return _material; }
    const std::string& width() const { return _width; }
    const std::array<int, 3>& miller() const { return _miller; }

  private:
    std::string _material;
    std::string _width;
    std::array<int, 3> _miller;
  };

}
