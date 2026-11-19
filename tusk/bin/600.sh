#!/usr/bin/bash
#
# Stage 600: build the print-ready shell for one plan.
#
# Reads  <plan dir>/500-Mouthguard-Export/mesh.stl   (written by xmppbot)
# Writes <plan dir>/600-Mouthguard-PrintReady/shell.stl
#
# Exits non-zero if any step fails or the output is not produced.

set -euo pipefail

if [ "$#" -ne 1 ]; then
    echo "usage: 600.sh <plan dir>" >&2
    exit 2
fi

PLAN=$1

APP_DIR="${APP_DIR:-/app}"
MSHLB="${MSHLB:-$HOME/metaspace/meshlab/distrib/meshlabserver}"
TUSK="${TUSK:-$APP_DIR/_build/bin/tusk/tusk}"
ETC="${ETC:-$APP_DIR/etc}"

IN="$PLAN/500-Mouthguard-Export/mesh.stl"
OUTDIR="$PLAN/600-Mouthguard-PrintReady"
OUT="$OUTDIR/shell.stl"

if [ ! -d "$PLAN" ]; then
    echo "$PLAN is not a directory" >&2
    exit 1
fi

if [ ! -f "$IN" ]; then
    echo "missing input $IN" >&2
    exit 1
fi

mkdir -p "$OUTDIR"
rm -f "$OUT"

WORK="$(mktemp -d)"
trap 'rm -rf "$WORK"' EXIT
LOG="$OUTDIR/meshlab.log"

"$MSHLB" -l "$LOG" -i "$IN" -o "$WORK/100.ply" -m vn fn
"$TUSK" convention "$WORK/100.ply" "$WORK/300.ply"
"$TUSK" normalize "$WORK/300.ply" "$WORK/400.ply"
"$MSHLB" -l "$LOG" -i "$WORK/400.ply" -o "$OUT" -m vn fn -s "$ETC/500-shell.mlx"

if [ ! -s "$OUT" ]; then
    echo "$OUT was not produced" >&2
    exit 1
fi
