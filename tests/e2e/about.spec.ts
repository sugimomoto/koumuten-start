// 会社の紹介（R-06）
import { test, expect } from '@playwright/test';

test.describe( '会社の紹介 @readonly', () => {
	test( '来訪者が会社の紹介のページを開くと、会社の紹介が読める', async ( { page } ) => {
		await page.goto( '/about/' );

		await expect( page.getByRole( 'heading', { level: 1, name: '会社の紹介' } ) ).toBeVisible();

		const main = page.getByRole( 'main' );
		// 代表のあいさつ
		await expect( main.getByText( '山田工務店　代表　山田 一郎' ) ).toBeVisible();
		// 会社概要
		await expect( main.getByRole( 'heading', { name: '会社概要' } ) ).toBeVisible();
		for ( const label of [ '会社名', '代表者', '所在地', '電話番号', '事業内容' ] ) {
			await expect( main.getByText( label, { exact: true } ) ).toBeVisible();
		}
	} );
} );
