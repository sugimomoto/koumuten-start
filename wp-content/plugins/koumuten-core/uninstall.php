<?php
/**
 * プラグインを「削除」したときの後片付け。
 *
 * 施工事例の投稿・問い合わせの投稿は消さない（お客さまのデータなので、WordPress の管理画面から消してもらう）。
 * このプラグインが作ったオプションだけを消す。
 *
 * @package Koumuten_Core
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'koumuten_phone' );
