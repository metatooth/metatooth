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

#include <libmultidraw/commands/Command.hpp> // class implemented

#include <libmultidraw/Multidraw.hpp>
#include <libmultidraw/components/Component.hpp>

using namespace multidraw;

Command::Command(Editor* editor, std::vector<Component*> clipboard) :
  _editor(editor),
  _clipboard(clipboard)
{
}

void
Command::execute()
{
  std::vector<Component*>::iterator iter = _clipboard.begin();
  while (iter != _clipboard.end()) {
    (*iter)->interpret(this);
    iter++;
  }
}// execute

void
Command::unexecute()
{
  std::vector<Component*>::iterator iter = _clipboard.begin();
  while (iter != _clipboard.end()) {
    (*iter)->uninterpret(this);
    iter++;
  }
}// unexecute

bool
Command::reversible() const
{
  return !_clipboard.empty();
}// reversible

void
Command::log()
{
  Multidraw::log(this);
}// log
