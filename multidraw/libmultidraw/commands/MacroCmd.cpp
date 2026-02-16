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

#include <libmultidraw/commands/MacroCmd.hpp> // class implemented

using namespace multidraw;

MacroCmd::MacroCmd(Editor* editor) : Command(editor)
{
}// constructor

void
MacroCmd::execute()
{
  std::vector<Command*>::iterator iter = _children.begin();
  while (iter != _children.end()) {
    (*iter)->execute();
    iter++;
  }
}// execute

void
MacroCmd::unexecute()
{
  std::vector<Command*>::iterator iter = _children.begin();
  while (iter != _children.end()) {
    (*iter)->unexecute();
    iter++;
  }
}// unexecute

bool
MacroCmd::reversible() const
{
  bool reversible = true;

  std::vector<Command*>::const_iterator iter = _children.cbegin();
  while (reversible && iter != _children.cend()) {
    reversible = (*iter)->reversible();
    iter++;
  }
  
  return reversible;
}// reversible


void
MacroCmd::addChild(Command* cmd)
{
  cmd->editor(this->editor());    
  _children.push_back(cmd);
}// addChild
