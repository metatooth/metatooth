#include <algorithm>
#include <cstdio>
#include <cstdlib>
#include <fstream>
#include <iostream>
#include <sstream>
#include <string>
#include <unistd.h>

#ifndef FABRIQUE_EXECUTABLE
#error "FABRIQUE_EXECUTABLE must be defined by the build"
#endif

namespace {

  int failures = 0;

  void check(bool condition, const std::string& message) {
    if (!condition) {
      std::cerr << "FAILED: " << message << std::endl;
      ++failures;
    }
  }

  std::string make_temp_path() {
    std::string path = "/tmp/fabrique_test_cli_XXXXXX";
    int fd = mkstemp(path.data());
    close(fd);
    return path;
  }

  std::string slurp(const std::string& path) {
    std::ifstream file(path);
    std::ostringstream out;
    out << file.rdbuf();
    return out.str();
  }

}

int main() {
  // A parse error (malformed Miller indices) must exit non-zero, emit no
  // JSON on stdout, and report the failure on stderr.
  {
    std::string stdout_path = make_temp_path();
    std::string stderr_path = make_temp_path();

    std::string command = std::string(FABRIQUE_EXECUTABLE) +
      " wafer Si 500nm 300nm '<1,1>' >" + stdout_path + " 2>" + stderr_path;
    int exit_status = std::system(command.c_str());

    check(exit_status != 0, "malformed miller indices should exit non-zero");
    check(slurp(stdout_path).empty(), "no JSON should be written to stdout on parse error");
    check(!slurp(stderr_path).empty(), "the parse error should be reported on stderr");

    std::remove(stdout_path.c_str());
    std::remove(stderr_path.c_str());
  }

  // `--file <path>` with a file of valid commands executes each in order.
  {
    std::string commands_path = make_temp_path();
    std::string stdout_path = make_temp_path();
    {
      std::ofstream commands(commands_path, std::ios::trunc);
      commands << "wafer Si 500nm 300nm <1,1,1>\n";
      commands << "wafer Ge 200nm 200nm <1,1,1>\n";
    }

    std::string command = std::string(FABRIQUE_EXECUTABLE) +
      " --file " + commands_path + " >" + stdout_path + " 2>/dev/null";
    int exit_status = std::system(command.c_str());

    std::string output = slurp(stdout_path);
    check(exit_status == 0, "--file with valid commands should exit 0");
    check(output.find("\"Si\"") != std::string::npos, "output should describe Si");
    check(output.find("\"Ge\"") != std::string::npos, "output should describe Ge");

    std::remove(commands_path.c_str());
    std::remove(stdout_path.c_str());
  }

  // `--file <path>` with a nonexistent file exits non-zero and emits no JSON.
  {
    std::string stdout_path = make_temp_path();
    std::string stderr_path = make_temp_path();

    std::string command = std::string(FABRIQUE_EXECUTABLE) +
      " --file /nonexistent/fabrique_test_path.txt >" + stdout_path +
      " 2>" + stderr_path;
    int exit_status = std::system(command.c_str());

    check(exit_status != 0, "--file with a nonexistent path should exit non-zero");
    check(slurp(stdout_path).empty(), "no JSON should be written to stdout");
    check(!slurp(stderr_path).empty(), "the error should be reported on stderr");

    std::remove(stdout_path.c_str());
    std::remove(stderr_path.c_str());
  }

  // Commands piped via stdin are executed in order.
  {
    std::string stdout_path = make_temp_path();

    std::string command = "printf 'wafer Si 500nm 300nm <1,1,1>\\n' | " +
      std::string(FABRIQUE_EXECUTABLE) + " >" + stdout_path + " 2>/dev/null";
    int exit_status = std::system(command.c_str());

    check(exit_status == 0, "piped stdin commands should exit 0");
    check(slurp(stdout_path).find("\"Si\"") != std::string::npos,
          "output should describe the piped Si command");

    std::remove(stdout_path.c_str());
  }

  // An argv command takes precedence over unrelated piped stdin content.
  {
    std::string stdout_path = make_temp_path();

    std::string command = "printf 'not a command\\n' | " +
      std::string(FABRIQUE_EXECUTABLE) +
      " wafer Si 500nm 300nm '<1,1,1>' >" + stdout_path + " 2>/dev/null";
    int exit_status = std::system(command.c_str());

    std::string output = slurp(stdout_path);
    check(exit_status == 0, "argv command should exit 0 regardless of piped stdin");
    check(output.find("\"Si\"") != std::string::npos,
          "output should reflect only the argv command");
    check(std::count(output.begin(), output.end(), '\n') == 1,
          "piped stdin content should not be read as additional commands");

    std::remove(stdout_path.c_str());
  }

  return failures == 0 ? 0 : 1;
}
