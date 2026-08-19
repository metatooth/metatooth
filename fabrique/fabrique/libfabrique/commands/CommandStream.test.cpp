#include <iostream>
#include <sstream>
#include <stdexcept>
#include <string>

#include <libfabrique/commands/CommandStream.hpp>

namespace {

  int failures = 0;

  void check(bool condition, const std::string& message) {
    if (!condition) {
      std::cerr << "FAILED: " << message << std::endl;
      ++failures;
    }
  }

  std::string capture_stdout_run(const std::string& input) {
    std::istringstream in(input);
    std::ostringstream captured;
    std::streambuf* old = std::cout.rdbuf(captured.rdbuf());
    fabrique::run_command_stream(in);
    std::cout.rdbuf(old);
    return captured.str();
  }

  std::size_t count_lines(const std::string& text) {
    std::size_t count = 0;
    std::size_t pos = 0;
    while ((pos = text.find('\n', pos)) != std::string::npos) {
      ++count;
      ++pos;
    }
    return count;
  }

}

int main() {
  // Multiple valid commands produce one JSON document per command, in order.
  {
    std::string output = capture_stdout_run(
      "wafer Si 500nm 300nm <1,1,1>\n"
      "wafer Ge 200nm 200nm <1,1,1>\n");

    check(count_lines(output) == 2, "expected two JSON documents on stdout");

    std::size_t first_newline = output.find('\n');
    std::string first = output.substr(0, first_newline);
    std::string rest = output.substr(first_newline + 1);

    check(first.find("\"Si\"") != std::string::npos,
          "first document should describe Si");
    check(rest.find("\"Ge\"") != std::string::npos,
          "second document should describe Ge");
  }

  // Blank lines and `#` comments are ignored, not treated as commands.
  {
    std::string output = capture_stdout_run(
      "\n"
      "# a comment\n"
      "wafer Si 500nm 300nm <1,1,1>\n"
      "   \n");

    check(count_lines(output) == 1,
          "blank/comment lines should not produce output or errors");
  }

  // A malformed command stops processing; earlier commands already ran.
  {
    std::istringstream in(
      "wafer Si 500nm 300nm <1,1,1>\n"
      "wafer Ge 200nm <1,1>\n"
      "wafer Al 100nm 100nm <1,1,1>\n");
    std::ostringstream captured;
    std::streambuf* old = std::cout.rdbuf(captured.rdbuf());

    bool threw = false;
    try {
      fabrique::run_command_stream(in);
    } catch (const std::exception&) {
      threw = true;
    }
    std::cout.rdbuf(old);

    check(threw, "malformed command should propagate an exception");
    check(captured.str().find("\"Si\"") != std::string::npos,
          "the command before the malformed one should have executed");
    check(captured.str().find("\"Al\"") == std::string::npos,
          "the command after the malformed one should not have executed");
  }

  // An unknown command name is reported the same way a malformed one is.
  {
    std::istringstream in("not-a-real-command foo\n");
    bool threw = false;
    try {
      fabrique::run_command_stream(in);
    } catch (const std::invalid_argument&) {
      threw = true;
    }
    check(threw, "unknown command should throw std::invalid_argument");
  }

  return failures == 0 ? 0 : 1;
}
