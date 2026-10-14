<?php
/**
 * トップページ（R-12）。
 * いちばん見せたい施工事例の新着と、電話番号を置き、各ページへ移動できるようにする。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

$koumuten_latest = new WP_Query(
	array(
		'post_type'           => koumuten_work_post_type(),
		'posts_per_page'      => 6,
		'post_status'         => 'publish',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
?>
<main>

	<section class="hero">
		<div class="container hero__inner">
			<div class="hero__text">
				<h1 class="hero__title">リフォーム・新築・修繕。<br>住まいのことなら、山田工務店へ。</h1>
				<p class="hero__lead">これまでに手がけた住まいを、写真と一緒にご紹介しています。かかった費用の目安も載せていますので、どうぞご覧ください。</p>
				<div class="btn-row">
					<a class="btn btn--primary" href="<?php echo esc_url( get_post_type_archive_link( koumuten_work_post_type() ) ); ?>">施工事例を見る</a>
					<a class="btn btn--outline" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">お問い合わせ</a>
				</div>
			</div>
			<div class="hero__photo">
				<div class="photo">
					<span class="photo__label">写真</span>
				</div>
			</div>
		</div>
	</section>

	<?php if ( $koumuten_latest->have_posts() ) : ?>
		<section class="section">
			<div class="container">
				<div class="section__head">
					<div>
						<h2 class="section__title">施工事例</h2>
						<p class="section__lead">実際に手がけた住まいです。</p>
					</div>
					<a href="<?php echo esc_url( get_post_type_archive_link( koumuten_work_post_type() ) ); ?>" style="font-weight:700;padding:12px 0">施工事例をもっと見る →</a>
				</div>

				<ul class="works-grid" aria-label="施工事例">
					<?php
					while ( $koumuten_latest->have_posts() ) :
						$koumuten_latest->the_post();
						get_template_part( 'parts/work-card', null, array( 'heading' => 'h3' ) );
					endwhile;
					wp_reset_postdata();
					?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<section class="section--entries">
		<div class="container">
			<div class="entry-grid">
				<a class="entry-card" href="<?php echo esc_url( get_post_type_archive_link( koumuten_work_post_type() ) ); ?>">
					<h2 class="entry-card__title">施工事例</h2>
					<p class="entry-card__lead">リフォーム・新築・修繕。種類で絞り込んでご覧いただけます。</p>
					<span class="entry-card__more">一覧を見る →</span>
				</a>
				<a class="entry-card" href="<?php echo esc_url( home_url( '/about/' ) ); ?>">
					<h2 class="entry-card__title">会社の紹介</h2>
					<p class="entry-card__lead">山田工務店について、代表のあいさつと会社概要。</p>
					<span class="entry-card__more">会社の紹介を読む →</span>
				</a>
				<a class="entry-card" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">
					<h2 class="entry-card__title">お問い合わせ</h2>
					<p class="entry-card__lead">お電話か、フォームからご相談ください。</p>
					<span class="entry-card__more">お問い合わせへ →</span>
				</a>
			</div>
		</div>
	</section>

	<?php get_template_part( 'parts/band-phone' ); ?>

</main>
<?php
get_footer();
