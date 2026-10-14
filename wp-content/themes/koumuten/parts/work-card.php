<?php
/**
 * 施工事例の一覧に並ぶ1件（写真＋種類＋タイトル）。
 * 一覧・絞り込み後・トップページで使い回す。
 *
 * @param string $args['heading'] 見出しのレベル（h2 / h3）。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

$koumuten_heading = isset( $args['heading'] ) ? $args['heading'] : 'h2';
$koumuten_type    = koumuten_work_type_name( get_the_ID() );
?>
<li>
	<a class="work-card" href="<?php the_permalink(); ?>">
		<div class="photo">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'large', array( 'alt' => '' ) ); ?>
			<?php else : ?>
				<span class="photo__label">写真</span>
			<?php endif; ?>
		</div>
		<div class="work-card__body">
			<?php if ( $koumuten_type ) : ?>
				<span class="badge"><?php echo esc_html( $koumuten_type ); ?></span>
			<?php endif; ?>
			<<?php echo esc_html( $koumuten_heading ); ?> class="work-card__title"><?php the_title(); ?></<?php echo esc_html( $koumuten_heading ); ?>>
		</div>
	</a>
</li>
