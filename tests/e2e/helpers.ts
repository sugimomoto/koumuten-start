// E2E テストの共通処理
import { execFileSync } from 'node:child_process';
import type { Page } from '@playwright/test';

const baseURL = process.env.E2E_BASE_URL || 'http://localhost:8890';

/** ローカル（wp-env）に対して動かしているか。本番にデータを書き込むテストは、ローカルのときだけ動かす。 */
export const isLocal = /^http:\/\/(localhost|127\.0\.0\.1)(:\d+)?/.test( baseURL );

/** ローカルの WordPress で WP-CLI を実行する（wp-env 経由） */
export function wpCli( ...args: string[] ): string {
	return execFileSync( 'npx', [ 'wp-env', 'run', 'cli', 'wp', ...args ], {
		encoding: 'utf8',
		stdio: [ 'ignore', 'pipe', 'ignore' ],
	} ).trim();
}

/** フォーム送信のテストで使う名前（後片付けで、この名前のデータだけを消す） */
export const TEST_NAME = 'E2Eテスト';

/** ローカルの管理画面にログインする（wp-env が最初から用意している、ローカル専用の管理者） */
export async function loginAsAdmin( page: Page ) {
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( 'admin' );
	await page.locator( '#user_pass' ).fill( 'password' );
	await page.locator( '#wp-submit' ).click();
	await page.waitForURL( /\/wp-admin\// );
}

/** 投稿の ID（投稿タイプとスラッグから引く） */
export function postId( postType: string, slug: string ): string {
	return wpCli( 'post', 'list', `--post_type=${ postType }`, `--name=${ slug }`, '--field=ID' );
}

/**
 * 投稿の編集画面を開く（ログインして、ブロックエディターの「ようこそ」案内を閉じた状態にする）
 */
export async function openEditor( page: Page, id: string ) {
	await loginAsAdmin( page );
	await page.goto( `/wp-admin/post.php?post=${ id }&action=edit` );
	await page.waitForFunction( () => ( window as any ).wp?.data?.select( 'core/editor' )?.getCurrentPostId() );
	await page.evaluate( () => ( window as any ).wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false ) );
}

/** 編集中の投稿を「更新」する（本文の下の入力欄＝カスタムフィールドの保存が終わるまで待つ） */
export async function updatePost( page: Page ) {
	const savedMetaBoxes = page.waitForResponse( ( res ) => res.url().includes( 'meta-box-loader=1' ) );
	await page.locator( '.editor-post-publish-button' ).click();
	await savedMetaBoxes;
}

/** カスタムフィールドを消す（テストの後片付け用。入っていなければ何もしない） */
export function deletePostMeta( postId: string, key: string ) {
	if ( wpCli( 'post', 'meta', 'list', postId, `--keys=${ key }`, '--format=count' ) !== '0' ) {
		wpCli( 'post', 'meta', 'delete', postId, key );
	}
}

/**
 * ブロックエディターの下にある「メタボックス」の引き出しを開く。
 * 施工事例のくわしい内容（場所・工期・築年数・費用）はこの中にある。
 */
export async function openMetaBoxes( page: Page ) {
	const drawer = page.locator( '.edit-post-meta-boxes-main button[aria-expanded]' ).first();
	if ( ( await drawer.getAttribute( 'aria-expanded' ) ) === 'false' ) {
		// 帯の真ん中には WordPress の「ドラッグしてサイズ変更」のつまみが重なっているので、
		// 利用者と同じように「メタボックス」の文字のところを押す。
		await drawer.click( { position: { x: 60, y: 12 } } );
	}
	await page.locator( '#koumuten-work-fields' ).waitFor( { state: 'visible' } );
}

/** エディター右側の、たたまれているパネルを開く（例：「種類」） */
export async function openSidebarPanel( page: Page, name: string ) {
	const button = page.getByRole( 'button', { name, exact: true } );
	if ( ( await button.getAttribute( 'aria-expanded' ) ) === 'false' ) {
		await button.click();
	}
}
