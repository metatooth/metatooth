#ifndef EXAMPLE_EDITOR_HPP
#define EXAMPLE_EDITOR_HPP

#include <libmultidraw/Editor.hpp>

using namespace multidraw;

class ExampleEditor : public Editor {
public:
  ExampleEditor(const std::string& initial_file);

  virtual Viewer* viewer(int index) const { return _viewer; };
  
private:
  Viewer* _viewer;
};

#endif // EXAMPLE_EDITOR_HPP
