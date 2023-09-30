
wp.domReady(function () {
    // オフにしたいブロックを指定する
    
    wp.blocks.unregisterBlockType( 'tadv/classic-paragraph');
    wp.blocks.unregisterBlockType( 'core/freeform');
    wp.blocks.unregisterBlockType( 'core/html');
    wp.blocks.unregisterBlockType( 'core/preformatted');
    wp.blocks.unregisterBlockType( 'core/pullquote');
    wp.blocks.unregisterBlockType( 'core/buttons');
    wp.blocks.unregisterBlockType( 'core/text-columns');
    
    wp.blocks.unregisterBlockType( 'core/more');
    wp.blocks.unregisterBlockType( 'core/nextpage');
    wp.blocks.unregisterBlockType( 'core/separator');
    wp.blocks.unregisterBlockType( 'core/shortcode');
    wp.blocks.unregisterBlockType( 'core/archives');
    wp.blocks.unregisterBlockType( 'core/categories');
    wp.blocks.unregisterBlockType( 'core/latest-comments');
    wp.blocks.unregisterBlockType( 'core/latest-posts');
    wp.blocks.unregisterBlockType( 'core/calendar');
    wp.blocks.unregisterBlockType( 'core/rss');
    wp.blocks.unregisterBlockType( 'core/search');
    wp.blocks.unregisterBlockType( 'core/tag-cloud');
    wp.blocks.unregisterBlockType( 'core/verse' ); 
    wp.blocks.unregisterBlockType( 'core/cover' ); 
    wp.blocks.unregisterBlockType( 'core/page-list');
    wp.blocks.unregisterBlockType( 'core/page-list-item');
    wp.blocks.unregisterBlockType( 'core/social-link');
    wp.blocks.unregisterBlockType( 'core/social-links');
    wp.blocks.unregisterBlockType( 'core/navigation');
    wp.blocks.unregisterBlockType( 'core/navigation-link');
    wp.blocks.unregisterBlockType( 'core/navigation-submenu');
    wp.blocks.unregisterBlockType( 'core/site-logo');
    wp.blocks.unregisterBlockType( 'core/site-title');
    wp.blocks.unregisterBlockType( 'core/site-tagline');
    wp.blocks.unregisterBlockType( 'core/template-part');
    wp.blocks.unregisterBlockType( 'core/avatar');
    wp.blocks.unregisterBlockType( 'core/post-title');
    wp.blocks.unregisterBlockType( 'core/post-excerpt');
    wp.blocks.unregisterBlockType( 'core/post-featured-image');
    wp.blocks.unregisterBlockType( 'core/post-content');
    wp.blocks.unregisterBlockType( 'core/post-author');
    wp.blocks.unregisterBlockType( 'core/post-author-name');
    wp.blocks.unregisterBlockType( 'core/post-date');
    wp.blocks.unregisterBlockType( 'core/post-terms');
    wp.blocks.unregisterBlockType( 'core/post-navigation-link');
    wp.blocks.unregisterBlockType( 'core/post-template');
    wp.blocks.unregisterBlockType( 'core/query');
    
    wp.blocks.unregisterBlockType( 'core/query-pagination');
    wp.blocks.unregisterBlockType( 'core/query-pagination-next');
    wp.blocks.unregisterBlockType( 'core/query-pagination-numbers');
    wp.blocks.unregisterBlockType( 'core/query-pagination-previous');
    wp.blocks.unregisterBlockType( 'core/query-no-results');
    wp.blocks.unregisterBlockType( 'core/read-more');
    wp.blocks.unregisterBlockType( 'core/comments');
    wp.blocks.unregisterBlockType( 'core/comment-author-name');
    wp.blocks.unregisterBlockType( 'core/comment-content');
    wp.blocks.unregisterBlockType( 'core/comment-date');
    wp.blocks.unregisterBlockType( 'core/comment-edit-link');
    wp.blocks.unregisterBlockType( 'core/comment-reply-link');
    wp.blocks.unregisterBlockType( 'core/comment-template');
    wp.blocks.unregisterBlockType( 'core/comments-title');
    wp.blocks.unregisterBlockType( 'core/comments-pagination');
    wp.blocks.unregisterBlockType( 'core/comments-pagination-next');
    wp.blocks.unregisterBlockType( 'core/comments-pagination-numbers');
    wp.blocks.unregisterBlockType( 'core/comments-pagination-previous');

    wp.blocks.unregisterBlockType( 'core/post-comments-form');
    wp.blocks.unregisterBlockType( 'core/home-link');
    wp.blocks.unregisterBlockType( 'core/loginout');
    wp.blocks.unregisterBlockType( 'core/term-description');
    wp.blocks.unregisterBlockType( 'core/query-title');
    wp.blocks.unregisterBlockType( 'core/post-author-biography');
    wp.blocks.unregisterBlockType( 'core/legacy-widget');
    wp.blocks.unregisterBlockType( 'core/widget-g');
    // 特定の埋め込みブロックの場合
    wp.blocks.unregisterBlockVariation('core/embed', 'wordpress');
    wp.blocks.unregisterBlockVariation('core/embed', 'soundcloud');
    wp.blocks.unregisterBlockVariation('core/embed', 'flickr');
    wp.blocks.unregisterBlockVariation('core/embed', 'tiktok');
    wp.blocks.unregisterBlockVariation('core/embed', 'vimeo');
    wp.blocks.unregisterBlockVariation('core/embed', 'animoto');
    wp.blocks.unregisterBlockVariation('core/embed', 'cloudup');
    wp.blocks.unregisterBlockVariation('core/embed', 'collegehumor');
    wp.blocks.unregisterBlockVariation('core/embed', 'dailymotion');
    wp.blocks.unregisterBlockVariation('core/embed', 'funnyordie');
    wp.blocks.unregisterBlockVariation('core/embed', 'hulu');
    wp.blocks.unregisterBlockVariation('core/embed', 'imgur');
    wp.blocks.unregisterBlockVariation('core/embed', 'issuu');
    wp.blocks.unregisterBlockVariation('core/embed', 'kickstarter');
    wp.blocks.unregisterBlockVariation('core/embed', 'meetup-com');
    wp.blocks.unregisterBlockVariation('core/embed', 'mixcloud');
    wp.blocks.unregisterBlockVariation('core/embed', 'photobucket');
    wp.blocks.unregisterBlockVariation('core/embed', 'polldaddy');
    wp.blocks.unregisterBlockVariation('core/embed', 'reddit');
    wp.blocks.unregisterBlockVariation('core/embed', 'reverbnation');
    wp.blocks.unregisterBlockVariation('core/embed', 'screencast');
    wp.blocks.unregisterBlockVariation('core/embed', 'scribd');
    wp.blocks.unregisterBlockVariation('core/embed', 'slideshare');
    wp.blocks.unregisterBlockVariation('core/embed', 'smugmug');
    wp.blocks.unregisterBlockVariation('core/embed', 'speaker');
    wp.blocks.unregisterBlockVariation('core/embed', 'ted');
    wp.blocks.unregisterBlockVariation('core/embed', 'tumblr');
    wp.blocks.unregisterBlockVariation('core/embed', 'videopress');
    wp.blocks.unregisterBlockVariation('core/embed', 'wordpress-tv');
    wp.blocks.unregisterBlockVariation('core/embed', "crowdsignal");
    wp.blocks.unregisterBlockVariation('core/embed', 'speaker-deck');
    wp.blocks.unregisterBlockVariation('core/embed', 'amazon-kindle');
    wp.blocks.unregisterBlockVariation('core/embed', 'pocketcasts')
    wp.blocks.unregisterBlockVariation('core/embed', "wolfram-cloud");
    wp.blocks.unregisterBlockVariation('core/embed', "pinterest");
  

//   // ブロックタイプ確認方法
//   console.log(wp.blocks.getBlockTypes());

//   // 埋め込みブロック確認方法
//   console.log(wp.blocks.getBlockVariations('core/embed'));
});