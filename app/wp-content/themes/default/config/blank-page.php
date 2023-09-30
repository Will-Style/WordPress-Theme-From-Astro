<?php 

function is_blank(){
    // 別窓で表示させるページ
    $blank_pages = [
        'policy',
        'entry',
        'entry/complete',
        'entry/error',
    ];
    $r = false;
    foreach ($blank_pages as $value) { 
        if(is_page($value)){
            $r = true;
        }
    }
    return $r;
}