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

#ifndef LIBMULTIDRAW_COMMAND_HPP
#define LIBMULTIDRAW_COMMAND_HPP

#include <vector>

namespace multidraw {
  
  class Editor;
  class Component;

  /**
   * @brief The COMMAND pattern.
   */
  class Command {
  public: 
    virtual void execute();

    virtual void unexecute();

    virtual bool reversible() const;

    virtual void log();

    Editor* editor() const { return _editor; };
    void editor(Editor* ed) { _editor = ed; };

    std::vector<Component*> clipboard() const { return _clipboard; };
    
  protected:
    Command(Editor*, std::vector<Component*> = std::vector<Component*>());
  
  private:
    Editor* _editor;
    std::vector<Component*> _clipboard;
    
  };

}
  
#endif // LIBMULTIDRAW_COMMAND_HPP
