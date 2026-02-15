from conans import ConanFile, tools, CMake
from conan.tools.cmake import CMakeToolchain, cmake_layout, CMakeDeps

class MultidrawConan(ConanFile):
    name = "libmultidraw"
    version = "0.1"

    generators = "cmake", "cmake_find_package"
    
    # Optional metadata
    license = "MIT"
    author = "Terry Lorber terry@metatooth.com"
    url = "https://github.com/metatooth/multidraw"
    description = "A multi-platform framework for domain-specific editors."
    topics = ("framework", "design", "visualization", "2D", "3D")

    # Binary configuration
    settings = "os", "compiler", "build_type", "arch"
    options = {"shared": [True, False], "fPIC": [True, False]}
    default_options = {"shared": False, "fPIC": True}

    # Sources are located in the same place as this recipe, copy them
    # to the recipe
    exports_sources = "CMakeLists.txt", "VERSION", "libmultidraw/*", "doc/*", "tests/*"

    def config_options(self):
        if self.settings.os == "Windows":
            del self.options.fPIC

    def layout(self):
        cmake_layout(self)

    def requirements(self):
        self.requires("fltk/1.3.8")
        self.requires("doxygen/1.9.4")
        self.requires("libxft/2.3.6")

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
        self.cpp_info.libs = ["libmultidraw"]
