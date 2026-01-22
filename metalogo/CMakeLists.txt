cmake_minimum_required(VERSION 3.10)

project(metalogo)

# red ff33bb
# green 4d9900
# blue 33669f

add_custom_target(SvgLogos ALL
  DEPENDS logo.svg logo-red.svg logo-green.svg logo-blue.svg)

add_custom_command(OUTPUT logo.svg logo-red.svg logo-green.svg logo-blue.svg
  COMMAND ${CMAKE_CURRENT_SOURCE_DIR}/metalogo.sh ARGS 256 0.95 45 000000 ffffff > ${CMAKE_CURRENT_BINARY_DIR}/logo.svg
  COMMAND ${CMAKE_CURRENT_SOURCE_DIR}/metalogo.sh ARGS 256 0.95 45 ff33bb ffffff  > ${CMAKE_CURRENT_BINARY_DIR}/logo-red.svg
  COMMAND ${CMAKE_CURRENT_SOURCE_DIR}/metalogo.sh ARGS 256 0.95 45 000000 4d9900 > ${CMAKE_CURRENT_BINARY_DIR}/logo-green.svg
  COMMAND ${CMAKE_CURRENT_SOURCE_DIR}/metalogo.sh ARGS 256 0.95 45 000000 33669f > ${CMAKE_CURRENT_BINARY_DIR}/logo-blue.svg)

add_custom_target(PngLogos ALL
  DEPENDS logo.png logo-red.png logo-green.png logo-blue.png)

add_custom_command(OUTPUT logo.png logo-red.png logo-green.png logo-blue.png
  COMMAND convert -background none ${CMAKE_CURRENT_BINARY_DIR}/logo.svg ${CMAKE_CURRENT_BINARY_DIR}/logo.png
  COMMAND convert -background none ${CMAKE_CURRENT_BINARY_DIR}/logo-red.svg ${CMAKE_CURRENT_BINARY_DIR}/logo-red.png
  COMMAND convert -background none ${CMAKE_CURRENT_BINARY_DIR}/logo-green.svg ${CMAKE_CURRENT_BINARY_DIR}/logo-green.png
  COMMAND convert -background none ${CMAKE_CURRENT_BINARY_DIR}/logo-blue.svg ${CMAKE_CURRENT_BINARY_DIR}/logo-blue.png)
