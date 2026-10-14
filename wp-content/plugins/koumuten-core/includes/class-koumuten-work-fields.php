<?php
/**
 * 施工事例のカスタムフィールド（場所・工期・築年数・費用）。
 * 入力は管理画面のメタボックス。保存のときに nonce 検証・権限チェック・サニタイズをする。
 *
 * @package Koumuten_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * 施工事例のカスタムフィールド。
 */
class Koumuten_Work_Fields {

	const NONCE_NAME   = 'koumuten_work_fields_nonce';
	const NONCE_ACTION = 'koumuten_work_fields_save';

	/**
	 * フィールドの一覧。
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'area'         => array(
				'key'   => '_koumuten_area',
				'label' => '場所',
				'type'  => 'text',
				'note'  => 'お客さまのことを考えて、区・市までにします（例：東京都世田谷区）',
			),
			'duration'     => array(
				'key'   => '_koumuten_duration',
				'label' => '工期',
				'type'  => 'text',
				'note'  => '例：約3か月',
			),
			'building_age' => array(
				'key'   => '_koumuten_building_age',
				'label' => '築年数',
				'type'  => 'text',
				'note'  => '例：築32年',
			),
			'cost'         => array(
				'key'   => '_koumuten_cost',
				'label' => '費用（万円）',
				'type'  => 'number',
				'note'  => '数字だけを入れると「約○○万円」と出ます。出したくない事例は空にしてください（空のときは費用を表示しません）',
			),
		);
	}

	/**
	 * フックを登録する。
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes_' . Koumuten_Work::POST_TYPE, array( __CLASS__, 'add_box' ) );
		add_action( 'save_post_' . Koumuten_Work::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
	}

	/**
	 * データの形（型とサニタイズ）を宣言する。
	 */
	public static function register_meta() {
		foreach ( self::fields() as $field ) {
			register_post_meta(
				Koumuten_Work::POST_TYPE,
				$field['key'],
				array(
					'type'              => 'number' === $field['type'] ? 'integer' : 'string',
					'single'            => true,
					'show_in_rest'      => false,
					'sanitize_callback' => 'number' === $field['type'] ? 'absint' : 'sanitize_text_field',
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}

	/**
	 * 入力欄（メタボックス）を置く。
	 */
	public static function add_box() {
		add_meta_box(
			'koumuten-work-fields',
			'施工事例のくわしい内容',
			array( __CLASS__, 'render_box' ),
			Koumuten_Work::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * 入力欄を描く。
	 *
	 * @param WP_Post $post いま編集している投稿。
	 */
	public static function render_box( $post ) {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		echo '<div class="koumuten-fields">';
		foreach ( self::fields() as $name => $field ) {
			$id    = 'koumuten_' . $name;
			$value = get_post_meta( $post->ID, $field['key'], true );
			printf(
				'<p style="margin:0 0 18px"><label for="%1$s" style="display:block;font-weight:600;margin-bottom:4px">%2$s</label>'
				. '<input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="widefat"%5$s>'
				. '<span class="description" style="display:block;margin-top:4px">%6$s</span></p>',
				esc_attr( $id ),
				esc_html( $field['label'] ),
				esc_attr( 'number' === $field['type'] ? 'number' : 'text' ),
				esc_attr( $value ),
				'number' === $field['type'] ? ' min="0" step="1"' : '',
				esc_html( $field['note'] )
			);
		}
		echo '</div>';
	}

	/**
	 * 保存する。
	 *
	 * @param int     $post_id 投稿 ID。
	 * @param WP_Post $post    投稿。
	 */
	public static function save( $post_id, $post ) {
		// 自動保存のときは保存しない。
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// nonce の検証。
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		// 権限の確認。
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		foreach ( self::fields() as $name => $field ) {
			if ( ! isset( $_POST[ 'koumuten_' . $name ] ) ) {
				continue;
			}
			$raw = wp_unslash( $_POST[ 'koumuten_' . $name ] );

			if ( 'number' === $field['type'] ) {
				// 費用：空なら「入っていない状態」にする（R-02）。
				if ( '' === trim( (string) $raw ) ) {
					delete_post_meta( $post_id, $field['key'] );
					continue;
				}
				update_post_meta( $post_id, $field['key'], absint( $raw ) );
				continue;
			}

			$value = sanitize_text_field( $raw );
			if ( '' === $value ) {
				delete_post_meta( $post_id, $field['key'] );
				continue;
			}
			update_post_meta( $post_id, $field['key'], $value );
		}
	}
}

/**
 * 施工事例のカスタムフィールドを読む。テーマから使う。
 *
 * @param string $name    area / duration / building_age / cost。
 * @param int    $post_id 投稿 ID。0 ならいまの投稿。
 * @return string 入っていなければ空文字。
 */
function koumuten_work_field( $name, $post_id = 0 ) {
	$fields = Koumuten_Work_Fields::fields();
	if ( ! isset( $fields[ $name ] ) ) {
		return '';
	}
	$post_id = $post_id ? (int) $post_id : (int) get_the_ID();
	if ( ! $post_id ) {
		return '';
	}
	$value = get_post_meta( $post_id, $fields[ $name ]['key'], true );
	return ( null === $value ) ? '' : (string) $value;
}
