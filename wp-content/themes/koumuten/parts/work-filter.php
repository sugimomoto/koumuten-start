<?php
/**
 * 施工事例の、種類での絞り込み（R-03）。
 * JavaScript は使わず、ただのリンクで /works/ と /work-type/○○/ を行き来する。
 * いま見ているものに aria-current="page" を付ける。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

$koumuten_tax   = koumuten_work_taxonomy();
$koumuten_terms = get_terms(
	array(
		'taxonomy'   => $koumuten_tax,
		'hide_empty' => false,
		'orderby'    => 'term_id',
		'order'      => 'ASC',
	)
);

if ( is_wp_error( $koumuten_terms ) || empty( $koumuten_terms ) ) {
	return;
}

$koumuten_current = is_tax( $koumuten_tax ) ? (int) get_queried_object_id() : 0;
?>
<nav class="filter" aria-label="種類で絞り込む">
	<h2 class="filter__heading">種類で絞り込む</h2>
	<ul class="filter__list">
		<li>
			<a class="filter__link" href="<?php echo esc_url( get_post_type_archive_link( koumuten_work_post_type() ) ); ?>"<?php echo $koumuten_current ? '' : ' aria-current="page"'; ?>>すべて</a>
		</li>
		<?php foreach ( $koumuten_terms as $koumuten_term ) : ?>
			<li>
				<a class="filter__link" href="<?php echo esc_url( get_term_link( $koumuten_term ) ); ?>"<?php echo ( $koumuten_current === (int) $koumuten_term->term_id ) ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $koumuten_term->name ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
