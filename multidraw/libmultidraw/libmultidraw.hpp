#pragma once

#ifdef _WIN32
  #define libmultidraw_EXPORT __declspec(dllexport)
#else
  #define libmultidraw_EXPORT
#endif

libmultidraw_EXPORT void libmultidraw();
