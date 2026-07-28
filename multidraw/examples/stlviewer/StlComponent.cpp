#include "StlComponent.hpp" // class implemented

#include <FL/gl.h>

#include <algorithm>
#include <limits>

// Largest dimension, in world units, that a normalized model spans. The
// Viewer's default zoom frames a model of roughly this size.
const float TARGET_SIZE = 100.0F;

StlComponent::StlComponent(const std::string& name) : Component(name)
{
}// constructor

void
StlComponent::addFacet(const Facet& facet)
{
  _facets.push_back(facet);
}// addFacet

void
StlComponent::normalize()
{
  if (_facets.empty()) {
    visible(true);
    return;
  }

  std::array<float, 3> lo = {std::numeric_limits<float>::max(),
                             std::numeric_limits<float>::max(),
                             std::numeric_limits<float>::max()};
  std::array<float, 3> hi = {std::numeric_limits<float>::lowest(),
                             std::numeric_limits<float>::lowest(),
                             std::numeric_limits<float>::lowest()};

  for (const auto& facet : _facets) {
    for (const auto& vertex : facet.vertices) {
      for (int axis = 0; axis < 3; ++axis) {
        lo[axis] = std::min(lo[axis], vertex[axis]);
        hi[axis] = std::max(hi[axis], vertex[axis]);
      }
    }
  }

  std::array<float, 3> center = {(lo[0] + hi[0]) / 2.0F,
                                 (lo[1] + hi[1]) / 2.0F,
                                 (lo[2] + hi[2]) / 2.0F};

  float span = std::max({hi[0] - lo[0], hi[1] - lo[1], hi[2] - lo[2]});
  float scale = (span > 0.0F) ? (TARGET_SIZE / span) : 1.0F;

  // Bake the transform into the vertices so draw3() stays trivial. A uniform
  // scale and translation do not change facet normal directions.
  for (auto& facet : _facets) {
    for (auto& vertex : facet.vertices) {
      for (int axis = 0; axis < 3; ++axis) {
        vertex[axis] = (vertex[axis] - center[axis]) * scale;
      }
    }
  }

  visible(true);
}// normalize

void
StlComponent::draw3() const
{
  if (!visible()) {
    return;
  }

  glColor3f(0.80F, 0.80F, 0.85F);

  glBegin(GL_TRIANGLES);
  for (const auto& facet : _facets) {
    glNormal3fv(facet.normal.data());
    glVertex3fv(facet.vertices[0].data());
    glVertex3fv(facet.vertices[1].data());
    glVertex3fv(facet.vertices[2].data());
  }
  glEnd();
}// draw3
