// テストが終わったあとの後片付け（ローカルだけ）
import { isLocal, TEST_NAME, wpCli } from './helpers';

export default function globalTeardown() {
	if ( ! isLocal ) {
		return;
	}
	// テストで作ったデータ（タイトルに「E2Eテスト」を含むもの）だけを消す
	const ids = wpCli( 'post', 'list', '--post_type=any', '--post_status=any', `--s=${ TEST_NAME }`, '--field=ID' )
		.split( /\s+/ )
		.filter( Boolean );
	if ( ids.length ) {
		wpCli( 'post', 'delete', ...ids, '--force' );
	}
}
