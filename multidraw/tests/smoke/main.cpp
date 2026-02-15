#include <iostream>

#include <libmultidraw/Multidraw.hpp>
#include <libmultidraw/version.hpp>

#include "ExampleCatalog.hpp"
#include "ExampleComponent.hpp"
#include "ExampleCreator.hpp"
#include "ExampleEditor.hpp"

using namespace multidraw;

int main() {
  Multidraw* multidraw = Multidraw::instance();
  multidraw->catalog(
    new ExampleCatalog("MultidrawSmokeTest", new ExampleCreator())
  );
                     
  ExampleEditor* editor = new ExampleEditor("./metatooth.stl");

  multidraw->open(editor);
  std::cout << "libmultidraw-" <<  __version__ << std::endl;
  
  multidraw->run();

  delete multidraw;

  return 0;
}
