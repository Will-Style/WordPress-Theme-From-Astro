<?php

/**
 * Lazy Blocksの補助処理
 *
 * ブロックの定義（タイトル・コントロール）は管理画面で編集する
 *   ツール > Lazy Blocks > Tools から _lzb-export-works-blocks.json をインポートすると
 *   「本文セクション」「ギャラリー（1枚）」「ギャラリー（2枚）」が登録される
 *   定義を変更したら同じ画面からエクスポートし直し、jsonを差し替えること
 *
 * 表示テンプレートはjsonではなくテーマ側に置く
 *   blocks/lazyblock-<スラッグ>/block.php
 */


// blog__section__bodyがラッパーの役割を持つため、InnerBlocksの内側にdivを足さない
add_filter('lazyblock/blog-section/allow_inner_blocks_wrapper', '__return_false');


/**
 * ギャラリーのfigureを出力する
 * 動画が設定されていれば動画を、なければ画像を表示する
 */
function render_blog_gallery_figure($image, $video,$webm , $space = false)
{
  $class = 'blog__gallery__figure' . ($space ? ' :space' : '');
  $video_url = !empty($video['url']) ? $video['url'] : '';
  $webm_url = !empty($webm['url']) ? $webm['url'] : '';
  $video_meta = !empty($video['id']) ? wp_get_attachment_metadata($video['id']) : [];
?>
<figure class="<?php echo esc_attr($class);?>" data-scroll-trigger="top">
    <?php if($video_url): ?>
    <video <?php if(!empty($video_meta['width']) && !empty($video_meta['height'])): ?> width="<?php echo esc_attr($video_meta['width']);?>" height="<?php echo esc_attr($video_meta['height']);?>"<?php endif; ?> data-lazy-video autoplay muted loop playsinline>
        <?php if($webm_url): ?><source data-src="<?php echo esc_url($webm_url);?>" type="video/webm" /><?php endif; ?>
        <source data-src="<?php echo esc_url($video_url);?>" type="video/mp4" />
    </video>
    <?php elseif(!empty($image['id'])): ?>
    <picture><?php echo wp_get_attachment_image($image['id'], 'full', false, ['decoding' => 'async', 'loading' => 'lazy']);?></picture>
    <?php elseif(!empty($image['url'])): ?>
    <picture><img src="<?php echo esc_url($image['url']);?>" alt="" decoding="async" loading="lazy"></picture>
    <?php endif; ?>
</figure>
<?php
}
