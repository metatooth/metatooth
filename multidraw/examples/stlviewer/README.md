# STL Viewer

A small, complete application built on Multidraw. It loads an
[STL](<https://en.wikipedia.org/wiki/STL_(file_format)>) mesh — the common 3D
printing / CAD interchange format — and renders it in an interactive 3D window.

It exists to show how the framework's pieces fit together on a real task.

## Building

The example is built as part of the top-level project:

```
$ cd multidraw
$ make
```

The `stlviewer` binary is written to
`_build/Release/examples/stlviewer/stlviewer`, with `cube.stl` copied beside it.

## Running

```
# uses the bundled cube.stl
$ ./_build/Release/examples/stlviewer/stlviewer

# or point it at any ASCII or binary STL file
$ ./_build/Release/examples/stlviewer/stlviewer /path/to/model.stl
```

Controls:

| Input        | Action     |
| ------------ | ---------- |
| drag         | rotate     |
| mouse wheel  | zoom       |
| `r`          | reset view |
| `q` / escape | quit       |

## How it maps onto Multidraw

Multidraw is a Unidraw-style framework: an application is assembled from a few
collaborating roles rather than written from scratch. Each source file here
supplies one role.

| Role (framework class)  | This example   | Responsibility                                                |
| ----------------------- | -------------- | ------------------------------------------------------------- |
| `Component` (model)     | `StlComponent` | Holds the triangle mesh; draws itself in 3D via `draw3()`.    |
| `Creator` (builder)     | `StlCreator`   | Parses ASCII and binary STL into an `StlComponent`.           |
| `Catalog` (persistence) | `StlCatalog`   | On `retrieve()`, hands loading off to the `StlCreator`.       |
| `Viewer` (view)         | `StlViewer`    | An `Fl_Gl_Window` that renders the model and orbits/zooms it. |
| `Editor` (controller)   | `StlEditor`    | Builds the window and the viewer, and owns the loaded model.  |
| `Multidraw` (app)       | `main.cpp`     | Installs the catalog, opens the editor, runs the event loop.  |

The data flow when the program starts:

1. `main` installs an `StlCatalog` and opens an `StlEditor` for a file path.
2. The base `Editor` constructor asks the catalog to `retrieve()` that path.
3. `StlCatalog` calls `StlCreator::readSTL()`, which detects the STL encoding,
   parses the facets, and normalizes the mesh to a consistent size.
4. The resulting `StlComponent` becomes the editor's model.
5. `StlViewer::draw()` reads that model every frame and calls its `draw3()`.

Because the model (`StlComponent`) and the view (`StlViewer`) only meet through
the framework's `Component` interface, either could be swapped independently —
the point of the pattern.
