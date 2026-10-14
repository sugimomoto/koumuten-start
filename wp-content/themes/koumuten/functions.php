<?php
/**
 * テーマのセットアップ。見た目に関わることだけを置く。
 * （施工事例・お問い合わせなどのデータと機能は、自作プラグイン koumuten-core にある）
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

/**
 * テーマが使う機能を宣言する。
 */
function koumuten_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
	);

	register_nav_menus(
		array(
			'primary' => 'メインメニュー',
		)
	);
}
add_action( 'after_setup_theme', 'koumuten_setup' );

/**
 * スタイルと書体を読み込む。
 */
function koumuten_enqueue_assets() {
	// 見出し＝明朝（Shippori Mincho）／本文＝ゴシック（Zen Kaku Gothic New）。
	wp_enqueue_style(
		'koumuten-fonts',
		'https://fonts.googleapis.com/css2?family=Shippori+Mincho:wght@600;700&family=Zen+Kaku+Gothic+New:wght@400;500;700&display=swap',
		array(),
		null
	);

	wp_enqueue_style(
		'koumuten-style',
		get_stylesheet_uri(),
		array( 'koumuten-fonts' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'koumuten_enqueue_assets' );

/**
 * 電話番号（表示用）。管理画面からあとで差し替えられるよう、オプションに持つ。
 *
 * @return string 例：03-0000-0000
 */
function koumuten_phone() {
	return (string) get_option( 'koumuten_phone', '' );
}

/**
 * 電話番号（tel: リンク用）。数字だけにする。
 *
 * @return string 例：tel:0300000000
 */
function koumuten_phone_href() {
	$digits = preg_replace( '/[^0-9+]/', '', koumuten_phone() );
	return $digits ? 'tel:' . $digits : '';
}

/**
 * 住所（表示用）。
 *
 * @return string
 */
function koumuten_address() {
	return (string) get_option( 'koumuten_address', '' );
}

/**
 * 施工事例の投稿タイプ名。プラグインが無くても画面が壊れないようにする。
 *
 * @return string
 */
function koumuten_work_post_type() {
	return class_exists( 'Koumuten_Work' ) ? Koumuten_Work::POST_TYPE : 'koumuten_work';
}

/**
 * 施工事例の「種類」のタクソノミー名。
 *
 * @return string
 */
function koumuten_work_taxonomy() {
	return class_exists( 'Koumuten_Work' ) ? Koumuten_Work::TAXONOMY : 'koumuten_work_type';
}

/**
 * 施工事例の、種類の名前（1件目）。
 *
 * @param int $post_id 投稿 ID。
 * @return string 種類の名前。無ければ空。
 */
function koumuten_work_type_name( $post_id ) {
	$terms = get_the_terms( $post_id, koumuten_work_taxonomy() );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}
	return $terms[0]->name;
}

/**
 * 費用の表示（R-02）。値があれば「約○○万円」、無ければ空文字。
 * 空文字が返ったら、呼ぶ側は費用の行そのものを出さない。
 *
 * @param int $post_id 投稿 ID。
 * @return string
 */
function koumuten_work_cost_text( $post_id ) {
	if ( ! function_exists( 'koumuten_work_field' ) ) {
		return '';
	}
	$cost = koumuten_work_field( 'cost', $post_id );
	if ( '' === $cost || null === $cost ) {
		return '';
	}
	$cost = (int) $cost;
	if ( $cost <= 0 ) {
		return '';
	}
	return '約' . number_format_i18n( $cost ) . '万円';
}

/**
 * 施工事例のカスタムフィールドを読む（プラグインが無くても落ちないように）。
 *
 * @param string $key     area / duration / building_age / cost。
 * @param int    $post_id 投稿 ID。
 * @return string
 */
function koumuten_field( $key, $post_id ) {
	if ( ! function_exists( 'koumuten_work_field' ) ) {
		return '';
	}
	return (string) koumuten_work_field( $key, $post_id );
}
