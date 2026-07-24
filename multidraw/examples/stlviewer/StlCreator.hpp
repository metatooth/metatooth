#ifndef STL_CREATOR_HPP
#define STL_CREATOR_HPP

#include <filesystem>

#include <libmultidraw/Creator.hpp>

using namespace multidraw;
namespace fs = std::filesystem;

class StlComponent;

/**
 * @brief Builds StlComponents from STL files.
 *
 * Creator is the framework's BUILDER: the Catalog delegates the work of
 * turning a file on disk into a Component to a Creator. This one understands
 * both the ASCII and binary STL encodings.
 */
class StlCreator : public Creator {
public:
  StlCreator();

  /// Parse @p source and return a populated (normalized) StlComponent.
  StlComponent* readSTL(const fs::path& source);
};

#endif // STL_CREATOR_HPP
