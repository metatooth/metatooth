#include <iostream>
#include <string>

#include <libmultidraw/Multidraw.hpp>
#include <libmultidraw/version.hpp>

#include "StlCatalog.hpp"
#include "StlCreator.hpp"
#include "StlEditor.hpp"

using namespace multidraw;

// A cube ships next to the example so it runs with no arguments.
const char* DEFAULT_MODEL = "cube.stl";

int main(int argc, char** argv)
{
  std::string path = (argc > 1) ? argv[1] : DEFAULT_MODEL;

  std::cout << "libmultidraw-" << multidraw_version << std::endl;
  std::cout << "stlviewer: opening " << path << std::endl;
  std::cout << "  drag to rotate, wheel to zoom, 'r' to reset, 'q' to quit"
            << std::endl;

  // Wire up the framework: a Catalog that reads STL (via its Creator), then an
  // Editor for the requested file. Opening the Editor shows its window; run()
  // drives the FLTK event loop until the viewer quits.
  Multidraw* multidraw = Multidraw::instance();
  multidraw->catalog(new StlCatalog("MultidrawStlViewer", new StlCreator()));

  StlEditor* editor = new StlEditor(path);
  multidraw->open(editor);

  multidraw->run();

  delete multidraw;

  return 0;
}
