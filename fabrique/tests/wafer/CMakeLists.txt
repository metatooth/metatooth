
add_executable(test_wafer main.cpp)

target_link_libraries(test_wafer libfabrique)
target_include_directories(test_wafer PUBLIC ${CMAKE_SOURCE_DIR} ${CMAKE_BINARY_DIR})
add_test(NAME test_wafer COMMAND test_wafer)
