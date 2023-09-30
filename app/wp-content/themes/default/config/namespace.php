<?php 

function get_namespace(){
    // ローディングを表示させないページ
    $disables_pages = [
        'policy',
        'entry',
        'entry/complete',
        'entry/error',
    ];
    
    $namespace = "lower";
    if(is_home()){
        $namespace = "home";
    }
    if(is_404()){
        $namespace = "notfound";
    }
    if(is_archive() || is_single()){
        $namespace = "posts";
    }
    // ローディングを表示させないページ
    foreach ($disables_pages as $value) { 
        if(is_page($value)){
            $namespace = "disabled";
        }
    }
    
    return $namespace;
}