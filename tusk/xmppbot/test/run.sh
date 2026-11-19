#!/usr/bin/bash
#
# End-to-end test of xmppbot against a throwaway local XMPP server
# (Prosody in Docker), a stub API/S3 (stub.py) and a fake stage script.
# Needs docker, python3 and `bundle install` done in ..
#
# Checks: backlog recovery, live items, no reprocessing on restart.

set -euo pipefail

HERE=$(cd "$(dirname "$0")" && pwd)
BOT_DIR=$(dirname "$HERE")
TMP=$(mktemp -d)
NAME=xmppbot-test-prosody
BOT_PID=""
STUB_PID=""

cleanup() {
    [ -n "${KEEP:-}" ] && docker logs "$NAME" 2>&1 | tail -15 >&2
    [ -n "$BOT_PID" ] && kill "$BOT_PID" 2>/dev/null || true
    [ -n "$STUB_PID" ] && kill "$STUB_PID" 2>/dev/null || true
    docker rm -f "$NAME" >/dev/null 2>&1 || true
    rm -rf "$TMP"
}
trap cleanup EXIT

fail() { [ -n "${KEEP:-}" ] && docker logs "$NAME" 2>&1 | tail -12 >&2; echo "FAIL: $*" >&2; grep -vE "NOTE:|Gem::Platform|DidYouMean|Ignoring prism" "$TMP/bot.log" >&2; tail -5 "$TMP/stub.log" >&2; exit 1; }

NODE=500-Mouthguard-Export
WORK="$TMP/work"
PROCESSED="$WORK/.processed"

docker rm -f "$NAME" >/dev/null 2>&1 || true
docker run -d --name "$NAME" -p 5222:5222 \
    -v "$HERE/prosody.cfg.lua:/etc/prosody/prosody.cfg.lua:ro" \
    prosody/prosody:latest >/dev/null
sleep 5
docker exec "$NAME" prosodyctl register bot localhost botpass
docker exec "$NAME" prosodyctl register pub localhost pubpass

python3 "$HERE/stub.py" 2>"$TMP/stub.log" &
STUB_PID=$!
sleep 1

cd "$BOT_DIR"
publish() { bundle exec ruby "$HERE/pub.rb" "$1" "$2" >/dev/null 2>&1; }

start_bot() {
    JABBER_ID=bot@localhost/bot JABBER_PASSWORD=botpass API_KEY=k \
    API_URL=http://localhost:8099 S3_BUCKET_NAME=testbucket \
    AWS_ACCESS_KEY_ID=x AWS_SECRET_ACCESS_KEY=x AWS_REGION=us-east-1 \
    ruby -rbundler/setup -r "$HERE/s3stub.rb" ${DBG:+-r $DBG} xmppbot.rb \
        -t pubsub.localhost -i "$NODE" -s "$HERE/fake600.sh" \
        -w "$WORK" -o 600-Mouthguard-PrintReady \
        >"$TMP/bot.log" 2>&1 &
    BOT_PID=$!
}

wait_count() { # wait_count N: wait until .processed has N lines
    for _ in $(seq 20); do
        [ -f "$PROCESSED" ] && [ "$(wc -l <"$PROCESSED")" -ge "$1" ] && return 0
        sleep 1
    done
    return 1
}

stop_bot() { kill -TERM "$BOT_PID"; wait "$BOT_PID" || true; BOT_PID=""; }

echo "backlog: publish p1 before the bot starts"
publish p1 r1
start_bot
wait_count 1 || fail "backlog item not processed"
[ -s "$WORK/p1/600-Mouthguard-PrintReady/shell.stl" ] || fail "no shell.stl for p1"

echo "live: publish p2 while the bot runs"
publish p2 r2
wait_count 2 || fail "live item not processed"

echo "restart: publish p3 while down, expect only p3 processed"
stop_bot
publish p3 r3
start_bot
wait_count 3 || fail "item published while down not recovered"
sleep 3
[ "$(wc -l <"$PROCESSED")" -eq 3 ] || fail "items were reprocessed"
grep -c "POST /assets" "$TMP/stub.log" | grep -qx 3 || fail "expected exactly 3 asset posts"

echo "PASS"
