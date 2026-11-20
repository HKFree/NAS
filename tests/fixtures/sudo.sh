#!/bin/sh

case "$1" in
    ../app/scripts/testnfs.sh|../app/scripts/exportnfs.sh|../app/scripts/exportrsync.sh)
        printf '%s\n' "$1" >> "$NAS_MOCK_COMMANDS_LOG"
        printf 'ok\n'
        ;;
    *)
        printf 'Unexpected privileged command\n' >&2
        exit 1
        ;;
esac
