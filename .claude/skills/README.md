# 取り込んだ Agent Skills

WordPress 公式の [WordPress/agent-skills](https://github.com/WordPress/agent-skills)（GPL-2.0-or-later）から、このプロジェクトで使うものだけを取り込んでいます。

- 取り込み元コミット：`f1bac1f`（2026-09-25）
- 取り込み日：2026-09-26

| スキル | 使う場面 |
| --- | --- |
| `wp-plugin-development` | カスタム投稿・カスタムフィールド・フォームの実装（フック、セキュリティの作法） |
| `wp-wpcli-and-ops` | WP-CLI でのセットアップ・公開作業（本番での安全手順） |
| `wp-env` | ローカル環境（wp-env）の設定とトラブル対応 |

更新するときは、元リポジトリから同じフォルダを上書きコピーし、上のコミットを書き換えます。
