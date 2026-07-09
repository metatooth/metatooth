from conan import ConanFile
from conan.tools.cmake import CMake, CMakeToolchain, cmake_layout, CMakeDeps

class FabriqueConan(ConanFile):
    name = "libfabrique"
    version = "0.1"

    # Optional metadata
    license = "MIT"
    author = "Terry Lorber terry@metatooth.com"
    url = "https://github.com/metatooth/fabrique"
    description = "A fabrication framework built on the multidraw editor platform."
    topics = ("framework", "fabrication", "design", "visualization")

    # Binary configuration
    settings = "os", "compiler", "build_type", "arch"
    options = {"shared": [True, False], "fPIC": [True, False]}
    default_options = {"shared": False, "fPIC": True}

    # Sources are located in the same place as this recipe, copy them
    # to the recipe
    exports_sources = "CMakeLists.txt", "VERSION", "fabrique/*", "doc/*", "tests/*"

    def config_options(self):
        if self.settings.os == "Windows":
            del self.options.fPIC

    def layout(self):
        cmake_layout(self)

    def requirements(self):
        self.requires("libmultidraw/0.1")

    def generate(self):
        deps = CMakeDeps(self)
        deps.generate()
        tc = CMakeToolchain(self)
        tc.user_presets_path = "ConanPresets.json"
        tc.generate()

    def build(self):
        cmake = CMake(self)
        cmake.configure()
        cmake.build()

    def package(self):
        cmake = CMake(self)
        cmake.install()

    def package_info(self):
        self.cpp_info.libs = ["libfabrique"]
