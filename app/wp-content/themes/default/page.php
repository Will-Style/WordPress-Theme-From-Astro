<?php
$uri = $_SERVER['REQUEST_URI'];

$uri = preg_replace('/\?.+$/', '', $uri);
$slug = str_replace( '/', '-' ,trim( $uri ,'/'));
//
$slugWithSlash = trim( $uri ,'/');

// ページ送りのある場合の対応
$slug = preg_replace('/\-page\-.+$/', '', $slug);


// スラッグ名のファイルが有れば include
if(file_exists( get_template_directory(). '/pages/'. $slugWithSlash .'/index.php') ):
    include( get_template_directory(). '/pages/'. $slugWithSlash .'/index.php' );

elseif(file_exists( get_template_directory(). '/pages/'. $slugWithSlash .'.php') ):
    include( get_template_directory(). '/pages/'. $slugWithSlash .'.php' );

elseif(file_exists( get_template_directory(). '/pages/'. $slug .'.php') ):
    include( get_template_directory(). '/pages/'. $slug .'.php' );

else:
    
    get_header();
    ?>
    <main id="content" class="lower">
    <?php
        $first = true;
        if(have_posts()):
            while(have_posts()):
                the_post();
                if($first) :
                    $first = false;
                    remove_filter('the_content','wpautop');
                    the_content();
                    add_filter('the_content','wpautop');
                else:
                    the_excerpt();
                endif;
            endwhile;
        endif;
    ?>
    </main>
    <?php 
    get_footer();
endif;