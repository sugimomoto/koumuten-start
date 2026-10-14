// 施工事例を、事務担当が管理画面から追加して公開する（R-01 の最後の条件／N-02）
// データを書き込むので、ローカルのときだけ動かす。タイトルに「E2Eテスト」を入れ、後片付けで消える。
import { test, expect } from '@playwright/test';
import { isLocal, loginAsAdmin, openMetaBoxes, openSidebarPanel, TEST_NAME } from './helpers';

test.describe( '管理画面からの施工事例の追加', () => {
	test.skip( ! isLocal, '本番にはデータを書き込まない' );

	test( '事務担当が管理画面で施工事例を追加して公開すると、来訪者が見る一覧にその事例が出る', async ( { page } ) => {
		const title = `${ TEST_NAME }の施工事例`;

		await loginAsAdmin( page );
		await page.goto( '/wp-admin/post-new.php?post_type=koumuten_work' );

		// エディターが開くのを待って、「ようこそ」案内を閉じる
		await page.waitForFunction( () => ( window as any ).wp?.data?.select( 'core/editor' ) );
		await page.evaluate( () =>
			( window as any ).wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false )
		);

		// 施工事例のくわしい内容（場所・工期・築年数・費用）を入力できる
		await openMetaBoxes( page );
		await expect( page.getByLabel( '場所' ) ).toBeVisible();
		await expect( page.getByLabel( '工期' ) ).toBeVisible();
		await expect( page.getByLabel( '築年数' ) ).toBeVisible();
		await expect( page.getByLabel( '費用（万円）' ) ).toBeVisible();

		await page.evaluate(
			( t ) => ( window as any ).wp.data.dispatch( 'core/editor' ).editPost( { title: t } ),
			title
		);
		await page.getByLabel( '場所' ).fill( '東京都E2E区' );
		await page.getByLabel( '工期' ).fill( '約4か月' );
		await page.getByLabel( '築年数' ).fill( '築40年' );
		await page.getByLabel( '費用（万円）' ).fill( '777' );

		// 種類を選ぶ
		await openSidebarPanel( page, '種類' );
		await page.getByRole( 'checkbox', { name: 'リフォーム' } ).check();

		// 公開する
		const savedMetaBoxes = page.waitForResponse( ( res ) => res.url().includes( 'meta-box-loader=1' ) );
		await page.locator( '.editor-post-publish-panel__toggle' ).click();
		await page.locator( '.editor-post-publish-button' ).click();
		await savedMetaBoxes;

		// 来訪者が見る一覧に出る
		await page.goto( '/works/' );
		const list = page.getByRole( 'list', { name: '施工事例' } );
		await expect( list.getByRole( 'heading', { name: title } ) ).toBeVisible();

		// 入力した内容が、詳細のページに出る
		await list.getByRole( 'link' ).filter( { hasText: title } ).first().click();
		const main = page.getByRole( 'main' );
		await expect( main.getByText( '東京都E2E区', { exact: true } ) ).toBeVisible();
		await expect( main.getByText( '約4か月', { exact: true } ) ).toBeVisible();
		await expect( main.getByText( '築40年', { exact: true } ) ).toBeVisible();
		await expect( main.getByText( '約777万円', { exact: true } ) ).toBeVisible();
	} );
} );
