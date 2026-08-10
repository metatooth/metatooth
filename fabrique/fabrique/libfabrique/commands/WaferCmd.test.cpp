#include <array>
#include <iostream>
#include <sstream>
#include <stdexcept>
#include <string>
#include <vector>

#include <nlohmann/json.hpp>

#include <libfabrique/commands/WaferCmd.hpp>

namespace {

  int failures = 0;

  void check(bool condition, const std::string& message) {
    if (!condition) {
      std::cerr << "FAILED: " << message << std::endl;
      ++failures;
    }
  }

  std::string capture_stdout(fabrique::WaferCmd& cmd) {
    std::ostringstream captured;
    std::streambuf* old = std::cout.rdbuf(captured.rdbuf());
    cmd.execute();
    std::cout.rdbuf(old);
    return captured.str();
  }

  char* to_argv(std::string& s) { return s.data(); }

}

int main() {
  using fabrique::WaferCmd;

  // Explicit width/height produce distinct values, in nanometers.
  {
    std::vector<std::string> tokens = {"Si", "500nm", "300nm", "<1,1,1>"};
    std::vector<char*> argv;
    for (auto& t : tokens) argv.push_back(to_argv(t));
    WaferCmd cmd = WaferCmd::parse(static_cast<int>(argv.size()), argv.data());

    check(cmd.width() == 500, "width should be 500");
    check(cmd.height() == 300, "height should be 300");

    nlohmann::json j = nlohmann::json::parse(capture_stdout(cmd));
    check(j.contains("width") && j["width"] == 500, "JSON width should be 500");
    check(j.contains("height") && j["height"] == 300, "JSON height should be 300");
    check(j.contains("materials") && j["materials"].size() == 1, "JSON should have one material");
    check(j.contains("materials") && !j["materials"].empty() &&
            j["materials"][0]["name"] == "Si",
          "material name should be Si");
    check(!j.contains("units"), "JSON should not contain a units property");
  }

  // Unitless tokens are assumed to already be nanometers.
  {
    std::vector<std::string> tokens = {"Si", "500", "300", "<1,1,1>"};
    std::vector<char*> argv;
    for (auto& t : tokens) argv.push_back(to_argv(t));
    WaferCmd cmd = WaferCmd::parse(static_cast<int>(argv.size()), argv.data());

    check(cmd.width() == 500, "unitless width should be treated as nanometers");
    check(cmd.height() == 300, "unitless height should be treated as nanometers");
  }

  // Non-nanometer units are converted to nanometers.
  {
    check(WaferCmd::parse_length_nm("0.5um") == 500, "0.5um should convert to 500nm");
  }

  // Negative lengths are rejected.
  {
    bool threw = false;
    try {
      WaferCmd::parse_length_nm("-1nm");
    } catch (const std::invalid_argument&) {
      threw = true;
    }
    check(threw, "negative length should throw std::invalid_argument");
  }

  // Missing width/height arguments raise an error and produce no JSON.
  {
    std::vector<std::string> tokens = {"Si", "500nm"};
    std::vector<char*> argv;
    for (auto& t : tokens) argv.push_back(to_argv(t));

    bool threw = false;
    try {
      WaferCmd::parse(static_cast<int>(argv.size()), argv.data());
    } catch (const std::invalid_argument&) {
      threw = true;
    }
    check(threw, "missing height argument should throw std::invalid_argument");
  }

  // Malformed Miller indices raise an error and produce no JSON.
  {
    std::vector<std::string> tokens = {"Si", "500nm", "300nm", "<1,1>"};
    std::vector<char*> argv;
    for (auto& t : tokens) argv.push_back(to_argv(t));

    bool threw = false;
    try {
      WaferCmd::parse(static_cast<int>(argv.size()), argv.data());
    } catch (const std::invalid_argument&) {
      threw = true;
    }
    check(threw, "malformed miller indices should throw std::invalid_argument");
  }

  return failures == 0 ? 0 : 1;
}
