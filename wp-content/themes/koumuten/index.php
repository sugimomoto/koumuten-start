<?php
/**
 * 行き先の決まらないときの受け皿。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main>
	<div class="page-head">
		<div class="container">
			<h1 class="page-head__title"><?php echo esc_html( wp_get_document_title() ); ?></h1>
		</div>
	</div>
	<div class="page-body">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article>
						<h2 class="section__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<div class="entry-content"><?php the_excerpt(); ?></div>
					</article>
				<?php endwhile; ?>
			<?php else : ?>
				<p>ご覧のページは見つかりませんでした。</p>
			<?php endif; ?>
		</div>
	</div>
</main>
<?php
get_footer();
