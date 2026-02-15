# Multidraw

Multidraw implements an application framework in C++. Not just any framework, the Unidraw framework. It is described by John Vlissides in his paper [Unidraw: A Framework for Building Domain-Specific Graphical Editors](https://dl.acm.org/doi/pdf/10.1145/73660.73680).

Multidraw depends on [FLTK](https://www.fltk.org/) for multi-platform user interface support.

Git repository at [metatooth/libmultidraw](https://github.com/metatooth/libmultidraw)

Original source from [vectaport/ivtools](https://github.com/vectaport/ivtools)

![Unidraw](./doc/Unidraw.png)

## Getting Started

### Install dependencies

```$ sudo apt install conan cmake build-essentials clang clang-tidy```

### Get and build

```
$ git clone https://github.com/metatooth/multidraw.git
$ cd multidraw
$ mkdir _build && cd _build
$ conan install -s compiler=clang -s compiler.version=14 ..
$ cmake ..
$ make
```

## License

Copyright (c) 2023 Metatooth LLC. See the [License](LICENSE).

In addition, some elements of the codebase are:

Copyright (c) 1990, 1991 Stanford University
