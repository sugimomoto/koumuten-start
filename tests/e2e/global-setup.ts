// テストを始める前の準備（ローカルだけ）
import { isLocal, wpCli } from './helpers';

export default function globalSetup() {
	if ( ! isLocal ) {
		return;
	}
	// お問い合わせの「連続送信の制限」の回数をリセットする（テストを何度も流すと制限にかかるため）
	wpCli( 'transient', 'delete', '--all' );
}
