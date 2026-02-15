add_custom_target(VersionHeader
  ${CMAKE_COMMAND} -P ${CMAKE_CURRENT_LIST_DIR}/VersionHeader.cmake
)

add_library(
	multidraw
	STATIC
        libmultidraw.cpp
	Catalog.cpp
	Creator.cpp
        Editor.cpp
	Multidraw.cpp
	Viewer.cpp
	commands/Command.cpp
	commands/MacroCmd.cpp
	commands/SaveAsCmd.cpp
	commands/SaveCmd.cpp
	components/Component.cpp
        state_vars/ComponentNameVar.cpp
	state_vars/ModifiedStatusVar.cpp	
	state_vars/NameVar.cpp
)

target_link_libraries(multidraw ${CONAN_LIBS})

add_dependencies(multidraw VersionHeader)

target_include_directories(
  multidraw
  PUBLIC
  ${CMAKE_SOURCE_DIR}
  ${CMAKE_CURRENT_BINARY_DIR}
  )

set_target_properties(
  multidraw
  PROPERTIES
  PUBLIC_HEADER
  "libmultidraw.hpp"
)

install(TARGETS multidraw)

