#include <exception>
#include <fstream>
#include <iostream>
#include <stdexcept>
#include <string>
#include <unistd.h>

#include <libfabrique/commands/CommandStream.hpp>
#include <libfabrique/commands/Dispatch.hpp>
#include <libfabrique/version.hpp>

namespace {

  bool is_flag(const std::string& arg) {
    return arg == "--file" || arg == "-f";
  }

}

int main(int argc, char* argv[]) {
  try {
    // A command given directly on argv (e.g. `fabrique wafer ...`) takes
    // precedence over `--file`/stdin batch input and runs exactly that one
    // command, unchanged from prior single-command behavior.
    if (argc > 1 && !is_flag(argv[1])) {
      fabrique::dispatch_command(argc - 1, argv + 1);
      return 0;
    }

    if (argc > 1 && is_flag(argv[1])) {
      if (argc < 3) {
        throw std::invalid_argument("usage: fabrique --file <path>");
      }

      std::ifstream file(argv[2]);
      if (!file) {
        throw std::invalid_argument("cannot open file: " + std::string(argv[2]));
      }

      fabrique::run_command_stream(file);
      return 0;
    }

    // No command and no `--file`: read batch commands from stdin only if
    // it has been redirected/piped (not an interactive terminal), so
    // running `fabrique` at a bare prompt keeps its prior no-op behavior.
    if (!isatty(STDIN_FILENO)) {
      fabrique::run_command_stream(std::cin);
      return 0;
    }
  } catch (const std::exception& e) {
    std::cerr << e.what() << std::endl;
    return 1;
  }

  return 0;
}
