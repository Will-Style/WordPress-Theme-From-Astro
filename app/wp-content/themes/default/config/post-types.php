<?php

/**
 * アイキャッチを有効化
 */
add_theme_support('post-thumbnails', ['post', 'works', 'cases', 'blog']);



// カスタム投稿タイプ作成
function create_post_type_blog()
{
  $blog = [
    'title',
    'editor',
    'thumbnail',
    'revisions',
    'author',
  ];

  // add post type
  register_post_type(
    'blog',
    array(
      'label' => 'ブログ',
      'public' => true,
      'has_archive' => true,
      'menu_position' => 4,
      'supports' => $blog,
      'show_in_rest' => true,
      'custom-fields' => true,
    )
  );

  // add taxonomy
  register_taxonomy(
    'blog-cat',
    'blog',
    array(
      'label' => 'ブログカテゴリー',
      'labels' => array(
        'all_items' => 'カテゴリー一覧',
        'add_new_item' => '新規カテゴリーを追加'
      ),
      'hierarchical' => true,
      'show_in_rest' => true,
    )
  );


}

add_action('init', 'create_post_type_blog');




/**
 * 一覧上部のタクソノミー絞り込みセレクトボックスを出力する
 *
 * optionのvalueは必ずslugにすること。
 * WP_Query::parse_tax_query()はタクソノミーのクエリ変数をslugとして解決するため、
 * wp_dropdown_categories()の既定値であるterm_idをvalueにすると常に0件になる
 *
 * @param string $taxonomy     タクソノミースラッグ
 * @param string $extra_option </select>の直前に差し込む独自option（任意）
 */
function render_admin_taxonomy_dropdown($taxonomy, $extra_option = '') {
    $tax_obj = get_taxonomy($taxonomy);
    if (!$tax_obj) {
        return;
    }

    $selected = isset($_GET[$taxonomy]) ? sanitize_text_field(wp_unslash($_GET[$taxonomy])) : '';

    $dropdown = wp_dropdown_categories(array(
        'show_option_all' => 'すべての' . $tax_obj->labels->singular_name,
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'orderby'         => 'name',
        'selected'        => $selected,
        'hide_empty'      => 0,
        'value_field'     => 'slug',
        'echo'            => 0,
    ));

    if (!$dropdown) {
        return;
    }

    // wp_dropdown_categories()は完成した<select>を返すため、独自optionは文字列で差し込む
    if ($extra_option) {
        $dropdown = str_replace('</select>', $extra_option . '</select>', $dropdown);
    }

    echo $dropdown;
}

// 1. 管理画面の一覧に絞り込みセレクトボックスを追加
function add_no_category_filter_to_custom_post() {
    global $post_type;

    if ($post_type == 'blog') {
        $selected = isset($_GET['blog-cat']) ? sanitize_text_field(wp_unslash($_GET['blog-cat'])) : '';
        $no_category = '<option value="no_category"' . selected($selected, 'no_category', false) . '>-- カテゴリー未設定 --</option>';

        render_admin_taxonomy_dropdown('blog-cat', $no_category);
    }
    // if ($post_type == 'works') {
    //     render_admin_taxonomy_dropdown('works-cat');
    //     render_admin_taxonomy_dropdown('industry');
    // }
}
add_action('restrict_manage_posts', 'add_no_category_filter_to_custom_post');

// 2. 「カテゴリー未設定」が選ばれたときの絞り込み処理
function filter_no_category_custom_posts($query) {
    global $pagenow, $post_type;

    if (is_admin() && $pagenow == 'edit.php' && $post_type == 'blog') {
        $taxonomy = 'blog-cat';
        if (isset($_GET[$taxonomy]) && $_GET[$taxonomy] === 'no_category') {
            // クエリ変数を残すと、存在しないslugのterm条件（0=1）が別に組まれて必ず0件になる
            $query->set($taxonomy, '');

            // どのタームにも属していない条件を指定
            $query->set('tax_query', array(
                array(
                    'taxonomy' => $taxonomy,
                    'operator' => 'NOT EXISTS',
                ),
            ));
        }
    }
}
add_action('parse_query', 'filter_no_category_custom_posts');