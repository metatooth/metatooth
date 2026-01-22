#!/bin/bash

pi=`echo "4*a(1)" | bc -l`
rad=`echo "60*($pi/180)" | bc -l`

if [ $# -ne 5 ]
then
    echo "usage: metalogo.sh <radius> <ratio> <theta> <primary> <secondary>"
    echo
    echo "  Outputs a SVG of width & height twice <radius>. Draw a circle "
    echo "  of <radius>. Inset a triangle inscribed within a"
    echo "  circle of <radius>*<ratio>. Then rotate the triangle by <theta>."
    echo "  Use <primary> and <secondary> colors."
    echo
    echo "  for example, metalogo.sh 256 0.95 45 000000 ffffff"
    exit
fi

radius=$1
ratio=$2
theta=$3
primary=$4
secondary=$5

center_x=$radius
center_y=$radius

R=`echo "$radius * $ratio" | bc -l`

delta_y=`echo "$radius - $R" | bc -l`

tan_sixty=`echo "s($rad)/c($rad)" | bc -l`
tan_thirty=`echo "s($rad/2)/c($rad/2)" | bc -l`
sine_thirty=`echo "s($rad/2)" | bc -l`

sine_theta=`echo "s($theta*($pi/180))" | bc -l`
cosine_theta=`echo "c($theta*($pi/180))" | bc -l`

# Calculate big triangle from R

X=`echo "$R * $sine_thirty" | bc -l`
B=`echo "$X / $tan_thirty" | bc -l`
H=`echo "$B * $tan_sixty" | bc -l`

# Small triangle is 1/4 height of big triangle

h=`echo "$H / 4" | bc -l`

b=`echo "$h / $tan_sixty" | bc -l`
x=`echo "$b * $tan_thirty" | bc -l`
r=`echo "$x / $sine_thirty" | bc -l`


# A #

x=`echo "$center_x - $center_x" | bc -l`
y=`echo "$delta_y - $center_y" | bc -l`

ax=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
ay=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

ax=`echo "$ax + $center_x" | bc -l`
ay=`echo "$ay + $center_y" | bc -l`


# B #

x=`echo "$center_x - $b - $center_x" | bc -l`
y=`echo "$h + $delta_y - $center_y" | bc -l`

bx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
by=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

bx=`echo "$bx + $center_x" | bc -l`
by=`echo "$by + $center_y" | bc -l`


# C #

x=`echo "$center_x + $b - $center_x" | bc -l`
y=`echo "$h + $delta_y - $center_y" | bc -l`

cx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
cy=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

cx=`echo "$cx + $center_x" | bc -l`
cy=`echo "$cy + $center_y" | bc -l`


# D #

x=`echo "$center_x - (2 * $b) - $center_x" | bc -l`
y=`echo "(2 * $h) + $delta_y - $center_y" | bc -l`

dx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
dy=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

dx=`echo "$dx + $center_x" | bc -l`
dy=`echo "$dy + $center_y" | bc -l`


# E #

x=`echo "$center_x - $center_x" | bc -l`
y=`echo "(2 * $h) + $delta_y - $center_y" | bc -l`

ex=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
ey=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

ex=`echo "$ex + $center_x" | bc -l`
ey=`echo "$ey + $center_y" | bc -l`


# F #

x=`echo "$center_x + (2 * $b) - $center_x" | bc -l`
y=`echo "(2 * $h) + $delta_y - $center_y" | bc -l`

fx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
fy=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

fx=`echo "$fx + $center_x" | bc -l`
fy=`echo "$fy + $center_y" | bc -l`


# G #

x=`echo "$center_x - (3 * $b) - $center_x" | bc -l`
y=`echo "(3 * $h) + $delta_y - $center_y" | bc -l`

gx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
gy=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

gx=`echo "$gx + $center_x" | bc -l`
gy=`echo "$gy + $center_y" | bc -l`


# H #

x=`echo "$center_x - $b - $center_x" | bc -l`
y=`echo "(3 * $h) + $delta_y - $center_y" | bc -l`

hx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
hy=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

hx=`echo "$hx + $center_x" | bc -l`
hy=`echo "$hy + $center_y" | bc -l`


# I #

x=`echo "$center_x + $b - $center_x" | bc -l`
y=`echo "(3 * $h) + $delta_y - $center_y" | bc -l`

ix=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
iy=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

ix=`echo "$ix + $center_x" | bc -l`
iy=`echo "$iy + $center_y" | bc -l`


# J #

x=`echo "$center_x + (3 * $b) - $center_x" | bc -l`
y=`echo "(3 * $h) + $delta_y - $center_y" | bc -l`

jx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
jy=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

jx=`echo "$jx + $center_x" | bc -l`
jy=`echo "$jy + $center_y" | bc -l`


# K #

x=`echo "$center_x - (4 * $b) - $center_x" | bc -l`
y=`echo "(4 * $h) + $delta_y - $center_y" | bc -l`

kx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
ky=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

kx=`echo "$kx + $center_x" | bc -l`
ky=`echo "$ky + $center_y" | bc -l`


# L #

x=`echo "$center_x - (2 * $b) - $center_x" | bc -l`
y=`echo "(4 * $h) + $delta_y - $center_y" | bc -l`

lx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
ly=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

lx=`echo "$lx + $center_x" | bc -l`
ly=`echo "$ly + $center_y" | bc -l`


# M #

x=`echo "$center_x - $center_x" | bc -l`
y=`echo "(4 * $h) + $delta_y - $center_y" | bc -l`

mx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
my=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

mx=`echo "$mx + $center_x" | bc -l`
my=`echo "$my + $center_y" | bc -l`


# N #

x=`echo "$center_x + (2 * $b) - $center_x" | bc -l`
y=`echo "(4 * $h) + $delta_y - $center_y" | bc -l`

nx=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
ny=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

nx=`echo "$nx + $center_x" | bc -l`
ny=`echo "$ny + $center_y" | bc -l`


# O #

x=`echo "$center_x + (4 * $b) - $center_x" | bc -l`
y=`echo "(4 * $h) + $delta_y - $center_y" | bc -l`

ox=`echo "($x * $cosine_theta) - ($y * $sine_theta)" | bc -l`
oy=`echo "($x * $sine_theta) + ($y * $cosine_theta)" | bc -l`

ox=`echo "$ox + $center_x" | bc -l`
oy=`echo "$oy + $center_y" | bc -l`

height=`echo "2*$radius" | bc -l`
width=`echo "2*$radius" | bc -l`

cat << EOF
<svg height="$height" width="$width">
  <!-- Copyright Metatooth LLC 2020. -->
  <rect height="$height" width="$width" style="fill:#$secondary;stroke-width=1;stroke=#$secondary"/>
  <circle r="$radius" cx="$center_x" cy="$center_y" style="fill:#$primary;stroke-width=1;stroke=#$primary"/>
  <polygon points="$ax,$ay $ox,$oy $kx,$ky" style="fill:#$secondary;stroke-width=1;stroke=#$secondary"/>
  <polygon points="$dx,$dy $fx,$fy $mx,$my" style="fill:#$primary;stroke-width=1;stroke=#$primary"/>
  <polygon points="$bx,$by $cx,$cy $ex,$ey" style="fill:#$primary;stroke-width=1;stroke=#$primary"/>
  <polygon points="$gx,$gy $hx,$hy $lx,$ly" style="fill:#$primary;stroke-width=1;stroke=#$primary"/>
  <polygon points="$ix,$iy $jx,$jy $nx,$ny" style="fill:#$primary;stroke-width=1;stroke=#$primary"/>
  <polygon points="$hx,$hy $ix,$iy $ex,$ey" style="fill:#$secondary;stroke-width=1;stroke=#$secondary"/>
</svg>
EOF
