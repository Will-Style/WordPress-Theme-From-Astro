<?php

// /**
//  * 管理画面で会社名を表示する
//  * worksとcasesはタイトルだけでは区別しづらいため、一覧と関連記事の検索に会社名を添える
//  */

// /**
//  * 投稿タイプに応じた会社名を返す
//  */
// function get_admin_corpname($post_id)
// {
//   switch (get_post_type($post_id)) {
//     case 'works':
//       return (string) get_post_meta($post_id, 'projects-corpname', true);
//     case 'cases':
//       return (string) get_post_meta($post_id, 'casestudy-corpname', true);
//     case 'blog':
//       return (string) get_post_meta($post_id, 'projects-corpname', true);
//   }

//   return '';
// }

// /**
//  * 一覧のカラム：タイトルの直後に会社名を差し込む
//  */
// function add_corpname_column($columns)
// {
//   $new = [];
//   foreach ($columns as $key => $label) {
//     $new[$key] = $label;
//     if ($key === 'title') {
//       $new['corpname'] = '会社名';
//     }
//   }

//   // titleカラムが無い場合でも落とさない
//   if (!isset($new['corpname'])) {
//     $new['corpname'] = '会社名';
//   }

//   return $new;
// }
// add_filter('manage_works_posts_columns', 'add_corpname_column');
// add_filter('manage_cases_posts_columns', 'add_corpname_column');
// add_filter('manage_blog_posts_columns', 'add_corpname_column');

// function render_corpname_column($column, $post_id)
// {
//   if ($column !== 'corpname') {
//     return;
//   }

//   $corpname = get_admin_corpname($post_id);
//   echo $corpname !== '' ? esc_html($corpname) : '—';
// }
// add_action('manage_works_posts_custom_column', 'render_corpname_column', 10, 2);
// add_action('manage_cases_posts_custom_column', 'render_corpname_column', 10, 2);
// add_action('manage_blog_posts_custom_column', 'render_corpname_column', 10, 2);

// /**
//  * 関連記事（related-url）の検索結果と選択済みラベルに会社名を添える
//  * blogには会社名が無いためタイトルのみになる
//  */
// add_filter('acf/fields/post_object/result/name=related-url', function ($title, $post, $field, $post_id) {
//   $corpname = get_admin_corpname($post->ID);

//   return $corpname !== '' ? $title . '（' . $corpname . '）' : $title;
// }, 10, 4);
