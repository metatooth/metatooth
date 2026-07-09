#include <iostream>

#include <libfabrique/libfabrique.hpp>
#include <libfabrique/version.hpp>

int main() {
  libfabrique();
  std::cout << "libfabrique-" << __version__ << std::endl;
  return 0;
}
