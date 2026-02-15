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

#ifndef LIBMULTIDRAW_VIEWER_HPP
#define LIBMULTIDRAW_VIEWER_HPP

#include <FL/Fl_Gl_Window.H>

namespace multidraw {

  class Editor;

  /**
   * @brief Displays a Component hierarchy.
   */
  class Viewer : public Fl_Gl_Window {
  public:
    Viewer(int posx, int posy, int width, int height, Editor*);

    virtual int handle(int event);
    
    virtual void draw();

    virtual void resize(int posx, int posy, int width, int height);
    
    virtual void update();

  protected:
    Editor* editor() const { return _editor; };
    
    float zoom() const { return _zoom; };
    float pan_x() const { return _pan_x; };
    float pan_y() const { return _pan_y; };

    virtual void reset();
    virtual void pan(float deltax, float deltay);
    virtual void zoom(float factor);

    virtual int keys(int key);
    virtual int mouse(int event, int posx, int posy);

    virtual void viewport(int width, int height);

  private:    
    Editor* _editor;
    float _zoom;
    float _pan_x;
    float _pan_y;
    int _mouse_x;
    int _mouse_y;
  };

}

#endif // LIBMULTIDRAW_VIEWER_HPP
