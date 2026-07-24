#ifndef STL_CATALOG_HPP
#define STL_CATALOG_HPP

#include <filesystem>

#include <libmultidraw/Catalog.hpp>

using namespace multidraw;
namespace fs = std::filesystem;

/**
 * @brief Loads STL files into the domain model.
 *
 * The Catalog is the framework's persistence boundary. When an Editor is
 * opened, it asks the Catalog to retrieve() the Component for a given path;
 * here we hand that off to the StlCreator.
 */
class StlCatalog : public Catalog {
public:
  StlCatalog(const std::string& name, Creator* creator);

  virtual bool retrieve(const fs::path& source, Component*& comp);
};

#endif // STL_CATALOG_HPP
