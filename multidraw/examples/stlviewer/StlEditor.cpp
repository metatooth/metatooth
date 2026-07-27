#include "StlEditor.hpp" // class implemented
#include "StlViewer.hpp"

#include <FL/Fl_Window.H>

const int WIDTH = 800;
const int HEIGHT = 600;

StlEditor::StlEditor(const std::string& initial_file) :
  // The base Editor constructor uses the Catalog to load initial_file into a
  // Component before we get here; there is no output path for a viewer.
  Editor(initial_file, ""),
  _viewer(nullptr)
{
  Fl_Window* window = new Fl_Window(0, 0, WIDTH, HEIGHT, "Multidraw STL Viewer");

  _viewer = new StlViewer(0, 0, WIDTH, HEIGHT, this);

  window->end();
  window->resizable(_viewer);

  this->window(window);
}// constructor

StlEditor::~StlEditor()
{
  // The StlViewer was added as a child of the window, so deleting the window
  // also deletes the viewer; do not delete _viewer separately.
  delete window();
  window(nullptr);
  _viewer = nullptr;
}// destructor
