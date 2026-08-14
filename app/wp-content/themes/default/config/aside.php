<?php

// /**
//  * フッター手前のasideの出し分け
//  *
//  * 基本ルール
//  *   works関連（一覧・カテゴリー・記事）→ AsideCase
//  *   それ以外                          → AsideWorks
//  * 記事ページではさらに、同じ投稿タイプを同カテゴリーで絞って並べる
//  *   このとき、常時表示のAsideBlogと重複する場合（blogの記事ページ）は
//  *   同カテゴリー版に寄せて1つにまとめる
//  */


// /**
//  * 表示中のページがworks関連かどうか
//  */
// function is_works_context()
// {
//   return is_singular('works') || is_post_type_archive('works') || is_tax('works-cat') || is_tax('industry');
// }


// /**
//  * asideを1つ読み込む
//  * $args は読み込み先のテンプレートに $aside_args として渡る
//  */
// function render_aside($name, $args = [])
// {
//   $aside_args = wp_parse_args($args, [
//     'post_type' => '',
//     'taxonomy' => '',
//     'terms' => [],
//     'exclude' => 0,
//     'posts_per_page' => 3,
//   ]);

//   include(get_template_directory() . '/components/' . $name . '.php');
// }


// /**
//  * 記事ページで、同じ投稿タイプを同カテゴリーで並べるための引数を組み立てる
//  * カテゴリーが未設定の記事もあるため、タームが無ければ絞り込みなしで返す
//  */
// function get_same_category_aside_args($post_type, $taxonomy, $posts_per_page = 3)
// {
//   $terms = wp_get_object_terms(get_the_ID(), $taxonomy, ['fields' => 'ids']);
//   $terms = is_wp_error($terms) ? [] : $terms;

//   return [
//     'post_type' => $post_type,
//     'taxonomy' => $terms ? $taxonomy : '',
//     'terms' => $terms,
//     'exclude' => get_the_ID(),
//     'posts_per_page' => $posts_per_page,
//   ];
// }


// /**
//  * asideのWP_Query引数を組み立てる
//  * 各Aside*.phpから呼ぶ
//  */
// function build_aside_query_args($post_type, $aside_args = [])
// {
//   $aside_args = wp_parse_args($aside_args, [
//     'taxonomy' => '',
//     'terms' => [],
//     'exclude' => 0,
//     'posts_per_page' => 3,
//   ]);

//   $args = [
//     'post_type' => $post_type,
//     'posts_per_page' => (int) $aside_args['posts_per_page'],
//     'ignore_sticky_posts' => true,
//     'no_found_rows' => true,
//   ];

//   if (!empty($aside_args['exclude'])) {
//     $args['post__not_in'] = [(int) $aside_args['exclude']];
//   }

//   if (!empty($aside_args['taxonomy']) && !empty($aside_args['terms'])) {
//     $args['tax_query'] = [[
//       'taxonomy' => $aside_args['taxonomy'],
//       'field' => 'term_id',
//       'terms' => $aside_args['terms'],
//     ]];
//   }

//   return $args;
// }


// /**
//  * ページに応じてasideを出力する
//  */
// function render_asides()
// {
//   // 常時表示のAsideBlogを、同カテゴリー版に置き換えるかどうか
//   $blog_is_same_type = is_singular('blog');

//   // 1つ目：works関連ならCase Study、それ以外はWorks
//   if (is_works_context()) {
//     render_aside('AsideCase');
//   } else {
//     render_aside('AsideWorks');
//   }

//   // 2つ目：記事ページでは同じ投稿タイプを同カテゴリーで
//   if (is_singular('works')) {
//     render_aside('AsideWorks', get_same_category_aside_args('works', 'works-cat'));
//   } elseif (is_singular('cases')) {
//     render_aside('AsideCase', get_same_category_aside_args('cases', 'cases-cat'));
//   } elseif ($blog_is_same_type) {
//     render_aside('AsideBlog', get_same_category_aside_args('blog', 'blog-cat', 4));
//   }

//   // 3つ目：常時表示のAsideBlog（blogの記事ページでは上で出しているので省く）
//   if (!$blog_is_same_type) {
//     render_aside('AsideBlog', ['posts_per_page' => 4]);
//   }
// }
