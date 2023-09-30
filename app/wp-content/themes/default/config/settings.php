<?php 

/* ========================================================
フロント：ブロックエディタ用CSSの追加
=========================================================*/
add_action( 'after_setup_theme', function(){
	add_theme_support( 'editor-styles' );
	add_editor_style( get_stylesheet_directory_uri() . '/editor-style.css' );
});

add_action('admin_enqueue_scripts', function ($hook_suffix) {
	// 新規・編集投稿ページのみ読み込み
	if ('post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
		// CSSディレクトリの設定
		$uri = get_template_directory_uri() . "/editor-style.css";
		// CSSファイルの読み込み
		wp_enqueue_style("smart-style", $uri, array(), wp_get_theme()->get('Version'));
	}
});


function disable_emoji() {
    remove_action( 'wp_head', 'wp_generator');
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );    
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );    
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
    
}
add_action( 'init', 'disable_emoji' );

add_action( 'wp_enqueue_scripts', 'remove_classic_theme_style' );
function remove_classic_theme_style() {
	wp_dequeue_style( 'classic-theme-styles' );
}

// プラグインを自動更新
add_filter( 'auto_update_plugin', '__return_true' );

//テーマの自動更新を無効化
add_filter( 'auto_update_theme', '__return_false' );

//マイナーアップデートを有効化
add_filter( 'allow_major_auto_core_updates', '__return_true' );
add_filter('automatic_updates_is_vcs_checkout', '__return_false', 1 );

function remove_admin_menus() {
    
    // level10以外のユーザーの場合
    if (!current_user_can('level_10')) {
 
        global $menu;
        global $submenu;
        unset($menu[20]);       // 固定ページ
        unset($menu[25]);       // コメント
        unset($menu[60]);       // 外観
        unset($menu[65]);       // プラグイン
        unset($menu[70]);       // ユーザー
        unset($menu[75]);       // ツール
        unset($menu[80]);       // 設定
        // unset($menu["80.026"]);
        // remove_submenu_page('edit.php','edit-tags.php?taxonomy=category');
        // remove_submenu_page('edit.php','edit-tags.php?taxonomy=post_tag');
        // remove_submenu_page('edit.php?post_type=blog','edit-tags.php?taxonomy=blog-cat&amp;post_type=blog');
    }
}
add_action('admin_menu', 'remove_admin_menus');

function remove_dashboard_widget() {
    remove_action( 'welcome_panel','wp_welcome_panel' ); // ようこそ
    
    remove_meta_box( 'dashboard_site_health' , 'dashboard', 'normal'); // サイトヘルス
 	// remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' ); // 概要
 	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' ); // アクティビティ
    remove_meta_box( 'semperplugins-rss-feed' , 'dashboard', 'normal'); // SEO 最新情報
 	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' ); // クイックドラフト
 	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' ); // WordPress イベントとニュース
 	remove_meta_box( 'wp_mail_smtp_reports_widget_lite', 'dashboard', 'normal' ); // WP mail SMTP
     
} 
add_action('wp_dashboard_setup', 'remove_dashboard_widget' );

function delete_column($columns) {
    unset($columns['author']);
    unset($columns['comments']);
    return $columns;
}
add_filter( 'manage_posts_columns', 'delete_column');


/***********************************************************
* 投稿画面の項目を非表示
***********************************************************/
function remove_default_post_screen_metaboxes() {
    remove_meta_box( 'postcustom','post','normal' ); // カスタムフィールド
    remove_meta_box( 'postexcerpt','post','normal' ); // 抜粋
    remove_meta_box( 'commentstatusdiv','post','normal' ); // ディスカッション
    remove_meta_box( 'commentsdiv','post','normal' ); // コメント
    remove_meta_box( 'trackbacksdiv','post','normal' ); // トラックバック
    remove_meta_box( 'authordiv','post','normal' ); // 作成者
    remove_meta_box( 'slugdiv','post','normal' ); // スラッグ
    remove_meta_box( 'revisionsdiv','post','normal' ); // リビジョン
    remove_meta_box( 'tagsdiv-post_tag' , 'post' , 'side' ); // 投稿のタグ
}
add_action('admin_menu','remove_default_post_screen_metaboxes');

add_action( 'wp_print_styles', 'my_deregister_styles', 100 );
function my_deregister_styles() {
    wp_deregister_style( 'wp-pagenavi' );
}


if ( !is_admin() ) {
	if ( !function_exists( 'mytheme_init' ) ) {
		add_action( 'init', 'mytheme_init' );
		function mytheme_init() {
			define( 'IFRAME_REQUEST', 1 );
		}
	}
}

add_action( 'enqueue_block_editor_assets', '_deny_list_blocks' );
function _deny_list_blocks() {
	wp_enqueue_script(
		'deny-list-blocks',
		get_template_directory_uri() . '/config/deny-list-blocks.js',
		array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' )
	);
}

function _embed_style() {
    wp_enqueue_style('wp-embed-template-org', get_stylesheet_directory_uri() . '/config/new_wp-embed-template.css');
}
add_filter('embed_head', '_embed_style');

add_filter( 'lazyblock/card/frontend_allow_wrapper', '__return_false' );
add_filter( 'lazyblock/page-title/frontend_allow_wrapper', '__return_false' );
add_filter( 'lazyblock/slider/frontend_allow_wrapper', '__return_false' );
add_filter( 'lazyblock/details/frontend_allow_wrapper', '__return_false' );
add_filter( 'lazyblock/divider/frontend_allow_wrapper', '__return_false' );
add_filter( 'lazyblock/container/frontend_allow_wrapper', '__return_false' );
add_filter( 'lazyblock/container/allow_inner_blocks_wrapper', '__return_false' );
add_filter( 'lazyblock/content-wrapper/frontend_allow_wrapper', '__return_false' );
add_filter( 'lazyblock/content-wrapper/allow_inner_blocks_wrapper', '__return_false' );



function usort_add_admin_head() {
    include(get_template_directory(). "/components/Meta.php");
}
add_action('admin_head', 'usort_add_admin_head');