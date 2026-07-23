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

#include <libmultidraw/Multidraw.hpp> // class implemented

#include <libmultidraw/Catalog.hpp>
#include <libmultidraw/Editor.hpp>
#include <libmultidraw/History.hpp>
#include <libmultidraw/commands/Command.hpp>
#include <libmultidraw/components/Component.hpp>

#include <FL/Fl.H>

const double FOREVER = 1e20;

using namespace multidraw;

Multidraw* Multidraw::_instance = nullptr;

Multidraw*
Multidraw::instance()
{
  if (_instance == nullptr) {
    _instance = new Multidraw();
  }

  return _instance;
}// instance

Multidraw::Multidraw()
{
  init(nullptr);
}// constructor

Multidraw::~Multidraw()
{
  delete _catalog;
  _catalog = nullptr;

  for (auto& [comp, history] : _histories) {
    delete history;
  }
  _histories.clear();

  for (auto* editor : _editors) {
    delete editor;
  }
  _editors.clear();

  alive(false);
}// destructor

void
Multidraw::catalog(Catalog* catalog)
{
  init(catalog);
}// catalog

void
Multidraw::clearHistory(Component* comp)
{
  auto iter = _histories.find(comp);
  if (iter != _histories.end()) {
    iter->second->past.clear();
    iter->second->future.clear();
  }
}// clearHistory

void
Multidraw::doUpdate()
{
  // TODO solve constraints

  for (auto iter = _editors.cbegin(); iter != _editors.cend(); iter++) {
    (*iter)->update();
  }
}// doUpdate

void
Multidraw::executeCmd(Command* cmd)
{
  if (cmd != nullptr) {
    cmd->execute();
    if (cmd->reversible()) {
      // log() hands the command to Multidraw::log, which adopts ownership.
      cmd->log();
    } else {
      // Non-reversible commands are not retained in any history.
      delete cmd;
    }
  }
}// executeCmd

void
Multidraw::init(Catalog* catalog)
{
  _catalog = catalog;

  _editors.clear();
  _histories.clear();

  alive(true);
  updated(false);
}// init

void
Multidraw::open(Editor* editor)
{
  _editors.push_back(editor);
  editor->open();
}// open

void
Multidraw::run()
{
  alive(true);

  while (alive()) {
    updated(false);

    if (updated()) {
      update(true);
    }

    Fl::wait(FOREVER);
  }
}// run

void
Multidraw::update(bool immediate)
{
  if (immediate) {
    doUpdate();
  }

  updated(!immediate);
}// update

void
Multidraw::quit()
{
  alive(false);
}// quit

void
Multidraw::log(Command* cmd)
{
  if (cmd->reversible()) {
    Component* comp = cmd->editor()->component()->root();

    History*& history = instance()->_histories[comp];
    if (history == nullptr) {
      history = new History();
    }

    // Adopt ownership of the command and record it as the most recent action.
    // A newly logged command invalidates any pending redo history.
    history->past.push_back(std::unique_ptr<Command>(cmd));
    history->future.clear();
  } else {
    delete cmd;
  }
}// log
