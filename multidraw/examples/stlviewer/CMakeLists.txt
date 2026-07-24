add_executable(stlviewer
  StlCatalog.cpp
  StlComponent.cpp
  StlCreator.cpp
  StlEditor.cpp
  StlViewer.cpp
  main.cpp)

target_link_libraries(stlviewer multidraw ${CONAN_LIBS})
target_include_directories(stlviewer PUBLIC ${CMAKE_SOURCE_DIR} ${CMAKE_BINARY_DIR})

# Ship the sample model next to the binary so `./stlviewer` runs as-is.
add_custom_command(TARGET stlviewer POST_BUILD
  COMMAND ${CMAKE_COMMAND} -E copy_if_different
    ${CMAKE_CURRENT_SOURCE_DIR}/cube.stl
    $<TARGET_FILE_DIR:stlviewer>/cube.stl)
