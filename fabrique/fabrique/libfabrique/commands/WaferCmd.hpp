#pragma once

#include <array>
#include <string>

namespace fabrique {

  /**
   * @brief Command to define a wafer: material, simulation width/height,
   * and crystal orientation (Miller indices).
   *
   * Usage: wafer <material> <width> <height> [miller]
   *   e.g. wafer Si 500nm 300nm <1,1,1>
   *
   * `width`/`height` accept an optional unit suffix (e.g. `500nm`,
   * `0.5um`); a token with no suffix is assumed to already be in
   * nanometers, fabrique's fixed system unit. Executing the command
   * renders the resulting device as JSON to stdout.
   */
  class WaferCmd {
  public:
    static const std::string DEFAULT_MATERIAL;
    static const std::array<int, 3> DEFAULT_MILLER;

    WaferCmd(const std::string& material = DEFAULT_MATERIAL,
             double width_nm = 0,
             double height_nm = 0,
             const std::array<int, 3>& miller = DEFAULT_MILLER);

    /// Parse a wafer command from CLI arguments (excluding the "wafer" token itself).
    static WaferCmd parse(int argc, char* argv[]);

    /// Parse a width/height token, converting an optional unit suffix to nanometers.
    static double parse_length_nm(const std::string& token);

    void execute();

    const std::string& material() const { return _material; }
    double width() const { return _width_nm; }
    double height() const { return _height_nm; }
    const std::array<int, 3>& miller() const { return _miller; }

  private:
    std::string _material;
    double _width_nm;
    double _height_nm;
    std::array<int, 3> _miller;
  };

}
