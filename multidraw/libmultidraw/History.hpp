#ifndef LIBMULTIDRAW_HISTORY_HPP
#define LIBMULTIDRAW_HISTORY_HPP

#include <memory>
#include <vector>

#include <libmultidraw/commands/Command.hpp>

namespace multidraw {

  class History {
   public:
    std::vector<std::unique_ptr<Command>> past;
    std::vector<std::unique_ptr<Command>> future;
  };

}

#endif // LIBMULTIDRAW_HISTORY_HPP
