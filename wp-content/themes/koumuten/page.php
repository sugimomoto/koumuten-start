<?php
/**
 * 固定ページ（会社の紹介 R-06／お問い合わせ R-10）。
 * 本文は管理画面から書き替えられるように、そのまま出す。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
<main>
	<div class="page-head">
		<div class="container">
			<nav class="breadcrumb" aria-label="いまいる場所">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a>
				<span class="breadcrumb__sep" aria-hidden="true">›</span>
				<span><?php the_title(); ?></span>
			</nav>
			<h1 class="page-head__title"><?php the_title(); ?></h1>
		</div>
	</div>

	<div class="page-body">
		<div class="container">
			<div class="entry-content"><?php the_content(); ?></div>
		</div>
	</div>

	<?php
	get_template_part(
		'parts/band-phone',
		null,
		array(
			'form' => ! is_page( 'contact' ),
		)
	);
	?>
</main>
	<?php
endwhile;

get_footer();
