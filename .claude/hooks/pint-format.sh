#!/bin/sh
# PostToolUse: formatea con Pint el archivo PHP que Claude acaba de editar.
file=$(jq -r '.tool_input.file_path // empty')

case "$file" in
    *.php) ;;
    *) exit 0 ;;
esac

[ -f "$file" ] || exit 0
cd "$CLAUDE_PROJECT_DIR" && vendor/bin/pint --quiet "$file"
