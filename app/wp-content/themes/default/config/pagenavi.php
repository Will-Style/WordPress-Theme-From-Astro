<?php 
// ページナビのHTMLを変更する
add_filter( 'wp_pagenavi', function($html){
    $html = trim(preg_replace('/<\/?div([^>]*)?>/u', '', $html));
    $html = preg_replace('/(<a[^>]*>[^<]*<\/a>)/u', '<li class="c-pagination__item">$1</li>', $html);
    $html = preg_replace_callback('/<span([^>]*?)>[^<]*<\/span>/u', function($matches){
        $current = '';
        if( false !== strpos($matches[1], 'current') ){
            $current = 'aria-current="page"';
        }
        return "<li class=\"c-pagination__item\"{$current}>{$matches[0]}</li>";
    }, $html);
    
    return <<<HTML
<div aria-label="Page navigation">
    <ul class="c-pagination">{$html}</ul>
</div>
HTML;
}, 10, 2 );