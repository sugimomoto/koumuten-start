#!/usr/bin/env bash
# 本番（XServer）の WordPress で WP-CLI を実行する。設定は bin/deploy-config.sh。
# 使い方：bash bin/wp-remote.sh option get siteurl
set -euo pipefail
cd "$(dirname "$0")/.."
source bin/deploy-config.sh
# 引数（日本語・改行・引用符を含む本文など）を、サーバー側のシェルでもそのまま渡せるように1つずつエスケープする
"${DEPLOY_SSH[@]}" "cd $DEPLOY_WP_PATH && $DEPLOY_PHP \$(command -v wp) $(printf '%q ' "$@")"
