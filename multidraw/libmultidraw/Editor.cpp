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

#include <libmultidraw/Editor.hpp> // class implemented

#include <libmultidraw/Catalog.hpp>
#include <libmultidraw/Multidraw.hpp>
#include <libmultidraw/Viewer.hpp>
#include <libmultidraw/components/Component.hpp>
#include <libmultidraw/commands/MacroCmd.hpp>
#include <libmultidraw/state_vars/ComponentNameVar.hpp>
#include <libmultidraw/state_vars/ModifiedStatusVar.hpp>
#include <libmultidraw/state_vars/StateVar.hpp>
#include <libmultidraw/tools/Tool.hpp>

#include <FL/Fl.H>
#include <FL/Fl_Window.H>

#include <string>

using namespace multidraw;

const float RGB = 0.1F;
const int WIDTH = 800;
const int HEIGHT = 800;

Editor::Editor(const std::string& inpath, const std::string& outpath) :
  _component(nullptr),
  _tool(nullptr),
  _command(nullptr),
  _window(nullptr),
  _viewer(nullptr)
{
  Catalog* catalog = Multidraw::instance()->catalog();

  // NOTE 20221014 Terry: Order is important. Some commands might use
  // this Editor's state variables.

  // initialize component tree from the input path
  
  Component* comp = nullptr;
  if (catalog->retrieve(inpath, comp)) {
    init(comp);
  }

  // state variables
  
  _outpath = new NameVar(outpath);

  // try to parse commands from the input file, then execute
  
  _command = dynamic_cast<Command*> (new MacroCmd(this));
  catalog->retrieve(inpath, _command);

  _command->execute();
}// constructor

Editor::~Editor()
{
  delete _modified;
  delete _outpath;
}// destructor

void
Editor::open()
{
  _window->show();
}// open

void
Editor::close()
{
}// close

void
Editor::update() const
{
  Viewer* view;
  for (int i = 0; (view = viewer(i)) != nullptr; ++i) {
    view->update();
  }
}// update

bool
Editor::modified() const
{
  return _modified->modified();
}

StateVar*
Editor::state(const std::string& name) const
{
  std::string ALLCAPS = name;
  for (auto& character: ALLCAPS) { character = (char)std::toupper(character); }

  StateVar* var = nullptr;
  
  if (ALLCAPS == "COMPONENTNAME") {
    var = _name;
  } else if (ALLCAPS == "MODIFIED") {
    var = _modified;
  } else if (ALLCAPS == "OUTPATH") {
    var = _outpath;
  }
  
  return var;
}// state

int
Editor::keystroke(int event)
{
  return 0;
}// keystroke

void
Editor::init(Component* comp)
{
  _component = comp;
  
  _modified = new ModifiedStatusVar(_component);
}// init
