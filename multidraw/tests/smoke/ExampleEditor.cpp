#include "ExampleEditor.hpp" // class implemented
#include "ExampleViewer.hpp"

#include <FL/Fl_Window.H>

const float WIDTH = 800;
const float HEIGHT = 600;

ExampleEditor::ExampleEditor(const std::string& initial_file) :
  Editor(initial_file, "")
{
  Fl_Window* window = new Fl_Window(0, 0, WIDTH, HEIGHT, "MultidrawSmokeTest");

  _viewer = new ExampleViewer(0, 0, WIDTH, HEIGHT, this);

  window->end();
  
  window->resizable(_viewer);
  
  this->window(window);
}// constructor
 
