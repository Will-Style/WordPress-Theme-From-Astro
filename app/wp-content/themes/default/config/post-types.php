<?php 

/**
 * アイキャッチを有効化
 */
add_theme_support( 'post-thumbnails', [ 'post','blog'] );

// // カスタム投稿タイプ作成
function create_post_type_blog() {
    $blog = [
      'title',
      'editor',
      'thumbnail',
      'revisions',
    ];
  
    // add post type
    register_post_type( 'blog',
      array(
        'label' => 'ブログ',
        'public' => true,
        'has_archive' => true,
        'menu_position' => 4,
        'supports' => $blog,
        'show_in_rest' => true,
      )
    );
  
    // add taxonomy
    register_taxonomy(
      'blog-cat',
      'blog',
      array(
        'label' => 'カテゴリー',
        'labels' => array(
          'all_items' => 'カテゴリー一覧',
          'add_new_item' => '新規カテゴリーを追加'
        ),
        'hierarchical' => true,
        'show_in_rest' => true,
      )
    );
  }
  
add_action( 'init', 'create_post_type_blog' );


// 管理画面のカスタム投稿一覧にカテゴリー(ターム)を表示
function add_custom_column( $defaults ) {
    $defaults['blog-cat'] = 'カテゴリー'; // 『area』はタクソノミースラッグ(複数設定可)
    return $defaults;
  }
  add_filter('manage_blog_posts_columns', 'add_custom_column'); // ここの『blog』はカスタム投稿タイプスラッグ
  function add_custom_column_id($column_name, $id) {
    $terms = get_the_terms($id, $column_name);
    if ( $terms && ! is_wp_error( $terms ) ){
      $blog_links = array(); // ここの『blog』は変えなくてもOKだが、カスタム投稿タイプスラッグがおすすめ
      foreach ( $terms as $term ) {
        $blog_links[] = $term->name;
      }
      echo join( ", ", $blog_links );
    }
  }
add_action('manage_blog_posts_custom_column', 'add_custom_column_id', 10, 2); // ここの『blog』はカスタム投稿タイプスラッグ


function add_post_taxonomy_restrict_filter() {
    global $post_type;
    if ( 'blog' == $post_type ) {
        ?>
        <select name="blog-cat">
            <option value="">カテゴリー指定なし</option>
            <?php
            $terms = get_terms('blog-cat');
            foreach ($terms as $term) { ?>
                <option value="<?php echo $term->slug; ?>"><?php echo $term->name; ?></option>
            <?php } ?>
        </select>
        <?php
    }
}
add_action( 'restrict_manage_posts', 'add_post_taxonomy_restrict_filter' );
