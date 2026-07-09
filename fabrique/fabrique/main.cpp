#include <iostream>
#include <libfabrique/version.hpp>

int main(int argc, char* argv[]) {
  std::cout << "fabrique-" << __version__ << std::endl;
  return 0;
}
