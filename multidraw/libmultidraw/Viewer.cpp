/*
 * Copyright (c) 1990, 1991 Stanford University
 *
 * Permission to use, copy, modify, distribute, and sell this software and its
 * documentation for any purpose is hereby granted without fee, provided
 * that the above copyright notice appear in all copies and that both that
 * copyright notice and this permission notice appear in supporting
 * documentation, and that the name of Stanford not be used in advertising or
 * publicity pertaining to distribution of the software without specific,
 * written prior permission.  Stanford makes no representations about
 * the suitability of this software for any purpose.  It is provided "as is"
 * without express or implied warranty.
 *
 * STANFORD DISCLAIMS ALL WARRANTIES WITH REGARD TO THIS SOFTWARE,
 * INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS.
 * IN NO EVENT SHALL STANFORD BE LIABLE FOR ANY SPECIAL, INDIRECT OR
 * CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM LOSS OF USE,
 * DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
 * OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION
 * WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

#include <libmultidraw/Viewer.hpp> // class implemented

#include <libmultidraw/Editor.hpp>
#include <libmultidraw/components/Component.hpp>

#include <FL/Fl.H>
#include <FL/gl.h>

using namespace multidraw;

const float EPSILON = 1E-06;
const float ZOOM = 4.0F;
const float PANX = 0.0F;
const float PANY = 0.0F;
const float SCALE = 1.0F;
const float GREY = 0.5F;
const int CLIPZ = 100;

Viewer::Viewer(int posx, int posy, int width, int height, Editor* editor) :
  Fl_Gl_Window(posx, posy, width, height),
  _editor(editor),
  _zoom(ZOOM),
  _pan_x(PANX),
  _pan_y(PANY),
  _mouse_x(0),
  _mouse_y(0)
{
  mode(FL_DOUBLE | FL_RGB | FL_DEPTH);  
}// constructor

void
Viewer::draw()
{
  if (valid() == '\0') {
    viewport(pixel_w(), pixel_h());
    glClearColor(GREY, GREY, GREY, 1.0F);
  }

  glClear(GL_COLOR_BUFFER_BIT);

  glMatrixMode(GL_MODELVIEW);
  glLoadIdentity();
  glScalef(zoom(), zoom(), 1.0F);
  glTranslatef(pan_x(), pan_y(), 0.0F);
}// draw

int
Viewer::keys(int key)
{
  return _editor->keystroke(key);
}// keys

int
Viewer::mouse(int event, int posx, int posy)
{
  switch (event) {
  case FL_PUSH:
    {
      _mouse_x = posx;
      _mouse_y = posy;
    }
    return 1;
  case FL_DRAG:
    {
      pan((posx - _mouse_x), (_mouse_y - posy));
      _mouse_x = posx;
      _mouse_y = posy;
    }
    return 1;
  case FL_RELEASE:
    {
      _mouse_x = posx;
      _mouse_y = posy;
    }
    return 1;
  default:
    return 0;
  }
}// mouse

int
Viewer::handle(int event)
{
  switch (event) {
  case FL_MOUSEWHEEL:
    zoom(1 + Fl::event_dy() / SCALE);
    return 1;
  case FL_KEYUP:
  case FL_KEYDOWN:
    return keys(Fl::event_key());    
  case FL_PUSH:
  case FL_DRAG:
  case FL_RELEASE:
    return mouse(event, Fl::event_x(), Fl::event_y());
  default:
    return Fl_Gl_Window::handle(event);
  }
}// handle

void
Viewer::update()
{
  redraw();
}// update

void
Viewer::resize(int posx, int posy, int width, int height)
{
  Fl_Gl_Window::resize(posx, posy, width, height);
  viewport(width, height);
  redraw();
}// resize

void
Viewer::viewport(int width, int height)
{
  glViewport(0, 0, width, height);
  glMatrixMode(GL_PROJECTION);
  glLoadIdentity();
  glOrtho(-width/2, width/2, -height/2, height/2, -CLIPZ, CLIPZ);  
}// viewport

void
Viewer::reset()
{
  _zoom = ZOOM;
  _pan_x = PANX;
  _pan_y = PANY;
  redraw();
}// reset

void
Viewer::pan(float deltax, float deltay)
{
  _pan_x += deltax / _zoom;
  _pan_y += deltay / _zoom;
  redraw();
}// pan

void
Viewer::zoom(float factor)
{
  _zoom *= factor;
  redraw();
}// zoom

