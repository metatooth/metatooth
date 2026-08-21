#include "ExampleCreator.hpp"  // class implemented
#include "ExampleComponent.hpp"

ExampleCreator::ExampleCreator()
{
}  // constructor

ExampleComponent*
ExampleCreator::read_stl(const fs::path& source)
{
  ExampleComponent* result = new ExampleComponent();

  return result;
}  // read_stl
