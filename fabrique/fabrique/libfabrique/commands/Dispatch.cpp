#include "Dispatch.hpp" // class implemented

#include <stdexcept>
#include <string>

#include <libfabrique/commands/WaferCmd.hpp>

using namespace fabrique;

void
fabrique::dispatch_command(int argc, char* argv[])
{
  if (argc < 1) {
    throw std::invalid_argument("no command given");
  }

  std::string name = argv[0];

  if (name == "wafer") {
    WaferCmd cmd = WaferCmd::parse(argc - 1, argv + 1);
    cmd.execute();
    return;
  }

  throw std::invalid_argument("unknown command: " + name);
}
