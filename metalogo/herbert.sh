#!/bin/bash


if [ $# -ne 2 ]
then
    echo "usage: herbert.sh [color] [font]"
    echo
    echo "  Makes the growherbert.com logotype."
    echo
    echo "  for example, herbert.sh \#00dd77 Ubuntu-Bold-Italic"
    exit
fi

color="$1"
font="$2"

# Validate color: expect hex RGB like "#00dd77" or "00dd77"
if ! printf '%s\n' "$color" | grep -Eq '^#?[0-9A-Fa-f]{6}$'; then
    echo "error: invalid color '$color'. Expected hex RGB value like '#00dd77'." >&2
    exit 1
fi

# Validate font: allow letters, digits, spaces, underscores, dots, and dashes
if ! printf '%s\n' "$font" | grep -Eq '^[A-Za-z0-9][A-Za-z0-9 _.-]*$'; then
    echo "error: invalid font '$font'." >&2
    exit 1
fi

./metalogo.sh 80 0.95 45 fdfdfd 00dd77 > logo.svg

convert -background white logo.svg logo.png

convert -fill "$color" -stroke "$color" -font "$font" -pointsize 200 label:Herbert herbert.png

convert -size 50x202 canvas:white spacer.png

convert -size 130x34 canvas:white top.png

convert -append top.png logo.png padded-logo.png

convert +append spacer.png padded-logo.png herbert.png spacer.png temp.png

convert temp.png -transparent white herbert.png
