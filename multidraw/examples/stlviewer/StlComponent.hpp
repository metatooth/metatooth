#ifndef STL_COMPONENT_HPP
#define STL_COMPONENT_HPP

#include <array>
#include <vector>

#include <libmultidraw/components/Component.hpp>

using namespace multidraw;

/**
 * @brief The domain model for the STL viewer example.
 *
 * StlComponent is the "M" of the framework's model/view/controller
 * decomposition: it stores the triangle mesh loaded from an STL file and
 * knows how to render itself in 3D via draw3(). The framework's Viewer walks
 * the Component tree and calls draw3() on each node, so a domain model only
 * has to describe how it draws.
 */
class StlComponent : public Component {
public:
  /// A single triangular facet: a face normal and three corner vertices.
  struct Facet {
    std::array<float, 3> normal;
    std::array<std::array<float, 3>, 3> vertices;
  };

  explicit StlComponent(const std::string& name = "stl-model");

  /// Append a facet parsed from an STL file.
  void addFacet(const Facet& facet);

  /// How many facets make up this mesh?
  size_t facets() const { return _facets.size(); }

  /**
   * @brief Center the mesh on the origin and scale it to a common size.
   *
   * STL files use arbitrary units and origins. Normalizing once, after the
   * whole file is read, keeps the render code simple and frames any model in
   * the Viewer regardless of how it was authored. Also marks the component
   * visible so the framework will draw it.
   */
  void normalize();

  /// Render the mesh with immediate-mode OpenGL. Called by the Viewer.
  virtual void draw3() const;

private:
  std::vector<Facet> _facets;
};

#endif // STL_COMPONENT_HPP
