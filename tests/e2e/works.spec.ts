// 施工事例（R-01 一覧と詳細／R-02 費用の概算／R-03 種類での絞り込み）
// サンプルデータを読むだけなので、本番でも動かす（@readonly）。
import { test, expect } from '@playwright/test';

const WHOLE = '住まい全体のリフォーム'; // リフォーム・費用 980
const SETAGAYA = '世田谷の新築'; // 新築・費用 2800
const ROOF = '台風のあとの屋根の修理'; // 修繕・費用 45
const GUTTER = '雨どいの取り付け直し'; // 修繕・費用は空欄（R-02）

/** 施工事例の一覧（並んでいるところ） */
const workList = ( page ) => page.getByRole( 'list', { name: '施工事例' } );

test.describe( '施工事例の一覧 @readonly', () => {
	test( '来訪者が施工事例の一覧を開くと、施工事例が写真つきで並んで見える', async ( { page } ) => {
		await page.goto( '/works/' );

		const list = workList( page );
		await expect( list ).toBeVisible();

		const items = list.getByRole( 'listitem' );
		expect( await items.count() ).toBeGreaterThan( 0 );

		// 並んでいる1件ずつに、写真とタイトルがある
		const count = await items.count();
		for ( let i = 0; i < count; i++ ) {
			const item = items.nth( i );
			await expect( item.locator( 'img' ) ).toBeVisible();
			await expect( item.getByRole( 'heading' ) ).toBeVisible();
		}

		await expect( list.getByRole( 'heading', { name: WHOLE } ) ).toBeVisible();
	} );

	test( '来訪者が一覧から施工事例を1件選ぶと、その事例のページが開く', async ( { page } ) => {
		await page.goto( '/works/' );
		await workList( page ).getByRole( 'link' ).filter( { hasText: WHOLE } ).first().click();
		await expect( page.getByRole( 'heading', { level: 1, name: WHOLE } ) ).toBeVisible();
	} );
} );

test.describe( '施工事例の詳細 @readonly', () => {
	test( '来訪者が施工事例を1件開くと、その事例の写真が見える', async ( { page } ) => {
		await page.goto( '/works/sample-reform-whole/' );
		await expect( page.getByRole( 'main' ).locator( 'img' ).first() ).toBeVisible();
	} );

	test( '来訪者が施工事例を1件開くと、場所・工期・築年数が見える', async ( { page } ) => {
		await page.goto( '/works/sample-reform-whole/' );

		const meta = page.getByRole( 'main' );
		for ( const [ label, value ] of [
			[ '場所', '東京都世田谷区' ],
			[ '工期', '約3か月' ],
			[ '築年数', '築32年' ],
		] ) {
			await expect( meta.getByText( label, { exact: true } ) ).toBeVisible();
			await expect( meta.getByText( value, { exact: true } ) ).toBeVisible();
		}
	} );
} );

test.describe( '施工事例の費用（R-02）@readonly', () => {
	test( '来訪者が、費用を入力した施工事例を開くと、「約○○万円」の形で概算の費用が見える', async ( { page } ) => {
		await page.goto( '/works/sample-reform-whole/' );
		const main = page.getByRole( 'main' );
		await expect( main.getByText( '費用', { exact: true } ) ).toBeVisible();
		await expect( main.getByText( '約980万円', { exact: true } ) ).toBeVisible();
	} );

	test( '来訪者が、費用が空欄の施工事例を開くと、費用はどこにも表示されない', async ( { page } ) => {
		await page.goto( '/works/sample-repair-gutter/' );

		// この事例のページであることを確かめてから
		await expect( page.getByRole( 'heading', { level: 1, name: GUTTER } ) ).toBeVisible();

		// 「費用」の項目も、金額も出ていない
		const main = page.getByRole( 'main' );
		await expect( main.getByText( '費用', { exact: true } ) ).toHaveCount( 0 );
		await expect( main.getByText( /万円/ ) ).toHaveCount( 0 );
	} );
} );

test.describe( '施工事例の種類での絞り込み（R-03）@readonly', () => {
	test( '来訪者が施工事例の一覧でリフォームを選ぶと、リフォームの事例だけが並び、ほかの種類の事例は出ない', async ( { page } ) => {
		await page.goto( '/works/' );
		await page.getByRole( 'link', { name: 'リフォーム', exact: true } ).click();

		const list = workList( page );
		await expect( list.getByRole( 'heading', { name: WHOLE } ) ).toBeVisible();
		await expect( list.getByRole( 'heading', { name: SETAGAYA } ) ).toHaveCount( 0 );
		await expect( list.getByRole( 'heading', { name: ROOF } ) ).toHaveCount( 0 );
	} );

	test( '来訪者が新築を選ぶと、新築の事例だけが並ぶ', async ( { page } ) => {
		await page.goto( '/works/' );
		await page.getByRole( 'link', { name: '新築', exact: true } ).click();

		const list = workList( page );
		await expect( list.getByRole( 'heading', { name: SETAGAYA } ) ).toBeVisible();
		await expect( list.getByRole( 'heading', { name: WHOLE } ) ).toHaveCount( 0 );
		await expect( list.getByRole( 'heading', { name: ROOF } ) ).toHaveCount( 0 );
	} );

	test( '来訪者が修繕を選ぶと、修繕の事例だけが並ぶ', async ( { page } ) => {
		await page.goto( '/works/' );
		await page.getByRole( 'link', { name: '修繕', exact: true } ).click();

		const list = workList( page );
		await expect( list.getByRole( 'heading', { name: ROOF } ) ).toBeVisible();
		await expect( list.getByRole( 'heading', { name: WHOLE } ) ).toHaveCount( 0 );
		await expect( list.getByRole( 'heading', { name: SETAGAYA } ) ).toHaveCount( 0 );
	} );

	test( '来訪者が絞り込みを解除すると、すべての種類の事例が並ぶ', async ( { page } ) => {
		await page.goto( '/works/' );
		await page.getByRole( 'link', { name: 'リフォーム', exact: true } ).click();
		await page.getByRole( 'link', { name: 'すべて', exact: true } ).click();

		const list = workList( page );
		await expect( list.getByRole( 'heading', { name: WHOLE } ) ).toBeVisible();
		await expect( list.getByRole( 'heading', { name: SETAGAYA } ) ).toBeVisible();
		await expect( list.getByRole( 'heading', { name: ROOF } ) ).toBeVisible();
	} );

	test( '来訪者が、いまどの種類を見ているかがわかる', async ( { page } ) => {
		await page.goto( '/works/' );
		await expect( page.getByRole( 'link', { name: 'すべて', exact: true } ) ).toHaveAttribute( 'aria-current', 'page' );

		await page.getByRole( 'link', { name: 'リフォーム', exact: true } ).click();
		await expect( page.getByRole( 'link', { name: 'リフォーム', exact: true } ) ).toHaveAttribute( 'aria-current', 'page' );
	} );
} );
