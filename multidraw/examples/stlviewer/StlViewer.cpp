#include "StlViewer.hpp" // class implemented

#include <libmultidraw/Editor.hpp>
#include <libmultidraw/Multidraw.hpp>
#include <libmultidraw/components/Component.hpp>

#include <FL/Fl.H>
#include <FL/gl.h>

// Half-depth of the orthographic clipping volume, in world units. Generous
// enough to hold a normalized model at any rotation.
const int CLIP = 1000;
const float DARK = 0.15F;
const float ROTATE_SPEED = 0.5F;

StlViewer::StlViewer(int posx,
                     int posy,
                     int width,
                     int height,
                     Editor* editor) :
  Viewer(posx, posy, width, height, editor),
  _rot_x(20.0F),
  _rot_y(-30.0F),
  _last_x(0),
  _last_y(0)
{
}// constructor

void
StlViewer::draw()
{
  // valid() is false the first time and after every resize, which is when the
  // GL context needs its one-time and viewport-dependent state set up.
  if (valid() == '\0') {
    glViewport(0, 0, pixel_w(), pixel_h());

    glMatrixMode(GL_PROJECTION);
    glLoadIdentity();
    glOrtho(-pixel_w() / 2.0, pixel_w() / 2.0,
            -pixel_h() / 2.0, pixel_h() / 2.0,
            -CLIP, CLIP);

    glClearColor(DARK, DARK, DARK, 1.0F);

    // Depth testing so nearer facets hide farther ones; a single headlight
    // and color-material so glColor in the Component acts as a diffuse color.
    glEnable(GL_DEPTH_TEST);
    glEnable(GL_LIGHTING);
    glEnable(GL_LIGHT0);
    glEnable(GL_NORMALIZE);
    glEnable(GL_COLOR_MATERIAL);
    glColorMaterial(GL_FRONT_AND_BACK, GL_AMBIENT_AND_DIFFUSE);

    const GLfloat light[] = {0.3F, 0.5F, 1.0F, 0.0F};
    glLightfv(GL_LIGHT0, GL_POSITION, light);
  }

  glClear(GL_COLOR_BUFFER_BIT | GL_DEPTH_BUFFER_BIT);

  glMatrixMode(GL_MODELVIEW);
  glLoadIdentity();

  // zoom()/pan_x()/pan_y() are maintained by the base Viewer from the mouse
  // wheel and drag events. Uniform scaling keeps the model undistorted as it
  // rotates.
  glScalef(zoom(), zoom(), zoom());
  glTranslatef(pan_x(), pan_y(), 0.0F);
  glRotatef(_rot_x, 1.0F, 0.0F, 0.0F);
  glRotatef(_rot_y, 0.0F, 1.0F, 0.0F);

  Component* comp = editor()->component();
  if (comp != nullptr) {
    comp->draw3();
  }
}// draw

int
StlViewer::keys(int key)
{
  switch (key) {
  case 'r':
    // Frame the model again: reset() restores the base zoom/pan.
    reset();
    _rot_x = 20.0F;
    _rot_y = -30.0F;
    redraw();
    return 1;
  case 'q':
  case FL_Escape:
    Multidraw::instance()->quit();
    return 1;
  default:
    return Viewer::keys(key);
  }
}// keys

int
StlViewer::mouse(int event, int posx, int posy)
{
  // Left-drag orbits the model instead of the base class's pan.
  switch (event) {
  case FL_PUSH:
    _last_x = posx;
    _last_y = posy;
    return 1;
  case FL_DRAG:
    _rot_y += (posx - _last_x) * ROTATE_SPEED;
    _rot_x += (posy - _last_y) * ROTATE_SPEED;
    _last_x = posx;
    _last_y = posy;
    redraw();
    return 1;
  case FL_RELEASE:
    return 1;
  default:
    return 0;
  }
}// mouse
