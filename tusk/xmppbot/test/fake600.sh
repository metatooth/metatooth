#!/usr/bin/bash
set -euo pipefail
test -f "$1/500-Mouthguard-Export/mesh.stl"
mkdir -p "$1/600-Mouthguard-PrintReady"
echo "solid shell" > "$1/600-Mouthguard-PrintReady/shell.stl"
