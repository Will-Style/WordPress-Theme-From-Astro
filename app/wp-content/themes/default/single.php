<?php
get_header(); ?>

<main id="content" class="lower blog blog-single">
    
    <div class="blog__wrapper">
        <div class="container">
            <div class="blog__content pb-60">
                
                <section class="blog-single__hero">

                    <div class="blog-single__hero-body">
                        <h1 class="blog-single__hero__title"><span><?php the_title();?></span></h1>
                    </div>

                    <dl class="blog-single__details">
                        <div class="blog-single__details__col">
                            <dt class="blog-single__details__dt">Publish date</dt>
                            <dd class="blog-single__details__dd"><time datetime="<?php the_time('c');?>"><?php the_time('Y.m.d');?></time></dd>
                        </div>
                        
                    </dl>
                </section>
                <div class="blog__body"><?php the_content();?></div>
            </div>

            <footer class="blog-single__footer">

                <div class="grid ">
                    
                    <div class="blog__share">
                        <h4 class="blog__share__title">この記事をシェア</h4>
                        <div class="blog__share__buttons">
                            <button type="button" class="blog__share__button blog__share__button--copy" data-copy-url>
                                <svg aria-label="リンクをコピー" width="15" height="20" viewBox="0 0 15 20" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.86 6.00975H10.9135C10.5198 6.00975 10.2003 6.3378 10.2003 6.7431C10.2003 7.14839 10.5198 7.47644 10.9135 7.47644H12.8605C13.2537 7.47644 13.5736 7.80547 13.5736 8.20979V17.8C13.5736 18.2043 13.2537 18.5333 12.8605 18.5333H2.13953C1.74633 18.5333 1.42635 18.2043 1.42635 17.8V8.20979C1.42635 7.80547 1.74633 7.47644 2.13953 7.47644H4.0865C4.48065 7.47644 4.79968 7.14839 4.79968 6.7431C4.79968 6.3378 4.48065 6.00975 4.0865 6.00975H2.13953C0.959935 6.00975 0 6.99683 0 8.20979V17.8C0 19.0129 0.959935 20 2.13953 20H12.8605C14.0401 20 15 19.0129 15 17.8V8.20979C14.9995 6.99635 14.0401 6.00975 12.86 6.00975ZM4.84057 4.52938L6.81036 2.50388V12.1586C6.81036 12.5634 7.12939 12.8919 7.52353 12.8919C7.91768 12.8919 8.23671 12.5634 8.23671 12.1586V2.50388L10.2065 4.52938C10.3458 4.67262 10.5284 4.744 10.711 4.744C10.8935 4.744 11.0761 4.67262 11.2154 4.52938C11.494 4.24337 11.494 3.77843 11.2154 3.49243L8.02846 0.21487C7.74937 -0.0716233 7.29817 -0.0716233 7.01956 0.21487L3.83213 3.49243C3.55352 3.77843 3.55352 4.24337 3.83213 4.52938C4.11075 4.81587 4.56195 4.81587 4.84057 4.52938Z" fill="cuurentColor"/></svg>                                            
                            </button>
                            <a href="http://twitter.com/share?url=<?php the_permalink();?>&amp;text=<?php the_title();?>" class="blog__share__button blog__share__button--tw" data-share-tw>
                                <svg role="img" aria-label="Twitterでシェアする" width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18.5067 0.0477449C18.2524 0.324874 17.9991 0.602856 17.7437 0.87892C15.7274 3.0577 13.7107 5.23606 11.6942 7.41453C11.663 7.4482 11.6317 7.48186 11.6019 7.51415C14.2108 11.0024 16.8182 14.4885 19.4393 17.9929C19.3802 17.995 19.3401 17.9977 19.3 17.9977C18.3128 17.9979 17.3255 17.9979 16.3382 17.9979C15.5037 17.9979 14.6691 17.9964 13.8346 18C13.7462 18.0004 13.6947 17.9713 13.6427 17.9018C11.9373 15.6175 10.2297 13.3348 8.52236 11.0519C8.49519 11.0156 8.46717 10.98 8.43468 10.9378C8.40271 10.9715 8.37576 10.9992 8.34954 11.0275C6.22201 13.3259 4.09437 15.6241 1.96811 17.9236C1.91803 17.9777 1.86753 17.9999 1.79369 17.9995C1.31071 17.9964 0.827736 17.998 0.344757 17.9977C0.310556 17.9977 0.276354 17.9949 0.221802 17.9924C0.257602 17.9509 0.280935 17.9221 0.305974 17.895C1.49259 16.6129 2.67942 15.3308 3.86625 14.0487C5.09889 12.7172 6.33165 11.3856 7.56418 10.054C7.69769 9.90976 7.68895 9.93874 7.57516 9.78648C5.2829 6.72186 2.99075 3.65725 0.699027 0.592308C0.559024 0.404998 0.422217 0.215237 0.284025 0.026542C0.286796 0.0204688 0.287648 0.014076 0.286476 0.00747008C0.321743 0.00565878 0.357117 0.00224927 0.392384 0.00224927C2.22105 0.00203617 4.04972 0.00203617 5.87829 0.00203617H5.98356C6.23746 0.341176 6.48848 0.676267 6.7393 1.01157C7.99996 2.69715 9.26042 4.38283 10.5211 6.06841C10.6104 6.18774 10.6993 6.3074 10.7892 6.42641C10.845 6.50046 10.8521 6.50131 10.9165 6.43195C11.3124 6.00512 11.7079 5.57776 12.1034 5.1504C13.6663 3.46205 15.2293 1.77402 16.7905 0.0842906C16.8456 0.0247307 16.9 -0.000627509 16.9818 1.17749e-05C17.454 0.00384748 17.9263 0.00182308 18.3985 0.00203617C18.4339 0.00203617 18.4694 0.00427366 18.5048 0.00544568C18.5054 0.0195099 18.5062 0.0335742 18.5068 0.0476384L18.5067 0.0477449ZM2.73461 1.2399C6.61219 6.42449 10.4745 11.5888 14.3342 16.7496C14.6326 16.7702 16.861 16.7604 16.9459 16.7374C16.9227 16.7049 16.9009 16.6733 16.8779 16.6425C16.255 15.8094 15.6322 14.9763 15.0091 14.1432C11.8131 9.87023 8.61687 5.59726 5.42237 1.32312C5.37741 1.26302 5.33266 1.23788 5.2568 1.2382C4.45769 1.24097 3.65859 1.2399 2.85949 1.2399H2.73472H2.73461Z" fill="cuurentColor" />
                                </svg>                                                                                                                              
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink();?>" class="blog__share__button blog__share__button--fb" data-share-fb>
                                <svg role="img" aria-label="Facebookでシェアする" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M19.2218 10.0581C19.2218 14.8289 15.7476 18.7831 11.2062 19.5V12.8209H13.4198L13.8409 10.0581H11.2062V8.26517C11.2062 7.50912 11.5743 6.77248 12.7544 6.77248H13.9523V4.4203C13.9523 4.4203 12.865 4.23362 11.8255 4.23362C9.65575 4.23362 8.23743 5.55681 8.23743 7.9523V10.0581H5.82532V12.8209H8.23743V19.5C3.69598 18.7831 0.221802 14.8289 0.221802 10.0581C0.221802 4.77947 4.47528 0.5 9.7218 0.5C14.9683 0.5 19.2218 4.77947 19.2218 10.0581Z" fill="cuurentColor" />
                                </svg>                                                                                
                            </a>
                        </div>
                    </div>
                </div>

            </footer>
        </div>
    </div>
    <div class="container">
        <div class="blog__pagenavi">
            <?php
                $prev_post = get_previous_post();
                $next_post = get_next_post();
            ?>
            <div class="blog__pagenavi__previous">
            <?php if($prev_post): ?>   
                <?php 
                    $attach_id = get_post_thumbnail_id($prev_post->ID);
                    $img = '<img src="/assets/images/dummy.jpg" alt="" class="c-card__image">';
                    if($attach_id):
                        $image = wp_get_attachment_image_src($attach_id,"thumb-sm");
                        if(!empty($image[0])):
                            $img = wp_get_attachment_image($attach_id,"thumb-sm",false,[
                                "class" => "c-card__image",
                                "sizes" => get_image_sizes_attr('pagenavi'),
                            ]);
                        endif;
                    endif;
                ?> 
                <h4 class="blog__pagenavi__title">Previous</h4>
                <article class="blog__pagenavi__card">
                    <a href="<?php echo get_permalink($prev_post->ID); ?>" class="blog__pagenavi__card__link">
                        <figure class="blog__pagenavi__card__figure" data-scroll-trigger="mask"><?php echo $img; ?></figure>
                        <div class="blog__pagenavi__card__content">
                            <h3 class="blog__pagenavi__card__title"><?php echo esc_html($prev_post->post_title);?></h3>
                            <time class="blog__pagenavi__card__time" aria-label="投稿日:"><?php echo date("Y.m.d",strtotime($prev_post->post_date));?></time>
                        </div>
                    </a>
                </article>
            <?php endif;?>
            </div>
            <div class="blog__pagenavi__next">
                <?php if($next_post): ?>
                <?php 
                    $attach_id = get_post_thumbnail_id($next_post->ID);
                    $img = '<img src="/assets/images/dummy.jpg" alt="" class="c-card__image">';
                    if($attach_id):
                        $image = wp_get_attachment_image_src($attach_id,"thumb-sm");
                        if(!empty($image[0])):
                            $img = wp_get_attachment_image($attach_id,"thumb-sm",false,[
                                "class" => "c-card__image",
                                "sizes" => get_image_sizes_attr('pagenavi'),
                            ]);
                        endif;
                    endif;
                ?>
                
                <h4 class="blog__pagenavi__title">Next</h4>
                <article class="blog__pagenavi__card">
                    <a href="<?php echo get_permalink($next_post->ID); ?>" class="blog__pagenavi__card__link">
                        <figure class="blog__pagenavi__card__figure" data-scroll-trigger='mask'><?php echo $img; ?></figure>
                        <div class="blog__pagenavi__card__content">
                            <h3 class="blog__pagenavi__card__title"><?php echo esc_html($next_post->post_title);?></h3>
                            <time class="blog__pagenavi__card__time" aria-label="投稿日:"><?php echo date("Y.m.d",strtotime($next_post->post_date));?></time>
                        </div>
                    </a>
                </article>
                <?php endif;?>
            </div>
        </div>
    </div>
    <div class="container lg:py-60 py-40">
        <nav aria-label="breadcrumb">
            <?php breadcrumb(1);?>
        </nav>
    </div>
</main>
<?php
get_footer();
