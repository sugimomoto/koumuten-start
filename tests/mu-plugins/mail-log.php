<?php
/**
 * Plugin Name: メール記録（E2E テスト用・ローカル専用）
 * Description: wp_mail で送られるメールを、送信せずにオプションへ記録する。E2E テストがここを読んで「メールで知らせが届く」ことを確かめる。
 *
 * これは mu-plugins（必ず使うプラグイン）として、ローカルの wp-env にだけ置かれる。
 * bin/deploy.sh はテーマ（koumuten）と自作プラグイン（koumuten-core）しか送らないので、本番には入らない。
 *
 * @package Koumuten\Tests
 */

defined( 'ABSPATH' ) || exit;

const KOUMUTEN_MAIL_LOG_OPTION = 'koumuten_mail_log';

/**
 * wp_mail を実際には送らず、内容を記録する。
 *
 * @param null|bool $short_circuit もとの戻り値（null なら通常送信）。
 * @param array     $atts          wp_mail に渡された引数。
 * @return bool 送信できたことにする。
 */
function koumuten_mail_log( $short_circuit, $atts ) {
	$log = get_option( KOUMUTEN_MAIL_LOG_OPTION, array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}

	$to = isset( $atts['to'] ) ? $atts['to'] : '';
	$log[] = array(
		'to'      => is_array( $to ) ? implode( ',', $to ) : (string) $to,
		'subject' => isset( $atts['subject'] ) ? (string) $atts['subject'] : '',
		'message' => isset( $atts['message'] ) ? (string) $atts['message'] : '',
		'at'      => time(),
	);

	// 直近の 20 件だけ残す。
	$log = array_slice( $log, -20 );
	update_option( KOUMUTEN_MAIL_LOG_OPTION, $log, false );

	return true;
}
add_filter( 'pre_wp_mail', 'koumuten_mail_log', 10, 2 );
