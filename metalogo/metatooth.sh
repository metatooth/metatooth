#!/bin/bash


if [ $# -ne 2 ]
then
    echo "usage: metatooth.sh [color] [font]"
    echo
    echo "  Makes the Metatooth LLC logotype."
    echo
    echo "  for example, metatooth.sh \#ff33bb EB-Garamond-08-Regular"
    exit
fi

color=$1
font=$2
background=ffffff

# Validate color: must be a hex color like #rrggbb
if ! [[ "$color" =~ ^#[0-9A-Fa-f]{6}$ ]]; then
    echo "error: color must be in the form #rrggbb (e.g., #ff33bb)" >&2
    exit 1
fi

# Validate font: allow only safe characters to avoid command injection
if ! [[ "$font" =~ ^[A-Za-z0-9_.:+-][A-Za-z0-9_.:+\- ]*$ ]]; then
    echo "error: font name contains invalid characters" >&2
    exit 1
fi

./metalogo.sh 65 0.95 45 "${color:1}" "${background}" > logo.svg

convert -background white logo.svg logo.png

convert -fill "$color" -stroke "$color" -font "$font" -pointsize 200 label:Metatooth metatooth.png

convert -size 50x202 canvas:white spacer.png

convert -size 130x17 canvas:white top.png

convert -append top.png logo.png padded-logo.png

convert +append spacer.png metatooth.png padded-logo.png spacer.png temp.png

convert temp.png -transparent white metatooth.png
