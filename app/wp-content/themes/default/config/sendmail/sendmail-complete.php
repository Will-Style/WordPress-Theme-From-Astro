<?php 

function complete_pages_redirect() {

    // 完了ページのリダイレクト設定
    $form_slugs = [
        "contact",
        // フォームの分だけここを増やす
        "example",
    ];

    foreach ($form_slugs as $key => $slug) {

        if(is_page( $slug. "/complete")){
            $cookie_key = "SEND_". strtoupper($slug) ."_COMPLETE";
            if( !array_key_exists($cookie_key,$_COOKIE) ) {
                
                header("Location: ". home_url("/") . $slug ."/");
                exit();
            }
            
            setcookie($cookie_key,"",time() - 1000, '/');
            unset($_COOKIE[$cookie_key]);
        }
    }
}
add_action('send_headers', "complete_pages_redirect" );