<?php 
    $attach_id = get_post_thumbnail_id(get_the_ID());
    $img = '<img src="/assets/images/dummy.jpg" alt="" class="c-card__image">';
    if($attach_id):
        $image = wp_get_attachment_image_src($attach_id,"card-sm");
        if(!empty($image[0])):
            $img = wp_get_attachment_image($attach_id,"card-sm",false,[
                "sizes" => get_image_sizes_attr('news-list'),
            ]);
        endif;
    endif;
?>
<article class="c-news-list" data-scroll-trigger="top">
    <a href="<?php the_permalink();?>" class="c-news-list__link">
        <figure class="c-news-list__figure" data-parallax><picture><?php echo $img;?></picture></figure>
        <div class="c-news-list__content">
            <h3 class="c-news-list__title"><?php the_title();?></h3>
            <time class="c-news-list__time" datetime="<?php the_time('c');?>" aria-label="投稿日"><?php the_time('Y.m.d');?></time>
        </div>
    </a>
</article>