#ifndef STL_VIEWER_HPP
#define STL_VIEWER_HPP

#include <libmultidraw/Viewer.hpp>

using namespace multidraw;

/**
 * @brief A 3D view of an STL model.
 *
 * Viewer is the framework's "V". The base class already provides an
 * Fl_Gl_Window with zoom (mouse wheel) and the event plumbing; this subclass
 * supplies the OpenGL rendering in draw() and adds drag-to-rotate. It reads
 * its geometry from the Editor's Component every frame, so the model and the
 * view stay decoupled.
 */
class StlViewer : public Viewer {
public:
  StlViewer(int posx, int posy, int width, int height, Editor* editor);

  virtual void draw();

protected:
  virtual int keys(int key);
  virtual int mouse(int event, int posx, int posy);

private:
  float _rot_x;
  float _rot_y;
  int _last_x;
  int _last_y;
};

#endif // STL_VIEWER_HPP
