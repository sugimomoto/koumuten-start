# 公開先（XServer WordPress）の設定。bin/deploy.sh と bin/wp-remote.sh が読み込む。
# 無料お試しを取り直すなどでサーバーが変わったら、ここの5行だけを書き換える。
# （環境変数で上書きもできる。例: DEPLOY_SERVER=xw123456.xwp.jp bash bin/deploy.sh）
# パスワードや鍵の中身は書かない。鍵は「置き場所」だけを指定する。

DEPLOY_SERVER="${DEPLOY_SERVER:-xw832954.xwp.jp}"              # サーバー名（＝サイトのドメイン）
DEPLOY_USER="${DEPLOY_USER:-xw832954}"                         # SSH のユーザー名（＝サーバーID）
DEPLOY_KEY="${DEPLOY_KEY:-$HOME/.ssh/xserver_koumuten}"        # SSH の秘密鍵の置き場所
DEPLOY_WP_PATH="${DEPLOY_WP_PATH:-$DEPLOY_SERVER/public_html}"  # WordPress の場所（ホームからの相対パス）
# SSH で入ったときの `php` はサイトとは別の古い版（8.0）なので、WP-CLI はサイトと同じ版の PHP で動かす。
# サイトの PHP の版（MCP の get_wphosting_php_version やパネルで確認できる）に合わせる。
DEPLOY_PHP="${DEPLOY_PHP:-php8.3}"

DEPLOY_PORT=10022
DEPLOY_SSH=(ssh -i "$DEPLOY_KEY" -o IdentitiesOnly=yes -o BatchMode=yes -p "$DEPLOY_PORT" "$DEPLOY_USER@$DEPLOY_SERVER")
