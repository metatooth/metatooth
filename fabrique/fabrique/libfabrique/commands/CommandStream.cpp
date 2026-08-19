#include "CommandStream.hpp" // class implemented

#include <cctype>
#include <sstream>
#include <string>
#include <vector>

#include <libfabrique/commands/Dispatch.hpp>

using namespace fabrique;

namespace {

  bool is_blank_or_comment(const std::string& line) {
    for (char c : line) {
      if (std::isspace(static_cast<unsigned char>(c))) {
        continue;
      }
      return c == '#';
    }
    return true;
  }

}

void
fabrique::run_command_stream(std::istream& in)
{
  std::string line;
  while (std::getline(in, line)) {
    if (!line.empty() && line.back() == '\r') {
      line.pop_back();
    }
    if (is_blank_or_comment(line)) {
      continue;
    }

    std::istringstream tokens_stream(line);
    std::vector<std::string> tokens;
    std::string token;
    while (tokens_stream >> token) {
      tokens.push_back(token);
    }
    if (tokens.empty()) {
      continue;
    }

    std::vector<char*> argv;
    argv.reserve(tokens.size());
    for (auto& t : tokens) {
      argv.push_back(t.data());
    }

    dispatch_command(static_cast<int>(argv.size()), argv.data());
  }
}
