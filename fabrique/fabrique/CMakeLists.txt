add_custom_target(VersionHeader
  COMMAND ${CMAKE_COMMAND}
    -DFABRIQUE_SOURCE_DIR=${CMAKE_CURRENT_SOURCE_DIR}
    -DFABRIQUE_BINARY_DIR=${CMAKE_CURRENT_BINARY_DIR}
    -P ${CMAKE_CURRENT_LIST_DIR}/VersionHeader.cmake
)

add_library(libfabrique STATIC
  libfabrique/libfabrique.cpp
  libfabrique/commands/WaferCmd.cpp
)

add_dependencies(libfabrique VersionHeader)

target_include_directories(libfabrique
  PUBLIC
  ${CMAKE_CURRENT_SOURCE_DIR}
  ${CMAKE_CURRENT_BINARY_DIR}
)

target_link_libraries(libfabrique libmultidraw::libmultidraw)

add_executable(fabrique main.cpp)
add_dependencies(fabrique VersionHeader)
target_link_libraries(fabrique libfabrique)
target_include_directories(fabrique PUBLIC ${CMAKE_CURRENT_BINARY_DIR}/libfabrique)

install(TARGETS fabrique libfabrique)
install(FILES libfabrique/libfabrique.hpp DESTINATION include/libfabrique)
install(FILES libfabrique/commands/WaferCmd.hpp DESTINATION include/libfabrique/commands)
