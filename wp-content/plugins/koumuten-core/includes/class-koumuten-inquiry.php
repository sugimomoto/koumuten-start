<?php
/**
 * お問い合わせの保存先。
 * メールが届かなくても内容が残るように、サイト側にも保存する。
 * 管理者だけが管理画面で読める（来訪者には見えない）。
 *
 * @package Koumuten_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * お問い合わせの投稿タイプ。
 */
class Koumuten_Inquiry {

	const POST_TYPE = 'koumuten_inquiry';

	const META = array(
		'email' => '_koumuten_inq_email',
		'tel'   => '_koumuten_inq_tel',
		'type'  => '_koumuten_inq_type',
		'body'  => '_koumuten_inq_body',
	);

	/**
	 * フックを登録する。
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'add_box' ) );
	}

	/**
	 * 投稿タイプを登録する。来訪者からは見えない。
	 */
	public static function register() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => 'お問い合わせ',
					'singular_name' => 'お問い合わせ',
					'menu_name'     => 'お問い合わせ',
					'all_items'     => 'お問い合わせ一覧',
					'edit_item'     => 'お問い合わせの内容',
					'search_items'  => 'お問い合わせを探す',
					'not_found'     => 'お問い合わせはありません',
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-email',
				'menu_position'       => 6,
				'supports'            => array( 'title' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'capabilities'        => array(
					'create_posts' => 'do_not_allow',
				),
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * 内容を読むための欄を置く。
	 */
	public static function add_box() {
		add_meta_box(
			'koumuten-inquiry',
			'お問い合わせの内容',
			array( __CLASS__, 'render_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * 内容を描く（読むだけ）。
	 *
	 * @param WP_Post $post いま見ている投稿。
	 */
	public static function render_box( $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}
		$rows = array(
			'メールアドレス'   => get_post_meta( $post->ID, self::META['email'], true ),
			'電話番号'         => get_post_meta( $post->ID, self::META['tel'], true ),
			'ご相談の種類'     => get_post_meta( $post->ID, self::META['type'], true ),
			'お問い合わせ内容' => get_post_meta( $post->ID, self::META['body'], true ),
		);
		echo '<table class="widefat striped"><tbody>';
		foreach ( $rows as $label => $value ) {
			printf(
				'<tr><th style="width:180px">%s</th><td>%s</td></tr>',
				esc_html( $label ),
				nl2br( esc_html( (string) $value ) )
			);
		}
		echo '</tbody></table>';
	}

	/**
	 * 問い合わせを1件保存する。
	 *
	 * @param array $data サニタイズ済みの入力。
	 * @return int 投稿 ID。失敗したら 0。
	 */
	public static function save( array $data ) {
		$post_id = wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $data['name'],
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		foreach ( self::META as $name => $key ) {
			update_post_meta( $post_id, $key, $data[ $name ] );
		}

		return (int) $post_id;
	}
}
