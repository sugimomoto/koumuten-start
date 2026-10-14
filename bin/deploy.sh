#!/usr/bin/env bash
# テーマ（koumuten）と自作プラグイン（koumuten-core）を、rsync で公開先のサーバーへ送る。
# 送るのはこの2つのフォルダだけ。WordPress 本体・画像（uploads）・ほかのプラグインには触らない。
# 使い方:
#   bash bin/deploy.sh          … 送る
#   bash bin/deploy.sh --dry-run … 何が送られるかを表示するだけ（サーバーは変わらない）
set -euo pipefail
cd "$(dirname "$0")/.."
source bin/deploy-config.sh

dry_run=()
if [ "${1:-}" = "--dry-run" ]; then
  dry_run=(--dry-run)
  echo "** ドライラン：表示だけで、サーバーには書き込みません"
fi

remote="$DEPLOY_USER@$DEPLOY_SERVER:$DEPLOY_WP_PATH/wp-content"
ssh_cmd="ssh -i $DEPLOY_KEY -o IdentitiesOnly=yes -o BatchMode=yes -p $DEPLOY_PORT"

# --delete は「手元で消したファイルをサーバーからも消す」ため。対象は下の2フォルダの中だけ。
# --checksum は「更新日時ではなく中身で比べる」ため（手元と GitHub Actions の両方から送るので、日時はあてにならない）。
for dir in themes/koumuten plugins/koumuten-core; do
  echo "== $dir → $DEPLOY_SERVER"
  rsync -rlptvz --checksum --delete ${dry_run[@]+"${dry_run[@]}"} --exclude='.DS_Store' \
    -e "$ssh_cmd" "wp-content/$dir/" "$remote/$dir/"
done

echo "完了: https://$DEPLOY_SERVER/"
