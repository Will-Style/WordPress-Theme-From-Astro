<article class="c-news-list" data-scroll-trigger="top">
    <a href="<?php the_permalink();?>" class="c-news-list__link grid gap-5">
        <time class="c-news-list__time sm:col-3 lg:col-2" datetime="<?php the_time('c');?>" aria-label="投稿日"><?php the_time('Y.m.d');?></time>
        <h3 class="c-news-list__title sm:col-9 lg:col-8"><?php the_title();?></h3>
    </a>
</article>