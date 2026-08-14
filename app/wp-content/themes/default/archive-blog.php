<?php
get_header(); ?>
<main id="content" class="lower">
    <header class="lower__header">
        <div class="container">
            <h2 class="lower__header__title">
                <div class="lower__header__title-ja">ブログ</div>
                <div class="lower__header__title-en">Blog</div>
            </h2>
        </div>
    </header>
    <div class="blog__wrapper" data-not-top>
        <div class="container">
            <div class="grid">
                <div class="lg:col-9 lg:order-last">
                    <div class="grid gap-y-50 xl:gap-30 xl:gap-y-60">
                        <?php if(have_posts()):?>
                        <?php while(have_posts()): the_post();?>
                            <div class="lg:col-4 sm:col-6">
                                <?php include(get_template_directory().'/components/BlogList.php');?>
                            </div>
                        <?php endwhile;?>
                        <?php endif;?>
                        <?php wp_reset_postdata();?>
                    </div>
                </div>
                <div class="lg:col-3 lg:order-first">
                    <?php include( get_template_directory().'/components/Sidebar.php');?>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        
        <nav aria-label="Page navigation">
            <?php if(function_exists('wp_pagenavi')) wp_pagenavi(); ?>
        </nav>
    </div>
    <div class="container lg:py-60 py-40">
        
        <div class="lower__breadcrumb">
            <nav aria-label="breadcrumb">
                <?php breadcrumb();?>
            </nav>
        </div>
    </div>
</main>
<?php
get_footer();
