<?php
get_header(); ?>

<main id="content" class="lower">
    <div class="container">
        <header class="lower__header">
            <h1 class="lower__header__title">
                <div class="lower__header__title-ja">『<?php single_tag_title(); ?>』の記事一覧</div>
                <div class="lower__header__title-en">Tag</div>
            </h1>
        </header>
    </div>
    <div class="blog__wrapper">
        <div class="container">
        
           
            <div class="grid lg:cols-3 sm:cols-2 xl:gap-x-40 md:gap-x-30 md:gap-y-50 gap-y-30">
                
                <?php if(have_posts()):?>
                <?php while(have_posts()): the_post();?>
                    <?php include(get_template_directory().'/components/BlogList.php');?>
                <?php endwhile;?>
                <?php endif;?>
                <?php wp_reset_postdata();?>
            </div>
        </div>
    </div>
    <div class="container">
        <nav aria-label="Page navigation">
            <?php if(function_exists('wp_pagenavi')) wp_pagenavi(); ?>
        </nav>
    </div>
    <div class="container lg:py-60 py-40">
        <nav aria-label="breadcrumb">
            <?php breadcrumb();?>
        </nav>
    </div>
</main>
<?php
get_footer();
