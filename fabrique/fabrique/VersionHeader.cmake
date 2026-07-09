file(STRINGS "${FABRIQUE_SOURCE_DIR}/../VERSION" FABRIQUE_VERSION)
file(MAKE_DIRECTORY "${FABRIQUE_BINARY_DIR}/libfabrique")
configure_file(${FABRIQUE_SOURCE_DIR}/version.tpp ${FABRIQUE_BINARY_DIR}/libfabrique/version.hpp @ONLY)
