<?php
get_header(); ?>

<main id="content" class="lower">
    
    <div class="blog__wrapper pt-140 lg:pt-200">
        <div class="container">
            <div class="grid">
                <div class="lg:col-9 lg:order-last justify-content-center">
                    <div class="blog__content">
                        <header class="blog__header" data-not-top>
                            <h1 class="blog__title">
                                <?php the_title();?>
                            </h1>
                            <div class="grid gap-y-10 mt-30">
                                <dl class="blog__time md:col-4">
                                    <dt aria-label="投稿日：" class="blog__time__dt">Publish : </dt>
                                    <dd class="blog__time__dd"><?php the_time('Y.m.d');?></dd>
                                </dl>
                                <dl class="blog__cat md:col-8">
                                    <dt class="blog__cat__dt">Category : </dt>
                                    <dd class="blog__cat__dd">
                                        <?php echo order_the_terms($post->ID,'blog-cat','blog__cat__link');?>
                                    </dd>
                                </dl>
                            </div>
                        </header>
                        <div class="blog__body">
                            <?php the_content();?>
                        </div>
    
                       
    
                        <div class="blog__share">
                            <h4 class="blog__share__title">Share</h4>
                            <div class="blog__share__buttons">
                                
                                <a href="http://twitter.com/share?url=<?php the_permalink();?>&amp;text=<?php the_title();?>" class="blog__share__button blog__share__button--tw" data-share-tw>
                                    <!-- <svg role="img" aria-label="Twitterでシェアする" width="22" height="18" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21.2218 2.51254C20.4409 2.85385 19.6087 3.08008 18.7412 3.18992C19.6337 2.659 20.3149 1.82469 20.6351 0.819077C19.803 1.31338 18.8842 1.66254 17.9051 1.85738C17.115 1.01915 15.9889 0.5 14.7604 0.5C12.3769 0.5 10.458 2.42754 10.458 4.79054C10.458 5.13054 10.4869 5.45746 10.5577 5.76869C6.97855 5.59477 3.81149 3.88562 1.68393 1.282C1.31249 1.92408 1.09461 2.659 1.09461 3.45015C1.09461 4.93569 1.86243 6.25254 3.00693 7.01492C2.31524 7.00185 1.63668 6.80177 1.0618 6.48662C1.0618 6.49969 1.0618 6.51669 1.0618 6.53369C1.0618 8.61815 2.55411 10.3495 4.51105 10.7484C4.16061 10.8438 3.77868 10.8896 3.3823 10.8896C3.10668 10.8896 2.82843 10.8739 2.56724 10.8164C3.12505 12.5151 4.70793 13.7639 6.59005 13.8045C5.1253 14.9461 3.26549 15.6339 1.25211 15.6339C0.899052 15.6339 0.560427 15.6182 0.221802 15.5751C2.12886 16.8004 4.38899 17.5 6.8263 17.5C14.7486 17.5 19.0798 10.9615 19.0798 5.294C19.0798 5.10438 19.0732 4.92131 19.0641 4.73954C19.9185 4.13538 20.6364 3.38085 21.2218 2.51254Z" />
                                    </svg>  -->   
                                    <svg role="img" aria-label="Twitterでシェアする" width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M18.5067 0.0477449C18.2524 0.324874 17.9991 0.602856 17.7437 0.87892C15.7274 3.0577 13.7107 5.23606 11.6942 7.41453C11.663 7.4482 11.6317 7.48186 11.6019 7.51415C14.2108 11.0024 16.8182 14.4885 19.4393 17.9929C19.3802 17.995 19.3401 17.9977 19.3 17.9977C18.3128 17.9979 17.3255 17.9979 16.3382 17.9979C15.5037 17.9979 14.6691 17.9964 13.8346 18C13.7462 18.0004 13.6947 17.9713 13.6427 17.9018C11.9373 15.6175 10.2297 13.3348 8.52236 11.0519C8.49519 11.0156 8.46717 10.98 8.43468 10.9378C8.40271 10.9715 8.37576 10.9992 8.34954 11.0275C6.22201 13.3259 4.09437 15.6241 1.96811 17.9236C1.91803 17.9777 1.86753 17.9999 1.79369 17.9995C1.31071 17.9964 0.827736 17.998 0.344757 17.9977C0.310556 17.9977 0.276354 17.9949 0.221802 17.9924C0.257602 17.9509 0.280935 17.9221 0.305974 17.895C1.49259 16.6129 2.67942 15.3308 3.86625 14.0487C5.09889 12.7172 6.33165 11.3856 7.56418 10.054C7.69769 9.90976 7.68895 9.93874 7.57516 9.78648C5.2829 6.72186 2.99075 3.65725 0.699027 0.592308C0.559024 0.404998 0.422217 0.215237 0.284025 0.026542C0.286796 0.0204688 0.287648 0.014076 0.286476 0.00747008C0.321743 0.00565878 0.357117 0.00224927 0.392384 0.00224927C2.22105 0.00203617 4.04972 0.00203617 5.87829 0.00203617H5.98356C6.23746 0.341176 6.48848 0.676267 6.7393 1.01157C7.99996 2.69715 9.26042 4.38283 10.5211 6.06841C10.6104 6.18774 10.6993 6.3074 10.7892 6.42641C10.845 6.50046 10.8521 6.50131 10.9165 6.43195C11.3124 6.00512 11.7079 5.57776 12.1034 5.1504C13.6663 3.46205 15.2293 1.77402 16.7905 0.0842906C16.8456 0.0247307 16.9 -0.000627509 16.9818 1.17749e-05C17.454 0.00384748 17.9263 0.00182308 18.3985 0.00203617C18.4339 0.00203617 18.4694 0.00427366 18.5048 0.00544568C18.5054 0.0195099 18.5062 0.0335742 18.5068 0.0476384L18.5067 0.0477449ZM2.73461 1.2399C6.61219 6.42449 10.4745 11.5888 14.3342 16.7496C14.6326 16.7702 16.861 16.7604 16.9459 16.7374C16.9227 16.7049 16.9009 16.6733 16.8779 16.6425C16.255 15.8094 15.6322 14.9763 15.0091 14.1432C11.8131 9.87023 8.61687 5.59726 5.42237 1.32312C5.37741 1.26302 5.33266 1.23788 5.2568 1.2382C4.45769 1.24097 3.65859 1.2399 2.85949 1.2399H2.73472H2.73461Z" />
                                    </svg>                                                                                                                              
                                </a>
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php the_permalink();?>" class="blog__share__button blog__share__button--fb" data-share-fb>
                                    <svg role="img" aria-label="Facebookでシェアする" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M19.2218 10.0581C19.2218 14.8289 15.7476 18.7831 11.2062 19.5V12.8209H13.4198L13.8409 10.0581H11.2062V8.26517C11.2062 7.50912 11.5743 6.77248 12.7544 6.77248H13.9523V4.4203C13.9523 4.4203 12.865 4.23362 11.8255 4.23362C9.65575 4.23362 8.23743 5.55681 8.23743 7.9523V10.0581H5.82532V12.8209H8.23743V19.5C3.69598 18.7831 0.221802 14.8289 0.221802 10.0581C0.221802 4.77947 4.47528 0.5 9.7218 0.5C14.9683 0.5 19.2218 4.77947 19.2218 10.0581Z" />
                                    </svg>                                                                                
                                </a>
                            </div>
                        </div>
    
                    </div>
                </div>

                <div class="lg:col-3 lg:order-first">
                    <?php include( get_template_directory().'/components/Sidebar.php');?>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        
        <div class="blog__pagenavi">
            <?php
                $prev_post = get_previous_post();
                $next_post = get_next_post();
            ?>
            <?php if($prev_post): ?>   
            <?php 
                $attach_id = get_post_thumbnail_id($prev_post->ID);
                $img = '<img src="/assets/images/dummy.jpg" alt="" class="blog__pagenavi__card__image">';
                if($attach_id):
                    $image = wp_get_attachment_image_src($attach_id,"large");
                    if(!empty($image[0])):
                        $img = wp_get_attachment_image($attach_id,"large",false,["class"=>"blog__pagenavi__card__image"]);
                    endif;
                endif;
            ?> 
            <div class="blog__pagenavi__previous">
                <h4 class="blog__pagenavi__title">前の記事</h4>
                <article class="blog__pagenavi__card--blog :sm">
                    <a href="<?php echo get_permalink($prev_post->ID); ?>" class="blog__pagenavi__card__link">
                        <div class="blog__pagenavi__card__content">
                            <h3 class="blog__pagenavi__card__title"><?php echo esc_html($prev_post->post_title);?></h3>
                            <time class="blog__pagenavi__card__time" aria-label="投稿日:"><?php echo date("Y.m.d",strtotime($prev_post->post_date));?></time>
                        </div>
                        <figure class="blog__pagenavi__card__figure" data-scroll-trigger='mask'><?php echo $img; ?></figure>
                    </a>
                </article>
            </div>
            <?php endif;?>
            <?php if($next_post): ?>
            <?php 
                $attach_id = get_post_thumbnail_id($next_post->ID);
                $img = '<img src="/assets/images/dummy.jpg" alt="" class="blog__pagenavi__card__image">';
                if($attach_id):
                    $image = wp_get_attachment_image_src($attach_id,"large");
                    if(!empty($image[0])):
                        $img = wp_get_attachment_image($attach_id,"large",false,["class"=>"blog__pagenavi__card__image"]);
                    endif;
                endif;
            ?>
            <div class="blog__pagenavi__next">
                <h4 class="blog__pagenavi__title">次の記事</h4>
                <article class="blog__pagenavi__card--blog :sm">
                    <a href="<?php echo get_permalink($next_post->ID); ?>" class="blog__pagenavi__card__link">
                        <div class="blog__pagenavi__card__content">
                            <h3 class="blog__pagenavi__card__title"><?php echo esc_html($next_post->post_title);?></h3>
                            <time class="blog__pagenavi__card__time" aria-label="投稿日:"><?php echo date("Y.m.d",strtotime($next_post->post_date));?></time>
                        </div>
                        <figure class="blog__pagenavi__card__figure" data-scroll-trigger='mask'><?php echo $img; ?></figure>
                    </a>
                </article>
            </div>
            <?php endif;?>
        </div>
    </div>
    <div class="container lg:py-60 py-40">
        <nav aria-label="breadcrumb">
            <?php breadcrumb();?>
        </nav>
    </div>

</main>
<?php
get_footer();
