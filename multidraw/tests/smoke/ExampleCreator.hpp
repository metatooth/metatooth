#ifndef EXAMPLE_CREATOR_HPP
#define EXAMPLE_CREATOR_HPP

#include <filesystem>

#include <libmultidraw/Creator.hpp>

using namespace multidraw;
namespace fs = std::filesystem;

class ExampleComponent;

class ExampleCreator : public Creator {
public:
  ExampleCreator();

  ExampleComponent* readSTL(const fs::path& source);
};


#endif // EXAMPLE_CREATOR_HPP
