<?php
get_header(); ?>

    <main id="content" class="lower">
        <section class="lower__hero">

            <div class="container">
                <div class="lower__hero-body" data-translate-support>
                    <h1 class="lower__title"><span data-scroll-trigger="text">News</span></h1>
                    <p class="lower__lead"><span data-scroll-trigger="text" data-delay=".2">お知らせ</span></p>
                    <span class="lower__title alternative">News</span>
                </div>
            </div>
        </section>
        <div class="blog__wrapper pt-60">
            <div class="container">
                <div class="grid">
                    <div class="xl:col-9 xl:start-3">
                        
                        <?php if(have_posts()):?>
                        <?php while(have_posts()): the_post();?>
                            <?php include(get_template_directory().'/components/NewsList.php');?>
                        <?php endwhile;?>
                        <?php endif;?>
                        <?php wp_reset_postdata();?>

                    </div>
                </div>

                <div class="pt-100 pb-40">
                    <?php if(function_exists('wp_pagenavi')) wp_pagenavi(); ?>
                </div>
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
