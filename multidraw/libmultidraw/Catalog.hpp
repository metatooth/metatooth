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

#ifndef LIBMULTIDRAW_CATALOG_HPP
#define LIBMULTIDRAW_CATALOG_HPP

#include <map>
#include <string>
#include <filesystem>

namespace multidraw {
  
  class Component;
  class Command;
  class Creator;

  /**
   * The domain model described by Components should be persist after
   * running Multidraw. Persisting to disk is a popular option.
   */
  class Catalog {
  public:
    Catalog(const std::string&, Creator*);

    virtual bool save(Command*, const std::filesystem::path&);
    virtual bool save(Component*, const std::filesystem::path&);
  
    virtual bool retrieve(const std::filesystem::path&, Command*&);
    virtual bool retrieve(const std::filesystem::path&, Component*&);

    Creator* creator() const { return _creator; };
    
    const std::string& name() const { return _name; };
  
    std::string name(Command*) const;
    std::string name(Component*) const;
  
  private:
    std::string _name;
    Creator* _creator;
    std::map<std::string, Component*> _compMap;
    std::map<std::string, Command*> _cmdMap;
  
  };

}

#endif // LIBMULTIDRAW_CATALOG_HPP
