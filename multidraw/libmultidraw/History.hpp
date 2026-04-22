#ifndef LIBMULTIDRAW_HISTORY_HPP
#define LIBMULTIDRAW_HISTORY_HPP

#include <vector>

namespace multidraw {

  class Command;

  class History {
   public:
    std::vector<Command*> past;
    std::vector<Command*> future;
  };

}

#endif // LIBMULTIDRAW_HISTORY_HPP
