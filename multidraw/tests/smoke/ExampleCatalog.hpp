#ifndef EXAMPLE_CATALOG_HPP
#define EXAMPLE_CATALOG_HPP

#include <filesystem>

#include <libmultidraw/Catalog.hpp>

using namespace multidraw;
namespace fs = std::filesystem;

class ExampleCatalog : public Catalog {
public:
  ExampleCatalog(const std::string& name, Creator* creator);

  virtual bool retrieve(const fs::path& source, Component*& comp);
};

#endif // EXAMPLE_CATALOG_HPP
