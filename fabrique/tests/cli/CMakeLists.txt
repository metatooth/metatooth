add_executable(test_cli main.cpp)

target_compile_definitions(test_cli PRIVATE
  FABRIQUE_EXECUTABLE="$<TARGET_FILE:fabrique>")

add_test(NAME test_cli COMMAND test_cli)
