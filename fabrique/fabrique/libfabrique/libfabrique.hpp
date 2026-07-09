#pragma once

#ifdef _WIN32
  #define libfabrique_EXPORT __declspec(dllexport)
#else
  #define libfabrique_EXPORT
#endif

libfabrique_EXPORT void libfabrique();
