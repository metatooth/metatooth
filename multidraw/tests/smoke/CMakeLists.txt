
add_executable(test_smoke
  ExampleEditor.cpp
  ExampleCatalog.cpp
  ExampleComponent.cpp
  ExampleCreator.cpp
  ExampleViewer.cpp
  main.cpp)

target_link_libraries(test_smoke multidraw ${CONAN_LIBS})
target_include_directories(test_smoke PUBLIC ${CMAKE_SOURCE_DIR} ${CMAKE_BINARY_DIR})
add_test(NAME test_smoke COMMAND test_smoke)
