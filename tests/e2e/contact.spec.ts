// お問い合わせ（R-09 電話／R-10 フォーム／N-03 迷惑メール対策）
import { test, expect } from '@playwright/test';
import { isLocal, wpCli, TEST_NAME } from './helpers';

/** フォームを開いてから送信するまでの「短すぎる送信」の見張りを通り抜けるための待ち時間 */
const FILL_WAIT = 3500;

async function fillValidForm( page ) {
	await page.getByLabel( 'お名前' ).fill( `${ TEST_NAME } 太郎` );
	await page.getByLabel( 'メールアドレス' ).fill( 'e2e@example.com' );
	await page.getByLabel( '電話番号' ).fill( '03-1111-2222' );
	await page.getByLabel( 'ご相談の種類' ).selectOption( { label: 'リフォーム' } );
	await page.getByLabel( 'お問い合わせ内容' ).fill( 'E2Eテストからの問い合わせです。' );
}

function inquiryCount(): number {
	return Number( wpCli( 'post', 'list', '--post_type=koumuten_inquiry', '--post_status=any', '--format=count' ) );
}

/** 送られたメールの記録（ローカル専用の mu-plugin が書く）。1通も無ければ空。 */
function mailLog(): Array< { to: string; subject: string; message: string } > {
	try {
		return JSON.parse( wpCli( 'option', 'get', 'koumuten_mail_log', '--format=json' ) );
	} catch {
		return []; // オプションそのものが無い＝1通も送られていない
	}
}

test.describe( '電話での問い合わせ（R-09）@readonly', () => {
	test( '来訪者がどのページを開いても、電話番号が目立つ位置に見える', async ( { page } ) => {
		for ( const url of [ '/', '/works/', '/works/sample-reform-whole/', '/about/', '/contact/' ] ) {
			await page.goto( url );
			await expect(
				page.getByRole( 'banner' ).getByRole( 'link', { name: /03-0000-0000/ } ).first()
			).toBeVisible();
		}
	} );

	test( 'スマホで見ている来訪者が電話番号を押すと、電話をかける動作になる', async ( { page } ) => {
		await page.setViewportSize( { width: 375, height: 812 } );
		await page.goto( '/contact/' );

		const tel = page.getByRole( 'banner' ).getByRole( 'link', { name: /03-0000-0000/ } ).first();
		await expect( tel ).toBeVisible();
		await expect( tel ).toHaveAttribute( 'href', /^tel:/ );
	} );
} );

test.describe( 'お問い合わせフォームの見た目（R-10）@readonly', () => {
	test( '来訪者が問い合わせフォームを開くと、ご相談の種類を選ぶ欄があり、リフォーム・新築などから選べる', async ( { page } ) => {
		await page.goto( '/contact/' );

		const select = page.getByLabel( 'ご相談の種類' );
		await expect( select ).toBeVisible();

		const options = await select.locator( 'option' ).allTextContents();
		for ( const name of [ 'リフォーム', '新築', '修繕', 'その他' ] ) {
			expect( options ).toContain( name );
		}

		// ほかの入力欄もある
		for ( const label of [ 'お名前', 'メールアドレス', '電話番号', 'お問い合わせ内容' ] ) {
			await expect( page.getByLabel( label ) ).toBeVisible();
		}
	} );
} );

test.describe( 'お問い合わせの送信（R-10・N-03）', () => {
	test.skip( ! isLocal, '本番にはデータを書き込まない' );

	test.beforeEach( () => {
		// 連続送信の制限と、メールの記録をまっさらにする
		wpCli( 'transient', 'delete', '--all' );
		wpCli( 'option', 'delete', 'koumuten_mail_log' );
	} );

	test( '来訪者が必要な項目を入れて送信すると、受け付けたことがわかる表示が出る', async ( { page } ) => {
		const before = inquiryCount();

		await page.goto( '/contact/' );
		await page.waitForTimeout( FILL_WAIT );
		await fillValidForm( page );
		await page.getByRole( 'button', { name: '送信する' } ).click();

		await expect( page.getByRole( 'alert' ) ).toContainText( '受け付けました' );
		expect( inquiryCount() ).toBe( before + 1 );
	} );

	test( '来訪者が必要な項目を空のまま送信すると、送信されず、どこを直せばよいかがわかる表示が出る', async ( { page } ) => {
		const before = inquiryCount();

		await page.goto( '/contact/' );
		await page.waitForTimeout( FILL_WAIT );
		await page.getByRole( 'button', { name: '送信する' } ).click();

		// どこを直せばよいかが、項目ごとに出る
		await expect( page.getByText( 'お名前を入れてください。' ) ).toBeVisible();
		await expect( page.getByText( 'メールアドレスを入れてください。' ) ).toBeVisible();
		await expect( page.getByText( 'ご相談の種類を選んでください。' ) ).toBeVisible();
		await expect( page.getByText( 'お問い合わせ内容を入れてください。' ) ).toBeVisible();

		// 受け付けの表示は出ないし、保存もされていない
		await expect( page.getByText( '受け付けました' ) ).toHaveCount( 0 );
		expect( inquiryCount() ).toBe( before );
	} );

	test( '来訪者が入れた内容は、直すときに残っている', async ( { page } ) => {
		await page.goto( '/contact/' );
		await page.waitForTimeout( FILL_WAIT );
		await page.getByLabel( 'お名前' ).fill( `${ TEST_NAME } 花子` );
		await page.getByRole( 'button', { name: '送信する' } ).click();

		await expect( page.getByLabel( 'お名前' ) ).toHaveValue( `${ TEST_NAME } 花子` );
	} );

	test( '問い合わせが送信されると、山田工務店にメールで知らせが届く', async ( { page } ) => {
		await page.goto( '/contact/' );
		await page.waitForTimeout( FILL_WAIT );
		await fillValidForm( page );
		await page.getByRole( 'button', { name: '送信する' } ).click();
		await expect( page.getByRole( 'alert' ) ).toContainText( '受け付けました' );

		const log = mailLog();
		expect( log.length ).toBe( 1 );
		expect( log[ 0 ].to ).toBe( wpCli( 'option', 'get', 'admin_email' ) );
		expect( log[ 0 ].subject ).toContain( 'お問い合わせ' );
		expect( log[ 0 ].message ).toContain( 'e2e@example.com' );
	} );

	test( '自動送信とみなされる送信は、受け付けられない', async ( { page } ) => {
		const before = inquiryCount();

		await page.goto( '/contact/' );
		await page.waitForTimeout( FILL_WAIT );
		await fillValidForm( page );

		// 人には見えない欄（ハニーポット）に、自動送信のプログラムだけが書き込む
		await page.locator( 'input[name="koumuten_website"]' ).evaluate(
			( el: HTMLInputElement ) => ( el.value = 'http://spam.example.com' )
		);
		await page.getByRole( 'button', { name: '送信する' } ).click();

		await expect( page.getByText( '受け付けました' ) ).toHaveCount( 0 );
		expect( inquiryCount() ).toBe( before );
		expect( mailLog() ).toHaveLength( 0 );
	} );

	test( 'フォームを開いてすぐの送信は、受け付けられない', async ( { page } ) => {
		const before = inquiryCount();

		await page.goto( '/contact/' );
		await fillValidForm( page ); // 待たずにすぐ送る
		await page.getByRole( 'button', { name: '送信する' } ).click();

		await expect( page.getByText( '受け付けました' ) ).toHaveCount( 0 );
		expect( inquiryCount() ).toBe( before );
	} );
} );
