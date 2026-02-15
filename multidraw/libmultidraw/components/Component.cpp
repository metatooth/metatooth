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

#include <libmultidraw/components/Component.hpp> // class implemented

#include <libmultidraw/tools/Tool.hpp>

#include <algorithm>

using namespace multidraw;

typedef std::vector<Component*> comps;

Component::Component(const std::string& name) :
  _parent(nullptr),
  _name(name),
  _visible(false)
{
}// constructor

Command*
Component::accept(Tool& tool)
{
  return 0;
}// accept

void
Component::add_child(Component* comp)
{
  comps::iterator iter = std::find(_children.begin(), _children.end(), comp);
  if (iter == _children.end()) {
    comp->parent(this);
    _children.push_back(comp);
  }
}// add_child

Component*
Component::child(size_t index) const
{
  if (index < _children.size()) {
    return _children[index];
  }
  return nullptr;
}// child

Component*
Component::child(const std::string& name) const
{
  Component* child = nullptr;
  comps::const_iterator iter = _children.begin();
  while (iter != _children.end()) {
    if ((*iter)->name() == name) {
      child = (*iter);
      break;
    }
    iter++;
  }

  return child;
}// child

Component*
Component::root()
{
  Component* current;
  Component* parent = this;

  do {
    current = parent;
    parent = current->parent();
  } while (parent != nullptr);

  return current;
}// root

void
Component::draw2() const
{
  if (_visible) {
    for (auto iter = _children.cbegin(); iter != _children.cend(); iter++) {
      (*iter)->draw2();
    }
  }
}// draw2

void
Component::draw3() const
{
  if (_visible) {
    for (auto iter = _children.cbegin(); iter != _children.cend(); iter++) {
      (*iter)->draw3();
    }
  }
}// draw3
