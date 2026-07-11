#ifndef LIBMULTIDRAW_HISTORY_HPP
#define LIBMULTIDRAW_HISTORY_HPP

#include <vector>

namespace multidraw {

  class Command;

  class History {
   public:
    ~History();
    std::vector<Command*> past;
    std::vector<Command*> future;
  };

  inline History::~History() {
    for (Command* cmd : past) { delete cmd; }
    for (Command* cmd : future) { delete cmd; }
  }

}

#endif // LIBMULTIDRAW_HISTORY_HPP
