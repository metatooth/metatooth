#include "StlComponent.hpp"  // class implemented

#include <FL/gl.h>

#include <algorithm>
#include <limits>

// Largest dimension, in world units, that a normalized model spans. The
// Viewer's default zoom frames a model of roughly this size.
const float TARGET_SIZE = 100.0F;
const float HALF = 0.5F;
const float MODEL_COLOR_R = 0.80F;
const float MODEL_COLOR_G = 0.80F;
const float MODEL_COLOR_B = 0.85F;

StlComponent::StlComponent(const std::string& name) : Component(name)
{
}  // constructor

void
StlComponent::add_facet(const Facet& facet)
{
  _facets.push_back(facet);
}  // add_facet

void
StlComponent::normalize()
{
  if (_facets.empty()) {
    visible(true);
    return;
  }

  std::array<float, 3> min_bound = {std::numeric_limits<float>::max(),
                                    std::numeric_limits<float>::max(),
                                    std::numeric_limits<float>::max()};
  std::array<float, 3> max_bound = {std::numeric_limits<float>::lowest(),
                                    std::numeric_limits<float>::lowest(),
                                    std::numeric_limits<float>::lowest()};

  for (const auto& facet : _facets) {
    for (const auto& vertex : facet.vertices) {
      for (int axis = 0; axis < 3; ++axis) {
        min_bound[axis] = std::min(min_bound[axis], vertex[axis]);
        max_bound[axis] = std::max(max_bound[axis], vertex[axis]);
      }
    }
  }

  std::array<float, 3> center = {(min_bound[0] + max_bound[0]) * HALF,
                                 (min_bound[1] + max_bound[1]) * HALF,
                                 (min_bound[2] + max_bound[2]) * HALF};

  float span = std::max(
      {max_bound[0] - min_bound[0], max_bound[1] - min_bound[1], max_bound[2] - min_bound[2]});
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
}  // normalize

void
StlComponent::draw3() const
{
  if (!visible()) {
    return;
  }

  glColor3f(MODEL_COLOR_R, MODEL_COLOR_G, MODEL_COLOR_B);

  glBegin(GL_TRIANGLES);
  for (const auto& facet : _facets) {
    glNormal3fv(facet.normal.data());
    glVertex3fv(facet.vertices[0].data());
    glVertex3fv(facet.vertices[1].data());
    glVertex3fv(facet.vertices[2].data());
  }
  glEnd();
}  // draw3
