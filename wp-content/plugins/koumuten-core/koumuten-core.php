<?php
/**
 * Plugin Name: Koumuten Core
 * Description: 山田工務店サイトの「データと機能」。施工事例（カスタム投稿タイプ・カスタムフィールド）と、お問い合わせの受付を受け持つ。テーマを替えてもデータが消えないように、ここに置く。
 * Version:     0.1.0
 * Author:      山田工務店サイト制作
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Text Domain: koumuten-core
 *
 * @package Koumuten_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'KOUMUTEN_CORE_VERSION', '0.1.0' );
define( 'KOUMUTEN_CORE_DIR', plugin_dir_path( __FILE__ ) );

/**
 * 読み込む機能のファイル。
 * ファイルの読み込みだけを行い、重い処理はそれぞれのクラスがフックの中で動かす。
 */
foreach ( array(
	'includes/class-koumuten-work.php',
	'includes/class-koumuten-work-fields.php',
	'includes/class-koumuten-inquiry.php',
	'includes/class-koumuten-contact-form.php',
) as $koumuten_core_file ) {
	$koumuten_core_path = KOUMUTEN_CORE_DIR . $koumuten_core_file;
	if ( file_exists( $koumuten_core_path ) ) {
		require_once $koumuten_core_path;
	}
}
unset( $koumuten_core_file, $koumuten_core_path );

if ( class_exists( 'Koumuten_Work' ) ) {
	Koumuten_Work::init();
}
if ( class_exists( 'Koumuten_Work_Fields' ) ) {
	Koumuten_Work_Fields::init();
}
if ( class_exists( 'Koumuten_Inquiry' ) ) {
	Koumuten_Inquiry::init();
}
if ( class_exists( 'Koumuten_Contact_Form' ) ) {
	Koumuten_Contact_Form::init();
}

/**
 * 有効にしたとき：投稿タイプを登録したあとに URL の決まりを作り直す。
 * （この順でないと /works/ が 404 になる）
 */
function koumuten_core_activate() {
	if ( class_exists( 'Koumuten_Work' ) ) {
		Koumuten_Work::register();
	}
	if ( class_exists( 'Koumuten_Inquiry' ) ) {
		Koumuten_Inquiry::register();
	}
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'koumuten_core_activate' );

/**
 * 無効にしたとき：URL の決まりを作り直す。投稿データは消さない。
 */
function koumuten_core_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'koumuten_core_deactivate' );
