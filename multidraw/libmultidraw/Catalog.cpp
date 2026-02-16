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

#include <libmultidraw/Catalog.hpp> // class implemented
#include <libmultidraw/Creator.hpp>

#include <iostream>
#include <filesystem>

namespace fs = std::filesystem;

using namespace multidraw;

Catalog::Catalog(const std::string& name, Creator* creator) :
  _name(name),
  _creator(creator)
{

}

bool
Catalog::save(Command* comp, const fs::path& target)
{
  return false;
}

bool
Catalog::save(Component* comp, const fs::path& target)
{
  return false;
}

bool
Catalog::retrieve(const fs::path& source, Component*& comp)
{
  return false;
}// retrieve

bool
Catalog::retrieve(const fs::path& source, Command*& cmd)
{
  return false;
}// retrieve

std::string
Catalog::name(Component* comp) const
{
  std::string result;
  std::map<std::string, Component*>::const_iterator iter = _compMap.cbegin();
  while (iter != _compMap.cend()) {
    if (iter->second == comp) {
      result = iter->first;
    }
    iter++;
  }
  return result;
}// name


std::string
Catalog::name(Command* cmd) const
{
  std::string result;
  std::map<std::string, Command*>::const_iterator iter = _cmdMap.cbegin();
  while (iter != _cmdMap.cend()) {
    if (iter->second == cmd) {
      result = iter->first;
    }
    iter++;
  }
  return result;
}// name
