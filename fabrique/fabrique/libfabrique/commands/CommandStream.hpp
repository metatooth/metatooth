#pragma once

#include <istream>

namespace fabrique {

  /**
   * @brief Read commands from `in`, one per line, and execute each in order.
   *
   * Each non-blank line whose first non-whitespace character is not `#` is
   * split into whitespace-separated tokens and dispatched via
   * `dispatch_command`, in the same form as an `argv` command (the first
   * token is the command name). Blank lines and `#`-comment lines are
   * skipped. Execution stops and propagates the exception as soon as any
   * command fails to parse or execute; commands already executed are not
   * rolled back.
   */
  void run_command_stream(std::istream& in);

}
