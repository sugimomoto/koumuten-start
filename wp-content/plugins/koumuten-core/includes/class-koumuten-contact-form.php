<?php
/**
 * お問い合わせフォーム（R-10）と、迷惑メール対策（N-03）。
 *
 * フォームの HTML もここで出す（ショートコード）。テーマを替えてもフォームが動くようにするため。
 * 見た目（色・間隔）はテーマの style.css が受け持つ。
 *
 * 迷惑メール対策は、外部のサービスを増やさずに3段で守る。
 *   1. ハニーポット … 人には見えない欄。自動送信のプログラムだけが書き込む
 *   2. 時間の罠     … フォームを開いてから 3 秒未満の送信は受け付けない
 *   3. 連続送信の制限 … 同じ送信元からの 60 秒以内の2回目以降は受け付けない
 *
 * @package Koumuten_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * お問い合わせフォーム。
 */
class Koumuten_Contact_Form {

	const SHORTCODE    = 'koumuten_contact_form';
	const NONCE_ACTION = 'koumuten_contact_send';
	const NONCE_NAME   = 'koumuten_contact_nonce';
	const HONEYPOT     = 'koumuten_website';
	const MIN_SECONDS  = 3;
	const THROTTLE_SEC = 60;

	/**
	 * 送信を受けた結果。ショートコードが描くときに読む。
	 *
	 * @var array|null
	 */
	private static $result = null;

	/**
	 * ご相談の種類（これ以外の値は受け付けない）。
	 *
	 * @return array
	 */
	public static function types() {
		return array(
			'reform'   => 'リフォーム',
			'newbuild' => '新築',
			'repair'   => '修繕',
			'other'    => 'その他',
		);
	}

	/**
	 * フックを登録する。
	 */
	public static function init() {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_handle' ) );
	}

	/**
	 * 送信されていれば受け付ける。画面に何か出す前に動かす。
	 */
	public static function maybe_handle() {
		if ( 'POST' !== strtoupper( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
			return;
		}
		if ( ! isset( $_POST['koumuten_contact_submit'] ) ) {
			return;
		}

		// 1. nonce の検証
		$nonce = isset( $_POST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			self::$result = array(
				'values'  => array(),
				'errors'  => array(),
				'message' => '時間が経ちすぎたため、送信できませんでした。お手数ですが、もう一度お試しください。',
			);
			return;
		}

		// 2. 迷惑メール対策（N-03）。はじかれた理由は画面に出さない。
		if ( self::looks_automated() ) {
			self::$result = array(
				'values'  => array(),
				'errors'  => array(),
				'message' => '送信できませんでした。お手数ですが、もう一度お試しいただくか、お電話ください。',
			);
			return;
		}

		// 3. サニタイズ
		$values = self::sanitized_input();

		// 4. 入力チェック
		$errors = self::validate( $values );
		if ( $errors ) {
			self::$result = array(
				'values' => $values,
				'errors' => $errors,
			);
			return;
		}

		// 5. 保存
		$post_id = Koumuten_Inquiry::save( $values );
		if ( ! $post_id ) {
			self::$result = array(
				'values'  => $values,
				'errors'  => array(),
				'message' => '送信できませんでした。お手数ですが、お電話ください。',
			);
			return;
		}

		// 6. メールで知らせる
		self::notify( $values );

		// 7. 連続送信の制限をかけてから、受け付けの画面へ
		set_transient( self::throttle_key(), 1, self::THROTTLE_SEC );

		wp_safe_redirect( add_query_arg( 'sent', '1', get_permalink() ) );
		exit;
	}

	/**
	 * 自動送信とみなすか（N-03）。
	 *
	 * @return bool
	 */
	private static function looks_automated() {
		// ハニーポット：人には見えない欄に何か入っている
		$pot = isset( $_POST[ self::HONEYPOT ] ) ? trim( (string) wp_unslash( $_POST[ self::HONEYPOT ] ) ) : '';
		if ( '' !== $pot ) {
			return true;
		}

		// 時間の罠：フォームを開いてから短すぎる
		$opened = isset( $_POST['koumuten_opened'] ) ? absint( $_POST['koumuten_opened'] ) : 0;
		$sign   = isset( $_POST['koumuten_opened_sign'] ) ? sanitize_text_field( wp_unslash( $_POST['koumuten_opened_sign'] ) ) : '';
		if ( ! $opened || ! hash_equals( wp_hash( (string) $opened ), $sign ) ) {
			return true;
		}
		if ( ( time() - $opened ) < self::MIN_SECONDS ) {
			return true;
		}

		// 連続送信の制限
		if ( get_transient( self::throttle_key() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * 送信元ごとの、連続送信の見張りに使う名前。
	 *
	 * @return string
	 */
	private static function throttle_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return 'koumuten_contact_' . md5( $ip );
	}

	/**
	 * 入力を、安全な形に整える。
	 *
	 * @return array
	 */
	private static function sanitized_input() {
		$types = self::types();
		$type  = isset( $_POST['koumuten_type'] ) ? sanitize_text_field( wp_unslash( $_POST['koumuten_type'] ) ) : '';

		return array(
			'name'  => isset( $_POST['koumuten_name'] ) ? sanitize_text_field( wp_unslash( $_POST['koumuten_name'] ) ) : '',
			'email' => isset( $_POST['koumuten_email'] ) ? sanitize_email( wp_unslash( $_POST['koumuten_email'] ) ) : '',
			'tel'   => isset( $_POST['koumuten_tel'] ) ? sanitize_text_field( wp_unslash( $_POST['koumuten_tel'] ) ) : '',
			// 選択肢にある値だけを受け取る
			'type'  => isset( $types[ $type ] ) ? $type : '',
			'body'  => isset( $_POST['koumuten_body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['koumuten_body'] ) ) : '',
		);
	}

	/**
	 * 足りない項目を調べる。
	 *
	 * @param array $values サニタイズ済みの入力。
	 * @return array 項目名 => 直し方。
	 */
	private static function validate( array $values ) {
		$errors = array();

		if ( '' === trim( $values['name'] ) ) {
			$errors['name'] = 'お名前を入れてください。';
		}

		if ( '' === trim( $values['email'] ) ) {
			$errors['email'] = 'メールアドレスを入れてください。';
		} elseif ( ! is_email( $values['email'] ) ) {
			$errors['email'] = 'メールアドレスの形を確かめてください。';
		}

		if ( '' === $values['type'] ) {
			$errors['type'] = 'ご相談の種類を選んでください。';
		}

		if ( '' === trim( $values['body'] ) ) {
			$errors['body'] = 'お問い合わせ内容を入れてください。';
		}

		return $errors;
	}

	/**
	 * 山田工務店にメールで知らせる。
	 *
	 * @param array $values サニタイズ済みの入力。
	 */
	private static function notify( array $values ) {
		$types = self::types();
		$to    = get_option( 'admin_email' );

		$message = implode(
			"\n",
			array(
				'ホームページのフォームから、お問い合わせがありました。',
				'',
				'お名前　　　　　: ' . $values['name'],
				'メールアドレス　: ' . $values['email'],
				'電話番号　　　　: ' . ( $values['tel'] ? $values['tel'] : '（未記入）' ),
				'ご相談の種類　　: ' . ( isset( $types[ $values['type'] ] ) ? $types[ $values['type'] ] : '' ),
				'',
				'お問い合わせ内容:',
				$values['body'],
				'',
				'---',
				'管理画面の「お問い合わせ」からも読めます。',
			)
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( is_email( $values['email'] ) ) {
			$headers[] = 'Reply-To: ' . $values['email'];
		}

		wp_mail(
			$to,
			'[' . get_bloginfo( 'name' ) . '] ホームページからお問い合わせがありました',
			$message,
			$headers
		);
	}

	/**
	 * フォーム（または受け付けの表示）を描く。
	 *
	 * @return string
	 */
	public static function render() {
		// 受け付けが済んだあと
		if ( isset( $_GET['sent'] ) && '1' === $_GET['sent'] ) {
			return self::render_done();
		}

		$result = self::$result;
		$values = isset( $result['values'] ) ? $result['values'] : array();
		$errors = isset( $result['errors'] ) ? $result['errors'] : array();
		$message = isset( $result['message'] ) ? $result['message'] : '';

		$value = function ( $key ) use ( $values ) {
			return isset( $values[ $key ] ) ? $values[ $key ] : '';
		};
		$error = function ( $key ) use ( $errors ) {
			return isset( $errors[ $key ] ) ? $errors[ $key ] : '';
		};

		$opened = time();

		ob_start();
		?>
		<form class="form" method="post" action="<?php echo esc_url( get_permalink() ); ?>">
			<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>
			<input type="hidden" name="koumuten_opened" value="<?php echo esc_attr( $opened ); ?>">
			<input type="hidden" name="koumuten_opened_sign" value="<?php echo esc_attr( wp_hash( (string) $opened ) ); ?>">

			<p class="form__note"><span class="required">※</span> の付いた項目は、必ず入れてください。</p>

			<?php if ( $message ) : ?>
				<div class="alert alert--error" role="alert"><?php echo esc_html( $message ); ?></div>
			<?php elseif ( $errors ) : ?>
				<div class="alert alert--error" role="alert">入力に<?php echo esc_html( number_format_i18n( count( $errors ) ) ); ?>か所、直すところがあります。</div>
			<?php endif; ?>

			<p class="field">
				<label class="field__label" for="koumuten_name">お名前 <span class="required">※必須</span></label>
				<input class="field__input" type="text" id="koumuten_name" name="koumuten_name" autocomplete="name" value="<?php echo esc_attr( $value( 'name' ) ); ?>">
				<?php if ( $error( 'name' ) ) : ?>
					<span class="field__error"><?php echo esc_html( $error( 'name' ) ); ?></span>
				<?php endif; ?>
			</p>

			<p class="field">
				<label class="field__label" for="koumuten_email">メールアドレス <span class="required">※必須</span></label>
				<input class="field__input" type="email" id="koumuten_email" name="koumuten_email" autocomplete="email" value="<?php echo esc_attr( $value( 'email' ) ); ?>">
				<?php if ( $error( 'email' ) ) : ?>
					<span class="field__error"><?php echo esc_html( $error( 'email' ) ); ?></span>
				<?php endif; ?>
			</p>

			<p class="field">
				<label class="field__label" for="koumuten_tel">電話番号 <span class="optional">（任意）</span></label>
				<input class="field__input" type="tel" id="koumuten_tel" name="koumuten_tel" autocomplete="tel" value="<?php echo esc_attr( $value( 'tel' ) ); ?>">
			</p>

			<p class="field">
				<label class="field__label" for="koumuten_type">ご相談の種類 <span class="required">※必須</span></label>
				<select class="field__select" id="koumuten_type" name="koumuten_type">
					<option value="">選んでください</option>
					<?php foreach ( self::types() as $koumuten_key => $koumuten_label ) : ?>
						<option value="<?php echo esc_attr( $koumuten_key ); ?>" <?php selected( $value( 'type' ), $koumuten_key ); ?>><?php echo esc_html( $koumuten_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php if ( $error( 'type' ) ) : ?>
					<span class="field__error"><?php echo esc_html( $error( 'type' ) ); ?></span>
				<?php endif; ?>
			</p>

			<p class="field">
				<label class="field__label" for="koumuten_body">お問い合わせ内容 <span class="required">※必須</span></label>
				<textarea class="field__textarea" id="koumuten_body" name="koumuten_body" rows="7"><?php echo esc_textarea( $value( 'body' ) ); ?></textarea>
				<?php if ( $error( 'body' ) ) : ?>
					<span class="field__error"><?php echo esc_html( $error( 'body' ) ); ?></span>
				<?php endif; ?>
			</p>

			<?php // ハニーポット：人には見せない。自動送信のプログラムだけが書き込む（N-03）。 ?>
			<p class="honeypot" aria-hidden="true">
				<label for="<?php echo esc_attr( self::HONEYPOT ); ?>">ホームページ（入力しないでください）</label>
				<input type="text" id="<?php echo esc_attr( self::HONEYPOT ); ?>" name="<?php echo esc_attr( self::HONEYPOT ); ?>" value="" tabindex="-1" autocomplete="off">
			</p>

			<button class="btn btn--kaki" type="submit" name="koumuten_contact_submit" value="1">送信する</button>
		</form>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * 受け付けの表示。
	 *
	 * @return string
	 */
	private static function render_done() {
		ob_start();
		?>
		<div class="alert alert--done" role="alert">
			<h2 class="alert--done__title">お問い合わせを受け付けました</h2>
			<p>ありがとうございます。内容を確かめて、折り返しご連絡いたします。</p>
			<?php if ( function_exists( 'koumuten_phone' ) && koumuten_phone() ) : ?>
				<p>お急ぎのご用件は、<a href="<?php echo esc_url( koumuten_phone_href() ); ?>"><strong><?php echo esc_html( koumuten_phone() ); ?></strong></a> までお電話ください。</p>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
