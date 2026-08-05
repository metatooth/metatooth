add_custom_target(VersionHeader
  COMMAND ${CMAKE_COMMAND}
    -DFABRIQUE_SOURCE_DIR=${CMAKE_CURRENT_SOURCE_DIR}
    -DFABRIQUE_BINARY_DIR=${CMAKE_CURRENT_BINARY_DIR}
    -P ${CMAKE_CURRENT_LIST_DIR}/VersionHeader.cmake
)

add_library(libfabrique STATIC
  libfabrique/libfabrique.cpp
  libfabrique/Device.cpp
  libfabrique/commands/WaferCmd.cpp
)

add_dependencies(libfabrique VersionHeader)

target_include_directories(libfabrique
  PUBLIC
  ${CMAKE_CURRENT_SOURCE_DIR}
  ${CMAKE_CURRENT_BINARY_DIR}
)

target_link_libraries(libfabrique libmultidraw::libmultidraw nlohmann_json::nlohmann_json)

add_executable(fabrique main.cpp)
add_dependencies(fabrique VersionHeader)
target_link_libraries(fabrique libfabrique)
target_include_directories(fabrique PUBLIC ${CMAKE_CURRENT_BINARY_DIR}/libfabrique)

install(TARGETS fabrique libfabrique)
install(FILES libfabrique/libfabrique.hpp libfabrique/Device.hpp DESTINATION include/libfabrique)
install(FILES libfabrique/commands/WaferCmd.hpp DESTINATION include/libfabrique/commands)

add_executable(test_wafer libfabrique/commands/WaferCmd.test.cpp)
target_link_libraries(test_wafer libfabrique)
target_include_directories(test_wafer PUBLIC ${CMAKE_SOURCE_DIR} ${CMAKE_BINARY_DIR})
add_test(NAME test_wafer COMMAND test_wafer)
