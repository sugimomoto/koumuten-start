<?php
/**
 * フッター。会社名・住所・電話番号・メニューを出す。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="site-footer">
	<div class="site-footer__inner">
		<div class="site-footer__brand">
			<span class="brand__name"><?php bloginfo( 'name' ); ?></span>
			<?php if ( koumuten_address() ) : ?>
				<p class="site-footer__address"><?php echo nl2br( esc_html( koumuten_address() ) ); ?></p>
			<?php endif; ?>
			<?php get_template_part( 'parts/phone', null, array( 'variant' => 'footer', 'label' => 'お電話でのご相談' ) ); ?>
		</div>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => 'nav',
				'container_class' => 'nav',
				'container_aria_label' => 'フッターメニュー',
				'menu_class'     => 'nav__list',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		?>
	</div>
	<div class="copyright">© <?php bloginfo( 'name' ); ?></div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
