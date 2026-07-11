#include <iostream>
#include <string>

#include <libfabrique/commands/WaferCmd.hpp>
#include <libfabrique/version.hpp>

int main(int argc, char* argv[]) {
  if (argc > 1 && std::string(argv[1]) == "wafer") {
    fabrique::WaferCmd cmd = fabrique::WaferCmd::parse(argc - 2, argv + 2);
    cmd.execute();
    return 0;
  }

  std::cout << "fabrique-" << __version__ << std::endl;
  return 0;
}
