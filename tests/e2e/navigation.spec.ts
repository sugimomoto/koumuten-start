// ヘッダーのメニュー（パソコンの幅・スマホの幅の両方）
// パソコンの幅でメニューが出ていなかった不具合を見つけられなかったので、足したテスト。
import { test, expect } from '@playwright/test';

const LINKS = [ 'ホーム', '施工事例', '会社の紹介', 'お問い合わせ' ] as const;

test.describe( 'パソコンの幅のメニュー @readonly', () => {
	test.use( { viewport: { width: 1280, height: 900 } } );

	test( '来訪者がパソコンの幅でページを開くと、メニューがそのまま並んでいて、各ページへ移動できる', async ( { page } ) => {
		await page.goto( '/' );

		const menu = page.getByRole( 'navigation', { name: 'メインメニュー' } );
		for ( const name of LINKS ) {
			await expect(
				menu.getByRole( 'link', { name, exact: true } ),
				`メニューの「${ name }」が見えない`
			).toBeVisible();
		}

		await menu.getByRole( 'link', { name: '施工事例', exact: true } ).click();
		await expect( page ).toHaveURL( /\/works\/?$/ );
		await expect( page.getByRole( 'heading', { level: 1, name: '施工事例' } ) ).toBeVisible();
	} );
} );

test.describe( 'スマホの幅のメニュー @readonly', () => {
	test.use( { viewport: { width: 375, height: 812 } } );

	test( '来訪者がスマホの幅でページを開くと、メニューは閉じていて、開くと各ページへ移動できる', async ( { page } ) => {
		await page.goto( '/' );

		const menu = page.getByRole( 'navigation', { name: 'メインメニュー' } );
		await expect( menu.getByRole( 'link', { name: '施工事例', exact: true } ) ).toBeHidden();

		await page.getByText( 'メニュー', { exact: true } ).click();
		for ( const name of LINKS ) {
			await expect( menu.getByRole( 'link', { name, exact: true } ) ).toBeVisible();
		}
	} );
} );
