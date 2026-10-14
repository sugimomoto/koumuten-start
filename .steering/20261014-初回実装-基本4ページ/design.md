# 設計：初回実装（トップページ・会社紹介・施工事例・お問い合わせ）

> [requirements.md](requirements.md) の受け入れ条件を満たすための設計

## 0. デザイン（Claude Design）

- **デザインの URL：https://claude.ai/artifact/WyuArprYDKQJt5HSHZZPyd**
- 10枚：トップページ・会社の紹介・施工事例の一覧・施工事例の詳細・お問い合わせ × **PC（1280px）／スマホ（390px）**
- メニューと画面どうしのリンクはつながっているので、クリックして画面を行き来できる。お問い合わせのフォームは実際に動き、空のまま送信するとエラー、入れて送信すると受付の表示に変わる
- 実装のときは、下の値をテーマの `style.css` に写す。**写真はすべて差し替え用の枠**。電話番号「03-0000-0000」と住所「〒000-0000 〇〇県…」は仮の値（実データ未入手）

### 色（`style.css` のカスタムプロパティにする）

| 役割 | 値 | 使うところ |
|---|---|---|
| 地色 | `#F7F4EF` | ページの背景 |
| 面 | `#FFFFFF` | ヘッダー・カード・フォーム |
| 文字 | `#1C2321` | 本文 |
| 薄い文字 | `#4A5551` | 補足・ラベル |
| 主色（藍） | `#17414E` | 見出し・リンク・ボタン・電話のバンド |
| 強調（柿渋） | `#A8481F` | 費用の金額・送信ボタン・必須マーク |
| 罫線 | `#DDD6CA` | 枠線・区切り |
| 種類バッジ | 背景 `#E3EAEC` ／ 文字 `#17414E` | 施工事例の種類 |
| 入力欄の枠 | `#B9C7CB` | フォーム |
| エラー | 背景 `#FAEDE6` ／ 枠 `#A8481F` ／ 文字 `#8E3A16` | 入力チェックの表示 |

文字色と背景色の組み合わせは、すべて 4.5:1 以上のコントラストを確かめてある（薄い文字 7.2:1、主色 10.1:1、強調 5.4:1、エラー文字 6.8:1）。

### 書体と大きさ

| 役割 | 値 |
|---|---|
| 見出し | `'Shippori Mincho'`（明朝）600・700。代替：Hiragino Mincho ProN → Yu Mincho → serif |
| 本文・UI | `'Zen Kaku Gothic New'` 400・500・700。代替：Hiragino Kaku Gothic ProN → Yu Gothic → sans-serif |
| 本文の大きさ | 17px（PC の読み物の本文は 18px）。行送り 1.9 |
| 見出しの大きさ | h1：PC 42〜48px／スマホ 30〜32px。h2：PC 30〜34px／スマホ 26〜28px |
| 角丸 | 2px（カード・ボタン・入力欄） |
| 触るところの高さ | 44px 以上。スマホのボタン・メニュー・入力欄は 48〜52px |

Google Fonts から2書体を読み込む（`fonts.googleapis.com/css2`、`display=swap`）。
**「スマホで字がちっちゃくて見づらい」[00:12] への答えが、本文 17px・行送り 1.9・電話番号 32px。** ここは小さくしない。

### デザインで決めた、設計に関わること

- 電話番号は、**ヘッダー（スマホでは画面の上の藍色の帯）・各ページの下のバンド・フッター**の3か所に出す。`parts/phone.php` を3か所で使い回す
- スマホのメニューは `<details>`／`<summary>`。開くと各ページへのリンクが縦に並ぶ（JavaScript なし）
- 施工事例のカードは **写真＋種類バッジ＋タイトル**（`parts/work-card.php`）。一覧・トップページ・絞り込み後で同じものを使う
- 施工事例の詳細は、PC では本文の左に「工事のあらまし」（場所・工期・築年数・種類・費用）を右の囲みで出す。スマホでは写真の下に囲みを置く
- 費用は囲みの最後に、柿渋色・明朝・28〜30px で大きく出す。**空欄ならこの行ごと出さない**（R-02）
- お客様の声（R-04・次の作業）は、詳細ページの「この工事について」の下に入る。そこに足せる形にしておく

## 1. 全体の分け方

| 置き場所 | 役割 | 今回作るもの |
|---|---|---|
| プラグイン `koumuten-core` | **データと機能** | 施工事例のカスタム投稿タイプ・種類のタクソノミー・カスタムフィールド、お問い合わせの受付処理 |
| テーマ `koumuten` | **見た目だけ** | 4ページのテンプレート、ヘッダー／フッター、スタイル（スマホ優先） |
| `bin/setup-content.sh` | **中身** | 固定ページ・メニュー・サンプルの施工事例（何度実行しても同じ状態） |
| `tests/e2e/*.spec.ts` | **確認** | 受け入れ条件ごとの E2E テスト |

テーマを替えてもデータが消えないように、投稿タイプ・フィールド・フォーム処理はすべてプラグイン側に置く。
お問い合わせフォームの HTML もプラグインがショートコードで出し、**見た目（CSS）だけ**テーマが持つ。

## 2. 作るファイル

### プラグイン（新規）

```
wp-content/plugins/koumuten-core/
├── koumuten-core.php          プラグイン本体。ヘッダー、定数、includes の読み込み、有効化／無効化
├── uninstall.php              削除時の後片付け（オプションの削除）
└── includes/
    ├── class-work.php         施工事例：投稿タイプ koumuten_work ＋ タクソノミー koumuten_work_type
    ├── class-work-fields.php  施工事例のカスタムフィールド（メタボックス・保存・取得）
    ├── class-contact-form.php お問い合わせ：ショートコード、検証、保存、メール通知、迷惑メール対策
    └── class-inquiry.php      問い合わせの保存先：投稿タイプ koumuten_inquiry（管理画面で中身を見る）
```

- 本体ではファイル読み込みと `add_action` の登録だけを行い、重い処理はフックの中で動かす
- 有効化時：投稿タイプとタクソノミーを登録したあとに `flush_rewrite_rules()`（URL を有効にするため）
- 無効化時：`flush_rewrite_rules()`。**投稿データは消さない**

### テーマ（新規）

```
wp-content/themes/koumuten/
├── style.css                      テーマヘッダー＋スタイル（スマホ優先・1枚）
├── functions.php                  テーマのセットアップ、メニュー登録、電話番号の取得
├── header.php                     ヘッダー（電話番号＝tel: リンク、メニュー）
├── footer.php                     フッター（電話番号）
├── front-page.php                 R-12 トップページ
├── page.php                       R-06 会社の紹介／お問い合わせ（固定ページ共通）
├── archive-koumuten_work.php      R-01・R-03 施工事例の一覧＋絞り込み
├── taxonomy-koumuten_work_type.php R-03 種類で絞った一覧（一覧と同じ見た目）
├── single-koumuten_work.php       R-01・R-02 施工事例の詳細
├── index.php                      フォールバック
└── parts/
    ├── work-card.php              一覧に並ぶ1件（写真＋タイトル＋種類）
    └── phone.php                  電話番号の表示（ヘッダー・フッター・トップで使い回す）
```

### そのほか（新規）

```
bin/setup-content.sh               中身を作るスクリプト（--remote で本番）
tests/mu-plugins/mail-log.php      【ローカル専用】送信メールを記録する（メール通知のテスト用）
tests/e2e/front-page.spec.ts       R-12
tests/e2e/about.spec.ts            R-06
tests/e2e/works.spec.ts            R-01・R-02・R-03
tests/e2e/works-admin.spec.ts      R-01（管理画面から追加）・N-02
tests/e2e/contact.spec.ts          R-09・R-10・N-03
tests/e2e/mobile.spec.ts           N-01
```

### 変えるファイル

| ファイル | 変更 |
|---|---|
| `.wp-env.json` | `mappings` に `wp-content/mu-plugins: ./tests/mu-plugins` を足す（メール記録はローカルだけ。`bin/deploy.sh` はテーマとプラグインしか送らないので本番には入らない） |
| `docs/product-requirements.md` | 各要件の「テスト」欄を、書いたテストのファイル名とテスト名に更新する |

## 3. データ

### 施工事例：投稿タイプ `koumuten_work`

| 設定 | 値 | 理由 |
|---|---|---|
| `public` | `true` | 来訪者が見る |
| `has_archive` | `true` | 一覧ページ `/works/` のため |
| `rewrite.slug` | `works` | URL を `/works/○○/` にする |
| `supports` | `title`, `editor`, `thumbnail`, `excerpt` | 写真はアイキャッチ画像（thumbnail）で持つ |
| `show_in_rest` | `true` | ブロックエディターで編集するため |
| `menu_icon` | `dashicons-hammer` | 管理画面でわかりやすく |
| `labels` | 「施工事例」 | 事務担当が迷わないよう日本語 |

### 施工事例の種類：タクソノミー `koumuten_work_type`

- 階層なし（タグ型ではなくカテゴリー型の見た目：`hierarchical => true`、選択ミスを防ぐためチェックボックスで選ばせる）
- `rewrite.slug` は `work-type` → 絞り込みの URL は `/work-type/reform/`
- 初期の3つ（`bin/setup-content.sh` が作る）：

| 名前 | スラッグ |
|---|---|
| リフォーム | `reform` |
| 新築 | `newbuild` |
| 修繕 | `repair` |

### 施工事例のカスタムフィールド（post meta）

すべて `_koumuten_` で始める（ほかのプラグインとぶつからないように）。
`register_post_meta()` で型とサニタイズを宣言し、管理画面はメタボックスで入力する。

| キー | 表示名 | 型 | 入力 | サニタイズ | 表示 |
|---|---|---|---|---|---|
| `_koumuten_area` | 場所 | string | テキスト（例：東京都世田谷区） | `sanitize_text_field` | そのまま |
| `_koumuten_duration` | 工期 | string | テキスト（例：約2か月） | `sanitize_text_field` | そのまま |
| `_koumuten_building_age` | 築年数 | string | テキスト（例：築30年） | `sanitize_text_field` | そのまま |
| `_koumuten_cost` | 費用（万円） | integer | 数値（空欄可） | `absint`、空文字はメタを削除 | **「約○○万円」**。空欄なら項目ごと出さない（R-02） |

- 場所は「区・市までの粗さ」（R-01／[01:10]）。細かい住所を入れないよう、入力欄の下に注意書きを出す
- 費用は**万円の整数**で持つ。空のまま保存したらメタを削除し、詳細ページでは費用の行そのものを出さない
- メタボックスの保存：`wp_verify_nonce` → `current_user_can( 'edit_post', $post_id )` → `DOING_AUTOSAVE` でなければ保存

### 問い合わせ：投稿タイプ `koumuten_inquiry`

メールが届かなくても内容が残るように、サイト側にも保存する。

| 設定 | 値 |
|---|---|
| `public` | `false`（`publicly_queryable => false`） |
| `show_ui` | `true`（管理画面で読める） |
| `capability_type` | 管理者のみ読める（`capabilities` で `edit_posts` を `manage_options` に） |
| `supports` | `title`（お名前）のみ。本文はメタで持つ |

| メタキー | 内容 | サニタイズ |
|---|---|---|
| `_koumuten_inq_email` | メールアドレス | `sanitize_email` |
| `_koumuten_inq_tel` | 電話番号（任意） | `sanitize_text_field` |
| `_koumuten_inq_type` | ご相談の種類 | 選択肢にある値だけ許可（ホワイトリスト） |
| `_koumuten_inq_body` | お問い合わせ内容 | `sanitize_textarea_field` |

## 4. 画面

### R-12 トップページ（`front-page.php`）

| 置くもの | 受け入れ条件 |
|---|---|
| 電話番号（ヘッダーにも出るが、本文にも大きく） | トップページを開くと電話番号が見える |
| 施工事例の新着6件（写真つきカード） | 施工事例の新着が写真つきで見える |
| 「施工事例をもっと見る」「会社の紹介」「お問い合わせ」への入口 | 各ページへ移動できる |

### R-06 会社の紹介（固定ページ `/about/` ＋ `page.php`）

- 本文は固定ページの中身をそのまま出す。事務担当が管理画面から書き替えられる
- 本文は `the_content()`（WordPress が整形・エスケープ済み）

### R-01・R-03 施工事例の一覧（`archive-koumuten_work.php` ／ `taxonomy-koumuten_work_type.php`）

- 1件ずつ `parts/work-card.php` で並べる：アイキャッチ画像＋タイトル＋種類
- 絞り込みは**リンク**（JavaScript なしで動く）。一覧の上に並べる：

```
[すべて] [リフォーム] [新築] [修繕]
   ↓         ↓          ↓        ↓
 /works/  /work-type/reform/  /work-type/newbuild/  /work-type/repair/
```

- いま選んでいるものに `aria-current="page"` を付ける（テストが「どれを選んでいるか」を見るため）
- 一覧と絞り込み後の見た目は同じにする（`taxonomy-koumuten_work_type.php` は同じパーツを使う）

### R-01・R-02 施工事例の詳細（`single-koumuten_work.php`）

```
タイトル
アイキャッチ画像                     ← 写真
┌─────────────────────────┐
│ 場所      東京都世田谷区          │  ← 定義リスト（dl/dt/dd）
│ 工期      約2か月                 │
│ 築年数    築30年                  │
│ 費用      約450万円               │  ← 空欄なら、この行を出さない（R-02）
│ 種類      リフォーム              │
└─────────────────────────┘
本文
（※ お客様の声はここに入る予定。今回は作らない＝R-04 は次の作業）
```

### R-09 電話番号（`header.php` ／ `footer.php` ／ `parts/phone.php`）

- ヘッダーの目立つ位置（スマホでも画面の上に出る）とフッターの両方に出す → 「どのページを開いても見える」
- `<a href="tel:0312345678">03-1234-5678</a>`。`tel:` の中身は数字とハイフンだけに整える
- 電話番号は `functions.php` で WordPress のオプション（`koumuten_phone`）から読み、`bin/setup-content.sh` が初期値（ダミー）を入れる。あとから差し替えられる

### R-10 お問い合わせ（固定ページ `/contact/` ＋ ショートコード `[koumuten_contact_form]`）

入力項目：

| 項目 | 必須 | 形 |
|---|---|---|
| お名前 | ◯ | テキスト |
| メールアドレス | ◯ | email |
| 電話番号 | — | tel |
| ご相談の種類 | ◯ | `<select>`：リフォーム／新築／修繕／その他 |
| お問い合わせ内容 | ◯ | textarea |

処理の流れ（`admin_post_nopriv` ではなく、**同じページに POST して処理**し、結果を同じページに出す）：

```
POST
 ↓ 1. nonce 検証（wp_verify_nonce）           → 失敗なら「時間が経ちすぎました」
 ↓ 2. 迷惑メール対策（N-03）
 ↓    a. ハニーポット：見えない入力欄に何か入っていたら受け付けない
 ↓    b. 時間の罠：フォーム表示から 3 秒未満の送信は受け付けない
 ↓    c. 連続送信の制限：同じ送信元から 60 秒以内の 2 回目以降は受け付けない（transient）
 ↓ 3. サニタイズ（sanitize_text_field / sanitize_email / sanitize_textarea_field）
 ↓ 4. 入力チェック → 足りない項目があれば、その項目に「○○を入れてください」と出す（入力済みの値は残す）
 ↓ 5. koumuten_inquiry として保存
 ↓ 6. wp_mail で山田工務店に通知（宛先は管理者のメールアドレス）
 ↓ 7. 「お問い合わせを受け付けました」と表示
```

- 迷惑メール対策に CAPTCHA は使わない（外部サービスが増えるため）。ハニーポット＋時間の罠＋連続送信の制限の3段で守る
- 受け付けの表示・エラーの表示は、同じページの中に出す（`role="alert"` を付けて、テストと読み上げの両方で拾えるようにする）
- 出力はすべて `esc_html` / `esc_attr` / `esc_url` を通す

### N-01 スマホで見やすい（`style.css`）

- スマホ優先で書き、広い画面には `min-width` のメディアクエリで足す
- 本文の文字は 16px 以上。`box-sizing: border-box`、画像は `max-width: 100%`（横にはみ出さないように）
- メニューはスマホではボタンで開閉する。**`<details>`/`<summary>` で作る**（JavaScript なしで開閉でき、テストからも操作できる）

## 5. 中身を作るスクリプト（`bin/setup-content.sh`）

GitHub Actions が `bash bin/setup-content.sh --remote` で呼ぶ。ローカルは引数なし。

```
wp() の切り替え：
  引数なし   → npx wp-env run cli wp …
  --remote   → bash bin/wp-remote.sh …
```

何度実行しても同じ状態になるように、**作る前に必ず「あるか」を確かめる**（`wp post list --name=…` など）。

| 作るもの | 冪等にする方法 |
|---|---|
| テーマ `koumuten` を有効化 | `wp theme activate`（すでに有効なら何も変わらない） |
| プラグイン `koumuten-core` を有効化 | `wp plugin activate`（同上） |
| パーマリンク構造 `/%postname%/` | `wp rewrite structure` ＋ `wp rewrite flush` |
| 固定ページ 3つ（`home` / `about` / `contact`） | スラッグで検索して、無ければ作る・あれば中身を更新 |
| トップページの設定 | `wp option update show_on_front page` ＋ `page_on_front` |
| 電話番号のダミー（`koumuten_phone`） | `wp option update`（値が同じなら変わらない） |
| 種類の3つ（リフォーム／新築／修繕） | `wp term create … || true` ではなく、`wp term list` で確かめてから作る |
| メニュー（グローバルナビ） | 既にあれば一度消して作り直す（順番まで同じ状態にするため） |
| 施工事例のサンプル 7件 | スラッグで検索して、無ければ作る。**費用が空欄のものを必ず1件入れる**（R-02 のテストのため） |
| サンプルの写真 | 色だけの画像をスクリプト内で作って `wp media import`、アイキャッチに設定 |

サンプルの施工事例（種類ごとに2件以上＋費用なし1件）：

| スラッグ | 種類 | 費用 |
|---|---|---|
| `sample-reform-kitchen` | リフォーム | 150 |
| `sample-reform-whole` | リフォーム | 980 |
| `sample-newbuild-setagaya` | 新築 | 2800 |
| `sample-newbuild-chofu` | 新築 | 3200 |
| `sample-repair-roof` | 修繕 | 45 |
| `sample-repair-gutter` | 修繕 | （空欄） |
| `sample-reform-bath` | リフォーム | 120 |

> 修繕の2件は、打ち合わせで出た台風の屋根修理・雨どいの話（R-01 の [04:42]）に合わせた見本にする。実際の写真は山田一郎さんから届いたら差し替える（宿題 2-03）。

## 6. テストの設計

| ファイル | 対象 | タグ | 備考 |
|---|---|---|---|
| `front-page.spec.ts` | R-12 | `@readonly` | 読むだけ。本番でも動く |
| `about.spec.ts` | R-06 | `@readonly` | 同上 |
| `works.spec.ts` | R-01（一覧・詳細）・R-02・R-03 | `@readonly` | サンプルデータを読むだけ。本番でも動く |
| `works-admin.spec.ts` | R-01（管理画面から追加）・N-02 | なし | **書き込む** → `isLocal` でスキップ。タイトルに `TEST_NAME`（E2Eテスト）を入れ、後片付けで消える |
| `contact.spec.ts` | R-09・R-10・N-03 | 表示の確認だけ `@readonly` | **送信は書き込む** → `isLocal` でスキップ |
| `mobile.spec.ts` | N-01 | `@readonly` | `page.setViewportSize({ width: 375, height: 812 })` |
| `navigation.spec.ts` | ヘッダーのメニュー（パソコン／スマホ） | `@readonly` | **実装中に追加**。パソコンの幅でメニューが出ない不具合を拾えなかったため |

- テスト名は日本語で「利用者がやること・見えること」（例：`来訪者が施工事例の一覧でリフォームを選ぶと、リフォームの事例だけが並ぶ`）
- 見た目の細部（色・px）には依存させない。役割（`role`）・見出し・ラベルの文字で要素を見つける
- メール通知の確認（R-10）：`tests/mu-plugins/mail-log.php` が `pre_wp_mail` で送信内容をオプションに記録する。テストは WP-CLI（`wp option get koumuten_mail_log`）で宛先と件名を確かめる。**ローカル専用**で、`bin/deploy.sh` はテーマとプラグインしか送らないので本番には入らない
- 迷惑メール対策の確認（N-03）：ハニーポットに値を入れて送信し、**受け付けの表示が出ないこと**と、`koumuten_inquiry` が増えていないことを確かめる
- 既存の `tests/e2e/helpers.ts` をそのまま使う（`wpCli` / `isLocal` / `TEST_NAME` / `loginAsAdmin` / `openEditor` / `updatePost` / `postId` / `deletePostMeta`）。`openEditor` と `updatePost` はメタボックス（`meta-box-loader=1`）の保存を待つ作りなので、**カスタムフィールドはメタボックスで実装する**（この helpers に合う）

## 7. 影響範囲

- サイトはいま空なので、**壊すものはない**。既存の E2E テスト（`*.spec.ts`）も無い
- `.wp-env.json` に mu-plugins の mappings を足すため、**`npm start`（wp-env）の再起動が必要**
- 投稿タイプとタクソノミーを増やすので、URL を有効にするため `wp rewrite flush` が必要（`bin/setup-content.sh` が実行する）
- `bin/setup-content.sh` を作ると、**GitHub Actions の deploy が本番でもこれを実行するようになる**（今まではファイルが無くてスキップされていた）。本番に初めてサンプルデータが入るので、中身を確認してから main に push する
- 今回作らない R-04（お客様の声）が施工事例の詳細に入る予定なので、`single-koumuten_work.php` は**本文の下に足せる形**にしておく

## 8. セキュリティ

| 対象 | やること |
|---|---|
| メタボックスの保存 | `wp_nonce_field` ＋ `wp_verify_nonce`、`current_user_can( 'edit_post', $post_id )`、自動保存は無視 |
| フォームの受付 | `wp_nonce_field` ＋ `wp_verify_nonce`、ハニーポット、時間の罠、連続送信の制限 |
| 入力 | `sanitize_text_field` / `sanitize_email` / `sanitize_textarea_field` / `absint`。ご相談の種類は選択肢のホワイトリスト |
| 出力 | `esc_html` / `esc_attr` / `esc_url`。本文は `the_content()` |
| 問い合わせの閲覧 | `koumuten_inquiry` は管理者だけが見られる（`publicly_queryable => false`） |
| 秘密情報 | APIキー・パスワード・秘密鍵はファイルにもログにも画面にも書かない。宛先は管理者のメールアドレス（`get_option('admin_email')`）を使う |
