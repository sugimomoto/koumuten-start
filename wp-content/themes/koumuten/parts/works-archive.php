<?php
/**
 * 施工事例の一覧（R-01・R-03）。
 * 一覧（/works/）と、種類で絞ったあと（/work-type/○○/）で、同じ見た目にするため共通にする。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

$koumuten_tax  = koumuten_work_taxonomy();
$koumuten_term = is_tax( $koumuten_tax ) ? get_queried_object() : null;
$koumuten_found = (int) $GLOBALS['wp_query']->found_posts;
?>
<main>
	<div class="page-head">
		<div class="container">
			<nav class="breadcrumb" aria-label="いまいる場所">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a>
				<?php if ( $koumuten_term ) : ?>
					<span class="breadcrumb__sep" aria-hidden="true">›</span>
					<a href="<?php echo esc_url( get_post_type_archive_link( koumuten_work_post_type() ) ); ?>">施工事例</a>
					<span class="breadcrumb__sep" aria-hidden="true">›</span>
					<span><?php echo esc_html( $koumuten_term->name ); ?></span>
				<?php else : ?>
					<span class="breadcrumb__sep" aria-hidden="true">›</span>
					<span>施工事例</span>
				<?php endif; ?>
			</nav>
			<h1 class="page-head__title">施工事例</h1>
			<p class="page-head__lead">実際に手がけた住まいです。種類で絞り込んでご覧いただけます。</p>
		</div>
	</div>

	<section class="section">
		<div class="container">
			<?php get_template_part( 'parts/work-filter' ); ?>
			<p class="works-count"><?php echo esc_html( number_format_i18n( $koumuten_found ) ); ?>件</p>
		</div>
	</section>

	<section class="section--entries">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<ul class="works-grid" aria-label="施工事例">
					<?php
					while ( have_posts() ) :
						the_post();
						get_template_part( 'parts/work-card', null, array( 'heading' => 'h2' ) );
					endwhile;
					?>
				</ul>
				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 1,
						'prev_text' => '前へ',
						'next_text' => '次へ',
					)
				);
				?>
			<?php else : ?>
				<p>この種類の施工事例は、まだありません。</p>
			<?php endif; ?>
		</div>
	</section>

	<?php
	get_template_part(
		'parts/band-phone',
		null,
		array(
			'title' => '似た工事のご相談も承ります',
			'lead'  => '「うちの場合はどうだろう」と思われたら、お気軽にどうぞ。',
		)
	);
	?>
</main>
