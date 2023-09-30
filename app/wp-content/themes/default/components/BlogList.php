<?php 
    $attach_id = get_post_thumbnail_id(get_the_ID());
    $img = '<img src="/assets/images/dummy.jpg" alt="" class="c-card__image">';
    if($attach_id):
        $image = wp_get_attachment_image_src($attach_id,"large");
        if(!empty($image[0])):
            $img = wp_get_attachment_image($attach_id,"large",false,["class"=>"c-card__image"]);
        endif;
    endif;
?>
<article class="c-card">
    <div class="c-card__content">
        <h3 class="c-card__title"><a href="<?php the_permalink();?>" class="c-card__title__link"><?php the_title();?></a></h3>
        <div class="c-card__cat" aria-label="カテゴリー:">
            <?php echo order_the_terms($post->ID,'blog-cat','c-card__cat__link');?>
        </div>
        <time class="c-card__time" datetime="<?php the_time('c');?>" aria-label="投稿日:"><?php the_time('Y.m.d');?></time>
        <figure class="c-card__figure"><a href="<?php the_permalink();?>" class="c-card__image__link"><?php echo $img;?></a></figure>
    </div>
</article>