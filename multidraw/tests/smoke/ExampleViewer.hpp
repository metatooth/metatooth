#ifndef EXAMPLE_VIEWER_HPP
#define EXAMPLE_VIEWER_HPP

#include <libmultidraw/Viewer.hpp>

using namespace multidraw;

class ExampleViewer : public Viewer {
public:
  ExampleViewer(int posx, int posy, int width, int height, Editor* editor);
};

#endif // EXAMPLE_VIEWER_HPP
