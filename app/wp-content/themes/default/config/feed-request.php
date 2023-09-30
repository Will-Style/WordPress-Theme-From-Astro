<?php 
/**
 * カスタム投稿タイプをFeedに含める
 */
function _feed_request($vars) {
    if ( isset( $vars['feed'] ) && !isset( $vars['post_type'] ) ) {
        $vars['post_type'] = array(
            'post',
            'blog',
        );
    }
    return $vars;
}
add_filter( 'request', '_feed_request' );