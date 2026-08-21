#include <filesystem>
#include <iostream>
#include <string>

#include <libmultidraw/Multidraw.hpp>
#include <libmultidraw/version.hpp>

#include "StlCatalog.hpp"
#include "StlCreator.hpp"
#include "StlEditor.hpp"

using namespace multidraw;
namespace fs = std::filesystem;

// The build copies this sample model next to the binary so the example runs
// with no arguments.
const char* default_model = "cube.stl";

int
main(int argc, char** argv)
{
  // Resolve the default relative to the binary's own directory (from argv[0]),
  // not the current working directory, so `./path/to/stlviewer` finds the
  // bundled model no matter where it is launched from.
  std::string path;
  if (argc > 1) {
    path = argv[1];
  } else {
    path = (fs::path(argv[0]).parent_path() / default_model).string();
  }

  std::cout << "libmultidraw-" << multidraw_version << std::endl;
  std::cout << "stlviewer: opening " << path << std::endl;
  std::cout << "  drag to rotate, wheel to zoom, 'r' to reset, 'q' to quit" << std::endl;

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
