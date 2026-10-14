// スマホで見やすい（N-01）
// 「スマホで字がちっちゃくて見づらい」という今のサイトの困りごとへの答え。
import { test, expect } from '@playwright/test';

const PAGES = [
	[ 'トップページ', '/' ],
	[ '会社の紹介', '/about/' ],
	[ '施工事例の一覧', '/works/' ],
	[ '施工事例の詳細', '/works/sample-reform-whole/' ],
	[ 'お問い合わせ', '/contact/' ],
] as const;

test.describe( 'スマホの幅（375px）@readonly', () => {
	test.use( { viewport: { width: 375, height: 812 } } );

	test( '来訪者がスマホの幅で各ページを開くと、横にはみ出さずに読める大きさの文字で表示される', async ( { page } ) => {
		for ( const [ name, url ] of PAGES ) {
			await page.goto( url );

			// 横にはみ出していない
			const overflow = await page.evaluate( () => ( {
				scroll: document.documentElement.scrollWidth,
				client: document.documentElement.clientWidth,
			} ) );
			expect( overflow.scroll, `${ name } が横にはみ出している` ).toBeLessThanOrEqual( overflow.client + 1 );

			// 本文が 16px 以上
			const fontSize = await page.evaluate( () =>
				parseFloat( getComputedStyle( document.body ).fontSize )
			);
			expect( fontSize, `${ name } の本文が小さい` ).toBeGreaterThanOrEqual( 16 );
		}
	} );

	test( '来訪者がスマホの幅でメニューを開くと、各ページへ移動できる', async ( { page } ) => {
		await page.goto( '/' );

		// 閉じているあいだは、メニューの行き先は見えない
		const menu = page.getByRole( 'banner' ).getByRole( 'link', { name: '会社の紹介', exact: true } );
		await expect( menu ).toBeHidden();

		// 「メニュー」を押すと開く
		await page.getByText( 'メニュー', { exact: true } ).click();
		await expect( menu ).toBeVisible();

		await menu.click();
		await expect( page ).toHaveURL( /\/about\// );
		await expect( page.getByRole( 'heading', { level: 1, name: '会社の紹介' } ) ).toBeVisible();
	} );

	test( '来訪者がスマホで開くと、電話番号がすぐ目につく位置にある', async ( { page } ) => {
		await page.goto( '/' );
		const tel = page.getByRole( 'banner' ).getByRole( 'link', { name: /03-0000-0000/ } ).first();
		await expect( tel ).toBeVisible();

		// 画面の最初のひと目で見える位置にある
		const box = await tel.boundingBox();
		expect( box ).not.toBeNull();
		expect( box!.y ).toBeLessThan( 400 );
	} );
} );
