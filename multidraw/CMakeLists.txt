cmake_minimum_required(VERSION 3.24)
set(CMAKE_CXX_STANDARD 20)

project(libmultidraw CXX)

find_package(fltk REQUIRED)
find_package(libxft REQUIRED)
find_package(freetype REQUIRED)
find_package(Doxygen)

include(CTest)

# first we can indicate the documentation build as an option and set
# it to ON by default
option(BUILD_DOC "Build documentation" ON)

# check if Doxygen is installed
find_package(Doxygen)
if (DOXYGEN_FOUND AND EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/doc/Doxyfile.in")
    # set input and output files
    set(DOXYGEN_IN ${CMAKE_CURRENT_SOURCE_DIR}/doc/Doxyfile.in)
    set(DOXYGEN_OUT ${CMAKE_CURRENT_BINARY_DIR}/Doxyfile)

    # request to configure the file
    configure_file(${DOXYGEN_IN} ${DOXYGEN_OUT} @ONLY)
    message("Doxygen build started")

    # note the option ALL which allows to build the docs together with
    # the application
    add_custom_target( docs
        COMMAND ${DOXYGEN_EXECUTABLE} ${DOXYGEN_OUT}
        WORKING_DIRECTORY ${CMAKE_CURRENT_BINARY_DIR}
        COMMENT "Generating API documentation with Doxygen"
        VERBATIM )
else (DOXYGEN_FOUND AND EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/doc/Doxyfile.in")
  message("Doxygen configuration (doc/Doxyfile.in) not found, skipping documentation build")
endif (DOXYGEN_FOUND AND EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/doc/Doxyfile.in")

set(CMAKE_CXX_CLANG_TIDY clang-tidy -checks=-*,readability-*)

add_subdirectory(libmultidraw)
add_subdirectory(tests)
