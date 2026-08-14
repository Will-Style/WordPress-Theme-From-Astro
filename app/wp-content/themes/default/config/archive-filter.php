<?php


/**
 * blogのアーカイブだけ1ページあたりの件数を変える
 * 一覧が4カラムのため、12件だと最終行が欠けにくい
 */
function set_blog_posts_per_page($query)
{
  if (is_admin() || !$query->is_main_query()) {
    return;
  }

  if ($query->is_post_type_archive('blog') || $query->is_tax('blog-cat')) {
    $query->set('posts_per_page', 16);
  }
}

add_action('pre_get_posts', 'set_blog_posts_per_page');
