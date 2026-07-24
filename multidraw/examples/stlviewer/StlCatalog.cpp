#include "StlCatalog.hpp" // class implemented
#include "StlComponent.hpp"
#include "StlCreator.hpp"

#include <libmultidraw/components/Component.hpp>

StlCatalog::StlCatalog(const std::string& name, Creator* creator) :
  Catalog(name, creator)
{
}// constructor

bool
StlCatalog::retrieve(const fs::path& source, Component*& comp)
{
  StlCreator* creator = dynamic_cast<StlCreator*>(this->creator());
  if (creator == nullptr) {
    return false;
  }

  comp = dynamic_cast<Component*>(creator->readSTL(source));

  // Returning true tells the Editor a Component was produced, so it will be
  // installed as the model the Viewer renders.
  return comp != nullptr;
}// retrieve
