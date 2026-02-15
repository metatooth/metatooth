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

#ifndef LIBMULTIDRAW_MULTIDRAW_HPP
#define LIBMULTIDRAW_MULTIDRAW_HPP

#include <map>
#include <vector>

namespace multidraw {
  class Catalog;
  class Command;
  class Component;
  class Editor;
  class History;

  /**
   * @brief The Multidraw class provides top-level Application support.
   *
   * Multidraw is a participant in the SINGLETON design pattern.
   */
  class Multidraw {
  public:
    /// Retrieve the single instance of this class.
    static Multidraw* instance();

    ~Multidraw();

    /// Starts the event loop
    void run();
    void update(bool immediate = false);
    void quit();

    void open(Editor*);
    void close(Editor*);
    void closeAll();
  
    static void log(Command*);
    void undo(Component*, int);
    void redo(Component*, int);
    void clearHistory(Component*);

    static void executeCmd(Command*);

    bool alive() const { return _alive; }
    bool updated() const { return _updated; }
    void alive(bool val) { _alive = val; }
    void updated(bool val) { _updated = val; }
  
    Catalog* catalog() const { return _catalog; };

    /// Setting the Catalog has a side-effect of reseting the class's member variables.
    void catalog(Catalog*);
    
  private:
    /**
     * @brief Default constructor.
     */
    Multidraw();

    static Multidraw* _instance;
    Catalog* _catalog;
    std::vector<Editor*> _editors;
    bool _alive;
    bool _updated;
    std::map<Component*, History*> _histories;

    void doUpdate();

    void init(Catalog*);
  
  };

}

#endif // LIBMULTIDRAW_MULTIDRAW_HPP
