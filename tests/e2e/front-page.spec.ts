// トップページ（R-12）
import { test, expect } from '@playwright/test';

test.describe( 'トップページ @readonly', () => {
	test( '来訪者がトップページを開くと、施工事例の新着が写真つきで見える', async ( { page } ) => {
		await page.goto( '/' );

		const list = page.getByRole( 'list', { name: '施工事例' } );
		await expect( list ).toBeVisible();

		const items = list.getByRole( 'listitem' );
		const count = await items.count();
		expect( count ).toBeGreaterThan( 0 );
		expect( count ).toBeLessThanOrEqual( 6 );

		for ( let i = 0; i < count; i++ ) {
			await expect( items.nth( i ).locator( 'img' ) ).toBeVisible();
			await expect( items.nth( i ).getByRole( 'heading' ) ).toBeVisible();
		}
	} );

	test( '来訪者がトップページを開くと、電話番号が見える', async ( { page } ) => {
		await page.goto( '/' );
		await expect( page.getByRole( 'link', { name: /03-0000-0000/ } ).first() ).toBeVisible();
	} );

	test( '来訪者がトップページから、施工事例の一覧・会社の紹介・問い合わせへ移動できる', async ( { page } ) => {
		for ( const [ name, path ] of [
			[ '施工事例', '/works/' ],
			[ '会社の紹介', '/about/' ],
			[ 'お問い合わせ', '/contact/' ],
		] ) {
			await page.goto( '/' );
			await page.getByRole( 'main' ).getByRole( 'link', { name: new RegExp( name ) } ).first().click();
			await expect( page ).toHaveURL( new RegExp( path ) );
		}
	} );
} );
