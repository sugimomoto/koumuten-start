<?php
/**
 * 施工事例の詳細（R-01・R-02）。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$koumuten_id   = get_the_ID();
	$koumuten_type = koumuten_work_type_name( $koumuten_id );
	$koumuten_cost = koumuten_work_cost_text( $koumuten_id );
	$koumuten_rows = array(
		'場所'   => koumuten_field( 'area', $koumuten_id ),
		'工期'   => koumuten_field( 'duration', $koumuten_id ),
		'築年数' => koumuten_field( 'building_age', $koumuten_id ),
	);
	?>
<main>
	<div class="page-head">
		<div class="container">
			<nav class="breadcrumb" aria-label="いまいる場所">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">ホーム</a>
				<span class="breadcrumb__sep" aria-hidden="true">›</span>
				<a href="<?php echo esc_url( get_post_type_archive_link( koumuten_work_post_type() ) ); ?>">施工事例</a>
			</nav>
			<?php if ( $koumuten_type ) : ?>
				<p style="margin:0 0 12px"><span class="badge"><?php echo esc_html( $koumuten_type ); ?></span></p>
			<?php endif; ?>
			<h1 class="page-head__title"><?php the_title(); ?></h1>
		</div>
	</div>

	<?php if ( has_post_thumbnail() ) : ?>
		<section class="section">
			<div class="container">
				<div class="work-photos">
					<div class="photo photo--wide">
						<?php the_post_thumbnail( 'full', array( 'alt' => '' ) ); ?>
					</div>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<section class="section--entries">
		<div class="container work-detail">

			<div class="work-detail__main">
				<h2 class="section__title">この工事について</h2>
				<div class="work-body"><?php the_content(); ?></div>

				<?php // お客様の声（R-04）は、次の作業でここに入る。 ?>

				<div class="btn-row" style="margin-top:24px">
					<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">似た工事を相談する</a>
					<a class="btn btn--outline" href="<?php echo esc_url( get_post_type_archive_link( koumuten_work_post_type() ) ); ?>">← 施工事例の一覧へ</a>
				</div>
			</div>

			<aside class="work-detail__aside">
				<div class="aside">
					<h2 class="aside__title">工事のあらまし</h2>
					<dl class="work-meta">
						<?php foreach ( $koumuten_rows as $koumuten_label => $koumuten_value ) : ?>
							<?php if ( '' === $koumuten_value ) : ?>
								<?php continue; ?>
							<?php endif; ?>
							<div class="work-meta__row">
								<dt><?php echo esc_html( $koumuten_label ); ?></dt>
								<dd><?php echo esc_html( $koumuten_value ); ?></dd>
							</div>
						<?php endforeach; ?>

						<?php if ( $koumuten_type ) : ?>
							<div class="work-meta__row">
								<dt>種類</dt>
								<dd><?php echo esc_html( $koumuten_type ); ?></dd>
							</div>
						<?php endif; ?>

						<?php // 費用：空欄なら、この行そのものを出さない（R-02）。 ?>
						<?php if ( '' !== $koumuten_cost ) : ?>
							<div class="work-meta__row work-meta__row--cost">
								<dt>費用</dt>
								<dd><span class="work-cost"><?php echo esc_html( $koumuten_cost ); ?></span></dd>
							</div>
						<?php endif; ?>
					</dl>

					<?php if ( '' !== $koumuten_cost ) : ?>
						<p class="aside__note">工事の内容によって金額は変わります。おおよその目安としてご覧ください。</p>
					<?php endif; ?>
				</div>
			</aside>

		</div>
	</section>

	<?php
	get_template_part(
		'parts/band-phone',
		null,
		array(
			'title' => 'お電話でのご相談',
			'lead'  => '「うちは築何十年だけど大丈夫だろうか」もお気軽にどうぞ。',
		)
	);
	?>
</main>
	<?php
endwhile;

get_footer();
