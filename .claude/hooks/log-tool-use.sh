#!/bin/bash
# PostToolUse hook: Claude が実行したコマンド／MCP呼び出しを docs/command-log.md に時刻付きで追記する。
# APIキー・トークン・パスワードらしき値は伏せ字にしてから書き込む。
# 記録の失敗で Claude の作業を止めないよう、常に exit 0 で終わる。

LOG_FILE="${CLAUDE_PROJECT_DIR:-.}/docs/command-log.md"

input=$(cat)
tool=$(printf '%s' "$input" | jq -r '.tool_name // "unknown"')

if [ "$tool" = "Bash" ]; then
  detail=$(printf '%s' "$input" | jq -r '.tool_input.command // ""')
else
  detail=$(printf '%s' "$input" | jq -c '.tool_input // {}')
fi

# 伏せ字化（XServer APIキー、KEY/TOKEN/SECRET/PASSWORD 系の値、Bearer、sk- 形式、JSON内のキー類）
redacted=$(printf '%s' "$detail" | perl -0pe '
  s/xs_[A-Za-z0-9_\-]+/xs_***REDACTED***/g;
  s/sk-[A-Za-z0-9_\-]{10,}/sk-***REDACTED***/g;
  s/(Bearer\s+)[^\s"\x27]+/$1***REDACTED***/gi;
  s/(\b[A-Z0-9_]*(?:API_?KEY|TOKEN|SECRET|PASSWORD|PASSWD)[A-Z0-9_]*\s*[=:]\s*)("[^"]*"|\x27[^\x27]*\x27|\S+)/$1***REDACTED***/gi;
  s/("[^"]*(?:key|token|secret|password)[^"]*"\s*:\s*")[^"]*"/$1***REDACTED***"/gi;
')

# 長すぎる内容は切り詰める
if [ ${#redacted} -gt 800 ]; then
  redacted="${redacted:0:800} …(省略)"
fi

if [ ! -f "$LOG_FILE" ]; then
  mkdir -p "$(dirname "$LOG_FILE")"
  printf '# コマンドログ（Hooks による自動記録）\n\n> PostToolUse フックが自動で追記します。手で編集しないでください。\n> 秘密情報らしき値は `***REDACTED***` に置き換えています。\n\n' > "$LOG_FILE"
fi

{
  printf -- '- `%s` **%s**\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$tool"
  printf '  ```\n'
  printf '%s\n' "$redacted" | sed 's/^/  /'
  printf '  ```\n'
} >> "$LOG_FILE"

exit 0
