#include <iostream>
#include <filesystem>

#include <libmultidraw/components/Component.hpp>

#include "ExampleCatalog.hpp"  // class implemented
#include "ExampleComponent.hpp"
#include "ExampleCreator.hpp"

namespace fs = std::filesystem;

ExampleCatalog::ExampleCatalog(const std::string& name, Creator* creator) : Catalog(name, creator)
{
}  // constructor

bool
ExampleCatalog::retrieve(const fs::path& source, Component*& comp)
{
  std::cout << "Ready to retrieve " << source.string() << std::endl;

  comp = dynamic_cast<Component*>(ExampleCreator::read_stl(source));

  return false;
}  // retrieve
