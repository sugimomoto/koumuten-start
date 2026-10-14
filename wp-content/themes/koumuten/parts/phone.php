<?php
/**
 * 電話番号（R-09）。ヘッダー・各ページ下のバンド・フッターの3か所で使い回す。
 *
 * @param string $args['variant'] header / band / footer。
 * @param string $args['label']   小さく出す文字。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

$koumuten_variant = isset( $args['variant'] ) ? $args['variant'] : 'band';
$koumuten_label   = isset( $args['label'] ) ? $args['label'] : 'タップで電話';
$koumuten_phone   = koumuten_phone();
$koumuten_href    = koumuten_phone_href();

if ( ! $koumuten_phone || ! $koumuten_href ) {
	return;
}
?>
<a class="tel tel--<?php echo esc_attr( $koumuten_variant ); ?>" href="<?php echo esc_url( $koumuten_href ); ?>">
	<span class="tel__label"><?php echo esc_html( $koumuten_label ); ?></span>
	<span class="tel__number"><?php echo esc_html( $koumuten_phone ); ?></span>
</a>
