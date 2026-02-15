#ifndef _HISTORY_H
#define _HISTORY_H

#include <vector>

class Command;

class History {
 public:
  std::vector<Command*> past;
  std::vector<Command*> future;
};

#endif // _HISTORY_H
