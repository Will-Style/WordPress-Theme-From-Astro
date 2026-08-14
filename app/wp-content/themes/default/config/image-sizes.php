<?php
/* ========================================================
アイキャッチ画像：生成サイズ
　カードの表示比率（16/10・4/3 など）はCSSの aspect-ratio + object-fit で
　作っているため、ここではトリミングせず長辺だけを揃える。
　・srcset は「元画像と同じ比率のサイズ」だけをまとめるので、
　　非トリミングで登録しておくとブラウザが最適な1枚を選べる
　・.c-card__works.contain は object-fit:contain（絵を全面表示）のため、
　　サーバー側でトリミングすると絵が欠ける
　※ここを変更・追加した場合、既存画像は自動では作られない。
　　Regenerate Thumbnails などで再生成すること
=========================================================*/
add_action('after_setup_theme', function () {
    // 詳細ページのメインビジュアル。遷移アニメーションで飛ばす画像・動画のポスターにも使う
    // WordPress は 2560px 超の画像を自動縮小するため、その範囲内に収めている
    add_image_size('hero', 2400, 2400, false);

    // works・cases の一覧カード（1800pxコンテナの2カラム＝実寸800px前後）、
    // トップのギャラリータイル
    add_image_size('card', 1600, 1600, false);

    // blog の一覧カード（1800pxコンテナの4カラム＝実寸435px前後）、
    // お知らせ一覧・関連記事（最大300px）
    add_image_size('card-sm', 800, 800, false);

    // 前後記事ナビ（80px）・トップのお知らせ（45px）の小サムネイル
    add_image_size('thumb-sm', 240, 240, false);
});

/**
 * sizes 属性の値を返す
 *
 * カードの実寸（CSSの width / grid のカラム数）に合わせておくと、
 * srcset の中から必要な解像度だけをダウンロードしてくれる。
 * 指定しないと WordPress の既定値 `(max-width: 1024px) 100vw, 1024px` になり、
 * 45pxで表示する画像でも1024pxを落としてしまう。
 *
 * @param string $key 表示箇所のキー
 * @return string
 */
function get_image_sizes_attr($key)
{
    $sizes = [
        // works一覧：2カラム × カード幅70〜90%
        'works-card' => '(min-width: 1800px) 800px, (min-width: 768px) 45vw, 90vw',
        // cases一覧：2カラム
        'cases-card' => '(min-width: 1800px) 885px, (min-width: 768px) 50vw, 100vw',
        // blog一覧：4カラム（SPは2カラム）
        'blog-card' => '(min-width: 1800px) 435px, (min-width: 1024px) 25vw, 50vw',
        // トップのギャラリータイル：clamp(320px, 100vw, 600px)
        'hero-tile' => '(min-width: 600px) 600px, 100vw',
        // お知らせ一覧：clamp(180px, 35vw, 300px)
        'news-list' => '(min-width: 858px) 300px, 35vw',
        // 関連記事：上と同じだが575px以下は90px固定
        'related' => '(max-width: 575px) 90px, (min-width: 858px) 300px, 35vw',
        // 前後記事ナビ：80px（SP 66px）
        'pagenavi' => '80px',
        // トップのお知らせ：45px
        'top-news' => '45px',
        // 詳細ページのメインビジュアル：全幅
        'hero' => '100vw',
    ];

    return isset($sizes[$key]) ? $sizes[$key] : '';
}
