<?php
/**
 * 施工事例（R-01）のデータ。
 * カスタム投稿タイプ koumuten_work と、種類のタクソノミー koumuten_work_type。
 * テーマを替えてもデータが消えないように、プラグイン側に置く。
 *
 * @package Koumuten_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * 施工事例の投稿タイプとタクソノミー。
 */
class Koumuten_Work {

	const POST_TYPE = 'koumuten_work';
	const TAXONOMY  = 'koumuten_work_type';

	/**
	 * フックを登録する。
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * 投稿タイプとタクソノミーを登録する。
	 * タクソノミーを先に登録する（投稿タイプに結びつけるため）。
	 */
	public static function register() {
		register_taxonomy(
			self::TAXONOMY,
			array( self::POST_TYPE ),
			array(
				'labels'            => array(
					'name'          => '種類',
					'singular_name' => '種類',
					'menu_name'     => '種類',
					'all_items'     => 'すべての種類',
					'edit_item'     => '種類を編集',
					'view_item'     => '種類を見る',
					'update_item'   => '種類を更新',
					'add_new_item'  => '種類を追加',
					'new_item_name' => '新しい種類の名前',
					'search_items'  => '種類を探す',
					'not_found'     => '種類が見つかりません',
				),
				'public'            => true,
				'hierarchical'      => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'work-type',
					'with_front' => false,
				),
			)
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'       => array(
					'name'               => '施工事例',
					'singular_name'      => '施工事例',
					'menu_name'          => '施工事例',
					'all_items'          => '施工事例一覧',
					'add_new'            => '新規追加',
					'add_new_item'       => '施工事例を追加',
					'edit_item'          => '施工事例を編集',
					'new_item'           => '新しい施工事例',
					'view_item'          => '施工事例を見る',
					'view_items'         => '施工事例を見る',
					'search_items'       => '施工事例を探す',
					'not_found'          => '施工事例がありません',
					'not_found_in_trash' => 'ゴミ箱に施工事例はありません',
					'featured_image'     => '写真',
					'set_featured_image' => '写真を設定する',
					'archives'           => '施工事例の一覧',
				),
				'description'  => 'これまでに手がけた住まい。写真・場所・工期・築年数・費用を載せる。',
				'public'       => true,
				'has_archive'  => true,
				'menu_icon'    => 'dashicons-hammer',
				'menu_position' => 5,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
				'show_in_rest' => true,
				'taxonomies'   => array( self::TAXONOMY ),
				'rewrite'      => array(
					'slug'       => 'works',
					'with_front' => false,
				),
			)
		);
	}
}
