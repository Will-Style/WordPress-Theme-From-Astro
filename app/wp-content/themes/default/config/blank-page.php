<?php 

function is_blank(){
    // 別窓で表示させるページ
    $blank_pages = [
        'pp',
    ];
    $r = false;
    foreach ($blank_pages as $value) { 
        if(is_page($value)){
            $r = true;
        }
    }
    return $r;
}