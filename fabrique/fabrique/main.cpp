#include <exception>
#include <iostream>
#include <string>

#include <libfabrique/commands/WaferCmd.hpp>
#include <libfabrique/version.hpp>

int main(int argc, char* argv[]) {
  try {
    if (argc > 1 && std::string(argv[1]) == "wafer") {
      fabrique::WaferCmd cmd = fabrique::WaferCmd::parse(argc - 2, argv + 2);
      cmd.execute();
      return 0;
    }
  } catch (const std::exception& e) {
    std::cerr << e.what() << std::endl;
    return 1;
  }

  return 0;
}
