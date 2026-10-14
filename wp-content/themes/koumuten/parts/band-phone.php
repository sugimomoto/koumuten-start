<?php
/**
 * 各ページの下に出す、電話のバンド（R-09）。
 *
 * @param string $args['title'] 見出し。
 * @param string $args['lead']  ひとこと。
 * @param bool   $args['form']  フォームへのボタンも出すか。
 *
 * @package Koumuten
 */

defined( 'ABSPATH' ) || exit;

$koumuten_title = isset( $args['title'] ) ? $args['title'] : 'お電話でのご相談';
$koumuten_lead  = isset( $args['lead'] ) ? $args['lead'] : 'リフォーム・新築・修繕、どのご相談でもお気軽にどうぞ。';
$koumuten_form  = ! empty( $args['form'] );

if ( ! koumuten_phone() ) {
	return;
}
?>
<section class="band">
	<div class="container band__inner">
		<div>
			<h2 class="band__title"><?php echo esc_html( $koumuten_title ); ?></h2>
			<p class="band__lead"><?php echo esc_html( $koumuten_lead ); ?></p>
		</div>
		<div class="btn-row">
			<?php get_template_part( 'parts/phone', null, array( 'variant' => 'band', 'label' => 'タップで電話' ) ); ?>
			<?php if ( $koumuten_form ) : ?>
				<a class="btn btn--outline-light" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">フォームで問い合わせる</a>
			<?php endif; ?>
		</div>
	</div>
</section>
