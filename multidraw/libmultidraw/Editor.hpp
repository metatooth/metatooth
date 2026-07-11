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

#ifndef LIBMULTIDRAW_EDITOR_HPP
#define LIBMULTIDRAW_EDITOR_HPP

#include <string>
#include <vector>

class Fl_Window;

namespace multidraw {
  class Component;
  class ComponentNameVar;
  class Command;
  class ModifiedStatusVar;
  class NameVar;
  class Viewer;
  class StateVar;
  class Tool;

  /**
   * Editor participates in the TOOLED COMPOSITE design pattern. A Component
   * is manipulated by a Tool and modified by a Command.
   */
  class Editor {
  public:
    Editor(const std::string&, const std::string&);
    ~Editor();

    void open();
    void close();
    void update() const;
  
    Component* component() const { return _component; }
    virtual Viewer* viewer(int id = 0) const { return (id == 0) ? _viewer : nullptr; }
    Tool* tool() const { return _tool; }
    bool modified() const;
    Command* command() const { return _command; }
    Fl_Window* window() const { return  _window; }
    
    void component(Component* comp) { _component = comp; }
    virtual void viewer(Viewer* viewer, int id = 0) { if (id == 0) _viewer = viewer; }
    void tool(Tool* tool) { _tool = tool; }
    void command(Command* cmd) { _command = cmd; }
    void window(Fl_Window* window) { _window = window; }
    
    void addTool(Tool*);
    bool hasTool(Tool*);
    void removeTool(Tool*);

    StateVar* state(const std::string&) const;

    virtual int keystroke(int event);
    
  private:
    void init(Component*);
  
    Component* _component;
    Tool* _tool;
    Command* _command;
    std::vector<Tool*> _tools;
    ComponentNameVar* _name;
    ModifiedStatusVar* _modified;
    NameVar* _outpath;

    Fl_Window* _window;
    Viewer* _viewer;
  };

}

#endif // LIBMULTIDRAW_EDITOR_HPP
