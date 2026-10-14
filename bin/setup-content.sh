#!/usr/bin/env bash
# サイトの中身（固定ページ・メニュー・施工事例のサンプル）を作る。
# 何度実行しても同じ状態になる（冪等）。作る前に「あるか」を必ず確かめる。
#
# 使い方:
#   bash bin/setup-content.sh            … ローカル（wp-env）
#   bash bin/setup-content.sh --remote   … 本番（XServer。bin/wp-remote.sh 経由）
#
# GitHub Actions の deploy が、公開のあとに --remote で呼ぶ。
set -euo pipefail
cd "$(dirname "$0")/.."

REMOTE=0
if [ "${1:-}" = "--remote" ]; then
  REMOTE=1
  echo "== 本番（$(grep -o 'xw[0-9]*\.xwp\.jp' bin/deploy-config.sh | head -1)）の中身を作ります"
else
  echo "== ローカル（http://localhost:8890）の中身を作ります"
fi

# WP-CLI の呼び分け
wpc() {
  if [ "$REMOTE" = "1" ]; then
    bash bin/wp-remote.sh "$@"
  else
    npx --no-install wp-env run cli wp "$@" 2>/dev/null
  fi
}

PHONE="03-0000-0000"
ADDRESS=$'〒000-0000\n〇〇県〇〇市〇〇町 0-0-0'
WORK_TYPE="koumuten_work_type"
WORK_PT="koumuten_work"

# ---------------------------------------------------------------
# 1. テーマとプラグインを有効にする
# ---------------------------------------------------------------
echo "-- テーマとプラグイン"
wpc theme activate koumuten >/dev/null
wpc plugin activate koumuten-core >/dev/null

# ---------------------------------------------------------------
# 2. 基本の設定
# ---------------------------------------------------------------
echo "-- 基本の設定"
wpc option update blogname "山田工務店" >/dev/null
wpc option update blogdescription "リフォーム・新築・修繕" >/dev/null
wpc option update koumuten_phone "$PHONE" >/dev/null
wpc option update koumuten_address "$ADDRESS" >/dev/null
wpc rewrite structure '/%postname%/' >/dev/null
wpc rewrite flush --hard >/dev/null

# ---------------------------------------------------------------
# 3. 固定ページ（スラッグで探して、無ければ作る・あれば中身を更新）
# ---------------------------------------------------------------
page_id() {
  wpc post list --post_type=page --post_status=any --name="$1" --field=ID | tr -d '\r' | head -1
}

upsert_page() {
  local slug="$1" title="$2" content="$3" id
  id="$(page_id "$slug")"
  if [ -z "$id" ]; then
    id="$(wpc post create --post_type=page --post_status=publish --post_name="$slug" \
      --post_title="$title" --post_content="$content" --porcelain | tr -d '\r' | head -1)"
    echo "   作成: $title (/$slug/) = $id" >&2
  else
    wpc post update "$id" --post_title="$title" --post_content="$content" --post_status=publish >/dev/null
    echo "   更新: $title (/$slug/) = $id" >&2
  fi
  printf '%s' "$id"
}

echo "-- 固定ページ"

ABOUT_BODY='<h2>住まいのことを、何でもご相談いただける工務店でありたい。</h2>
<p>わたしたちは、住まいのリフォームと新築、それに雨どいや屋根といった小さな修繕まで、住まいのご相談をお聞きしています。</p>
<p>このホームページでは、これまでに手がけた住まいを、写真と一緒にご紹介しています。かかった費用の目安も載せていますので、ご相談の前の参考にしていただければと思います。</p>
<p>「うちは築何十年だけど大丈夫だろうか」といったご心配も、よくお聞きします。まずはお電話か、フォームからお気軽にお声がけください。</p>
<p><strong>山田工務店　代表　山田 一郎</strong></p>
<h2>会社概要</h2>
<table>
<tbody>
<tr><th>会社名</th><td>山田工務店</td></tr>
<tr><th>代表者</th><td>山田 一郎</td></tr>
<tr><th>所在地</th><td>〒000-0000　〇〇県〇〇市〇〇町 0-0-0</td></tr>
<tr><th>電話番号</th><td>03-0000-0000</td></tr>
<tr><th>事業内容</th><td>住まいのリフォーム／新築／修繕</td></tr>
</tbody>
</table>'

CONTACT_BODY='<p>お電話か、下のフォームからご相談ください。いただいた内容を確かめて、折り返しご連絡いたします。</p>
[koumuten_contact_form]'

HOME_ID="$(upsert_page home "ホーム" '')"
upsert_page about "会社の紹介" "$ABOUT_BODY" >/dev/null
upsert_page contact "お問い合わせ" "$CONTACT_BODY" >/dev/null

# トップページを「ホーム」にする
wpc option update show_on_front page >/dev/null
wpc option update page_on_front "$HOME_ID" >/dev/null

# ---------------------------------------------------------------
# 4. 施工事例の「種類」（リフォーム・新築・修繕）
# ---------------------------------------------------------------
echo "-- 施工事例の種類"
ensure_term() {
  local slug="$1" name="$2" existing
  existing="$(wpc term list "$WORK_TYPE" --slug="$slug" --field=term_id | tr -d '\r' | head -1)"
  if [ -z "$existing" ]; then
    wpc term create "$WORK_TYPE" "$name" --slug="$slug" >/dev/null
    echo "   作成: $name"
  else
    echo "   あり: $name"
  fi
}
ensure_term reform "リフォーム"
ensure_term newbuild "新築"
ensure_term repair "修繕"

# ---------------------------------------------------------------
# 5. 施工事例のサンプル
#    費用が空欄のものを必ず1件入れる（R-02 のテストのため）
# ---------------------------------------------------------------
echo "-- 施工事例のサンプル"

work_id() {
  wpc post list --post_type="$WORK_PT" --post_status=any --name="$1" --field=ID | tr -d '\r' | head -1
}

# 単色の画像を作って、アイキャッチにする（写真が届くまでの差し替え用）
ensure_photo() {
  local id="$1" slug="$2" r="$3" g="$4" b="$5" title="$6" path
  if [ -n "$(wpc post meta get "$id" _thumbnail_id 2>/dev/null | tr -d '\r')" ]; then
    return
  fi
  path="$(wpc eval "\$d = wp_upload_dir(); \$p = \$d['basedir'] . '/koumuten-${slug}.png'; if ( ! file_exists( \$p ) ) { \$im = imagecreatetruecolor( 1200, 900 ); imagefill( \$im, 0, 0, imagecolorallocate( \$im, ${r}, ${g}, ${b} ) ); imagepng( \$im, \$p ); imagedestroy( \$im ); } echo \$p;" | tr -d '\r' | tail -1)"
  wpc media import "$path" --post_id="$id" --featured_image --title="$title" --porcelain >/dev/null
}

upsert_work() {
  local slug="$1" title="$2" type="$3" area="$4" duration="$5" age="$6" cost="$7" body="$8" r="$9" g="${10}" b="${11}"
  local id
  id="$(work_id "$slug")"
  if [ -z "$id" ]; then
    id="$(wpc post create --post_type="$WORK_PT" --post_status=publish --post_name="$slug" \
      --post_title="$title" --post_content="$body" --porcelain | tr -d '\r' | head -1)"
    echo "   作成: $title = $id"
  else
    wpc post update "$id" --post_title="$title" --post_content="$body" --post_status=publish >/dev/null
    echo "   更新: $title = $id"
  fi

  wpc post term set "$id" "$WORK_TYPE" "$type" >/dev/null
  wpc post meta update "$id" _koumuten_area "$area" >/dev/null
  wpc post meta update "$id" _koumuten_duration "$duration" >/dev/null
  wpc post meta update "$id" _koumuten_building_age "$age" >/dev/null

  # 費用：値があれば入れる。空なら「入っていない状態」にする（R-02）
  if [ -n "$cost" ]; then
    wpc post meta update "$id" _koumuten_cost "$cost" >/dev/null
  else
    wpc post meta delete "$id" _koumuten_cost >/dev/null 2>&1 || true
  fi

  ensure_photo "$id" "$slug" "$r" "$g" "$b" "$title の写真"
}

upsert_work sample-reform-whole "住まい全体のリフォーム" reform \
  "東京都世田谷区" "約3か月" "築32年" "980" \
  '<p>築32年のお住まいに、住まい全体にわたって手を入れたリフォームです。くらしの変わりように合わせて間取りを見直し、台所とお風呂、洗面も入れ替えました。</p><p>お住まいになりながらの工事でしたので、工事の順番をお施主さまと相談しながら進めました。</p>' \
  217 211 199

upsert_work sample-newbuild-setagaya "世田谷の新築" newbuild \
  "東京都世田谷区" "約6か月" "―" "2800" \
  '<p>ご家族4人のお住まいを新しく建てました。</p>' \
  207 214 212

upsert_work sample-repair-roof "台風のあとの屋根の修理" repair \
  "東京都調布市" "約1週間" "築28年" "45" \
  '<p>台風で瓦がずれてしまった屋根を直しました。お電話をいただいた翌日に見にうかがい、応急の処置をしてから本格的に直しました。</p>' \
  212 206 194

upsert_work sample-reform-kitchen "キッチンのリフォーム" reform \
  "東京都三鷹市" "約3週間" "築18年" "150" \
  '<p>使いにくくなっていた台所を入れ替えました。</p>' \
  217 211 199

upsert_work sample-newbuild-chofu "調布の新築" newbuild \
  "東京都調布市" "約7か月" "―" "3200" \
  '<p>二世帯でお住まいになる家を新しく建てました。</p>' \
  207 214 212

upsert_work sample-repair-gutter "雨どいの取り付け直し" repair \
  "東京都世田谷区" "約2日" "築35年" "" \
  '<p>台風で外れてしまった雨どいを取り付け直しました。</p>' \
  212 206 194

upsert_work sample-reform-bath "浴室のリフォーム" reform \
  "東京都武蔵野市" "約2週間" "築22年" "120" \
  '<p>寒かった浴室を入れ替えました。</p>' \
  217 211 199

# ---------------------------------------------------------------
# 6. メニュー（順番まで同じ状態にするため、一度消して作り直す）
# ---------------------------------------------------------------
echo "-- メニュー"
# 名前で探して、あれば消す（スラッグは日本語名から作られるので、ID で扱う）
for old_id in $(wpc menu list --fields=term_id,name --format=csv | tr -d '\r' | awk -F, '$2=="メインメニュー"{print $1}'); do
  wpc menu delete "$old_id" >/dev/null
done

MENU_ID="$(wpc menu create "メインメニュー" --porcelain | tr -d '\r' | head -1)"
wpc menu item add-post "$MENU_ID" "$HOME_ID" --title="ホーム" >/dev/null
wpc menu item add-custom "$MENU_ID" "施工事例" "/works/" >/dev/null
wpc menu item add-post "$MENU_ID" "$(page_id about)" --title="会社の紹介" >/dev/null
wpc menu item add-post "$MENU_ID" "$(page_id contact)" --title="お問い合わせ" >/dev/null
wpc menu location assign "$MENU_ID" primary >/dev/null

# ---------------------------------------------------------------
# 7. 仕上げ
# ---------------------------------------------------------------
wpc rewrite flush --hard >/dev/null
wpc cache flush >/dev/null 2>&1 || true

echo "== できました"
echo "   固定ページ: $(wpc post list --post_type=page --post_status=publish --format=count | tr -d '\r') 件"
echo "   施工事例  : $(wpc post list --post_type="$WORK_PT" --post_status=publish --format=count | tr -d '\r') 件"
