#pragma once

namespace fabrique {

  /**
   * @brief Parse and execute a single command given its name and arguments.
   *
   * `argv[0]` is the command name (e.g. "wafer"); `argv[1..argc-1]` are its
   * arguments, in the same form `WaferCmd::parse` expects. Throws
   * `std::invalid_argument` if `argc` is zero or the command name is
   * unrecognized, or whatever exception the command's own parsing raises.
   */
  void dispatch_command(int argc, char* argv[]);

}
