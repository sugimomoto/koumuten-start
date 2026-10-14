<?php
/**
 * ヘッダー。会社名・メニュー・電話番号を出す。
 * スマホではメニューを details／summary で開閉する（JavaScript なしで動く）。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
	<div class="site-header__brand">
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<span class="brand__name"><?php bloginfo( 'name' ); ?></span>
			<span class="brand__tagline">リフォーム・新築・修繕</span>
		</a>
		<div class="header-contact">
			<?php get_template_part( 'parts/phone', null, array( 'variant' => 'header-inline', 'label' => 'お電話でのご相談' ) ); ?>
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">お問い合わせ</a>
		</div>
	</div>

	<details class="sp-menu">
		<summary class="sp-menu__button">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18"></path><path d="M3 12h18"></path><path d="M3 18h18"></path></svg>
			メニュー
		</summary>
		<?php
		wp_nav_menu(
			array(
				'theme_location' => 'primary',
				'container'      => 'nav',
				'container_class' => 'nav',
				'container_aria_label' => 'メインメニュー',
				'menu_class'     => 'nav__list',
				'depth'          => 1,
				'fallback_cb'    => false,
			)
		);
		?>
	</details>

	<?php get_template_part( 'parts/phone', null, array( 'variant' => 'header', 'label' => 'タップで電話' ) ); ?>
</header>
