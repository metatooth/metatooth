#include <iostream>
#include <filesystem>

#include <libmultidraw/components/Component.hpp>

#include "ExampleCatalog.hpp" // class implemented
#include "ExampleComponent.hpp"
#include "ExampleCreator.hpp"
  
namespace fs = std::filesystem;

ExampleCatalog::ExampleCatalog(const std::string& name, Creator* creator) :
  Catalog(name, creator)
{
}// constructor

bool
ExampleCatalog::retrieve(const fs::path& source, Component*& comp)
{
  std::cout << "Ready to retrieve " << source.string() << std::endl;

  ExampleCreator* creator = dynamic_cast<ExampleCreator*> (this->creator());
  
  comp = dynamic_cast<Component*> (creator->readSTL(source));
  
  return false;
}// retrieve
