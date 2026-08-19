#include <iostream>
#include <sstream>
#include <stdexcept>
#include <string>
#include <vector>

#include <nlohmann/json.hpp>

#include <libfabrique/commands/Dispatch.hpp>

namespace {

  int failures = 0;

  void check(bool condition, const std::string& message) {
    if (!condition) {
      std::cerr << "FAILED: " << message << std::endl;
      ++failures;
    }
  }

  char* to_argv(std::string& token) { return token.data(); }

  std::string capture_stdout_dispatch(std::vector<std::string>& tokens) {
    std::vector<char*> argv;
    argv.reserve(tokens.size());
    for (auto& token : tokens) {
      argv.push_back(to_argv(token));
    }

    std::ostringstream captured;
    std::streambuf* old = std::cout.rdbuf(captured.rdbuf());
    fabrique::dispatch_command(static_cast<int>(argv.size()), argv.data());
    std::cout.rdbuf(old);
    return captured.str();
  }

}

int main() {
  // A known command name is dispatched to its handler, which writes JSON.
  {
    std::vector<std::string> tokens = {"wafer", "Si", "500nm", "300nm", "<1,1,1>"};
    std::string output = capture_stdout_dispatch(tokens);

    nlohmann::json j = nlohmann::json::parse(output);
    check(j.contains("width") && j["width"] == 500, "dispatched wafer command should emit width");
    check(j.contains("materials") && !j["materials"].empty() &&
            j["materials"][0]["name"] == "Si",
          "dispatched wafer command should emit the Si material");
  }

  // An unrecognized command name raises std::invalid_argument.
  {
    std::vector<std::string> tokens = {"not-a-real-command", "foo"};
    std::vector<char*> argv;
    for (auto& token : tokens) argv.push_back(to_argv(token));

    bool threw = false;
    try {
      fabrique::dispatch_command(static_cast<int>(argv.size()), argv.data());
    } catch (const std::invalid_argument&) {
      threw = true;
    }
    check(threw, "unknown command should throw std::invalid_argument");
  }

  // No tokens at all (argc == 0) raises std::invalid_argument.
  {
    bool threw = false;
    try {
      fabrique::dispatch_command(0, nullptr);
    } catch (const std::invalid_argument&) {
      threw = true;
    }
    check(threw, "empty command should throw std::invalid_argument");
  }

  // A malformed argument for a known command propagates that command's error.
  {
    std::vector<std::string> tokens = {"wafer", "Si", "500nm", "300nm", "<1,1>"};
    std::vector<char*> argv;
    for (auto& token : tokens) argv.push_back(to_argv(token));

    bool threw = false;
    try {
      fabrique::dispatch_command(static_cast<int>(argv.size()), argv.data());
    } catch (const std::invalid_argument&) {
      threw = true;
    }
    check(threw, "malformed wafer arguments should propagate std::invalid_argument");
  }

  return failures == 0 ? 0 : 1;
}
