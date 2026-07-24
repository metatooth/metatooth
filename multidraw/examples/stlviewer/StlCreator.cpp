#include "StlCreator.hpp" // class implemented
#include "StlComponent.hpp"

#include <cstdint>
#include <fstream>
#include <iostream>
#include <sstream>
#include <string>

namespace {

  // A binary STL is an 80-byte header, a 4-byte little-endian facet count,
  // then 50 bytes per facet. If the file size matches that formula exactly it
  // is binary; otherwise treat it as ASCII. This is more reliable than testing
  // for a leading "solid", which binary files may also contain.
  const std::streamoff HEADER_BYTES = 80;
  const std::streamoff COUNT_BYTES = 4;
  const std::streamoff FACET_BYTES = 50;

  bool looksBinary(std::ifstream& file, std::streamoff size)
  {
    if (size < HEADER_BYTES + COUNT_BYTES) {
      return false;
    }

    file.seekg(HEADER_BYTES, std::ios::beg);
    std::uint32_t count = 0;
    file.read(reinterpret_cast<char*>(&count), sizeof(count));
    file.seekg(0, std::ios::beg);

    return size == HEADER_BYTES + COUNT_BYTES +
      static_cast<std::streamoff>(count) * FACET_BYTES;
  }

  void readBinary(std::ifstream& file, StlComponent* comp)
  {
    file.seekg(HEADER_BYTES, std::ios::beg);

    std::uint32_t count = 0;
    file.read(reinterpret_cast<char*>(&count), sizeof(count));

    for (std::uint32_t i = 0; i < count; ++i) {
      StlComponent::Facet facet;

      // 12 little-endian floats: normal (3) followed by three vertices (3x3).
      file.read(reinterpret_cast<char*>(facet.normal.data()), 3 * sizeof(float));
      for (auto& vertex : facet.vertices) {
        file.read(reinterpret_cast<char*>(vertex.data()), 3 * sizeof(float));
      }

      // Skip the 2-byte "attribute byte count".
      file.seekg(2, std::ios::cur);

      if (!file) {
        break;
      }
      comp->addFacet(facet);
    }
  }

  void readAscii(std::ifstream& file, StlComponent* comp)
  {
    file.seekg(0, std::ios::beg);

    StlComponent::Facet facet;
    int vertex = 0;
    std::string token;

    while (file >> token) {
      if (token == "normal") {
        file >> facet.normal[0] >> facet.normal[1] >> facet.normal[2];
      } else if (token == "vertex") {
        if (vertex < 3) {
          file >> facet.vertices[vertex][0]
               >> facet.vertices[vertex][1]
               >> facet.vertices[vertex][2];
          ++vertex;
        }
      } else if (token == "endfacet") {
        if (vertex == 3) {
          comp->addFacet(facet);
        }
        vertex = 0;
      }
    }
  }

}// anonymous namespace

StlCreator::StlCreator()
{
}// constructor

StlComponent*
StlCreator::readSTL(const fs::path& source)
{
  StlComponent* comp = new StlComponent(source.filename().string());

  std::ifstream file(source, std::ios::binary);
  if (!file) {
    std::cerr << "stlviewer: could not open " << source.string() << std::endl;
    comp->normalize();
    return comp;
  }

  file.seekg(0, std::ios::end);
  std::streamoff size = file.tellg();
  file.seekg(0, std::ios::beg);

  if (looksBinary(file, size)) {
    readBinary(file, comp);
  } else {
    readAscii(file, comp);
  }

  std::cout << "stlviewer: loaded " << comp->facets() << " facets from "
            << source.string() << std::endl;

  comp->normalize();
  return comp;
}// readSTL
