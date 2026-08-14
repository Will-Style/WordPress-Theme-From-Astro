<?php
/**
 * 前後記事ナビ
 * get_previous_post / get_next_post は現在の投稿タイプで引くため、
 * blog・works・cases のいずれからも同じ書き方で読み込める
 */
$prev_post = get_previous_post();
$next_post = get_next_post();

/**
 * ナビのカード画像を返す
 * 画像はすべてアイキャッチに統一している（一覧・ヒーローも同じ）
 */
if (!function_exists('get_pagenavi_image')) {
  function get_pagenavi_image($post_id)
  {
    $attach_id = get_post_thumbnail_id($post_id);

    if ($attach_id) {
      $image = wp_get_attachment_image_src($attach_id, "thumb-sm");
      if (!empty($image[0])) {
        return wp_get_attachment_image($attach_id, "thumb-sm", false, [
          "class" => "c-card__image",
          "sizes" => get_image_sizes_attr('pagenavi'),
        ]);
      }
    }

    return '<img src="/assets/images/dummy.jpg" alt="" class="c-card__image">';
  }
}
?>
<?php if($prev_post || $next_post): ?>
<div class="container">
    <div class="blog__pagenavi">
        <div class="blog__pagenavi__previous">
        <?php if($prev_post): ?>
            <h4 class="blog__pagenavi__title">Previous</h4>
            <article class="blog__pagenavi__card">
                <a href="<?php echo get_permalink($prev_post->ID); ?>" class="blog__pagenavi__card__link">
                    <figure class="blog__pagenavi__card__figure" data-scroll-trigger="mask"><?php echo get_pagenavi_image($prev_post->ID); ?></figure>
                    <div class="blog__pagenavi__card__content">
                        <h3 class="blog__pagenavi__card__title"><?php echo esc_html($prev_post->post_title);?></h3>
                    </div>
                </a>
            </article>
        <?php endif;?>
        </div>
        <div class="blog__pagenavi__next">
            <?php if($next_post): ?>
            <h4 class="blog__pagenavi__title">Next</h4>
            <article class="blog__pagenavi__card">
                <a href="<?php echo get_permalink($next_post->ID); ?>" class="blog__pagenavi__card__link">
                    <figure class="blog__pagenavi__card__figure" data-scroll-trigger='mask'><?php echo get_pagenavi_image($next_post->ID); ?></figure>
                    <div class="blog__pagenavi__card__content">
                        <h3 class="blog__pagenavi__card__title"><?php echo esc_html($next_post->post_title);?></h3>
                    </div>
                </a>
            </article>
            <?php endif;?>
        </div>
    </div>
</div>
<?php endif; ?>
