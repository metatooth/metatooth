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

  return failures == 0 ? 0 : 1;
}
