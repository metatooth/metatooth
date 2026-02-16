while true; do
    git reset --hard HEAD
    cat PROMPT.md | claude -p \
        --dangerously-skip-permissions \
        --output-format=stream-json \
        --verbose \
        | npx repomirror visualize
    echo -n "================================LOOP=============================="
    sleep 10
done
