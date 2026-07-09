
add_executable(test_smoke main.cpp)

target_link_libraries(test_smoke libfabrique)
target_include_directories(test_smoke PUBLIC ${CMAKE_SOURCE_DIR} ${CMAKE_BINARY_DIR})
add_test(NAME test_smoke COMMAND test_smoke)
