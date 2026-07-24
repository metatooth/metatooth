#ifndef STL_EDITOR_HPP
#define STL_EDITOR_HPP

#include <libmultidraw/Editor.hpp>

using namespace multidraw;

/**
 * @brief Ties the window, the StlViewer, and the loaded model together.
 *
 * The Editor is the framework's controller. Constructing one with a file path
 * asks the Catalog to load the model; this subclass then builds the FLTK
 * window and the StlViewer that renders it.
 */
class StlEditor : public Editor {
public:
  explicit StlEditor(const std::string& initial_file);

  virtual Viewer* viewer(int id = 0) const { return (id == 0) ? _viewer : nullptr; }

private:
  Viewer* _viewer;
};

#endif // STL_EDITOR_HPP
