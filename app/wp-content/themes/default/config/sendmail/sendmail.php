<?php

include_once(get_template_directory(). "/config/sendmail/sendAutoMail.php");
include_once(get_template_directory(). "/config/sendmail/sendAdminMail.php");



if(isset($_GET['sendmail'])){
    
    $sendmail = $_GET['sendmail'];
    $url = home_url("/") . $sendmail;

    function recaptcha_callback(){
		
        $sendmail = $_GET['sendmail'];
        $function_name = str_replace("-","_",$sendmail);
        $function_name = str_replace("/","",$function_name);
        
        $url = home_url("/") . $sendmail;
        
        setcookie('SEND_'. strtoupper($sendmail) .'_COMPLETE', 1, time() + 2000,"/");

        
        $SendAutoMail = new SendAutoMail();
        if( method_exists( $SendAutoMail ,$function_name ) ){
            $SendAutoMail->$function_name($_POST['email'],$_POST);
        }
        
        $SendAdminMail = new SendAdminMail();
        if( method_exists( $SendAdminMail , $function_name ) ){
            $SendAdminMail->$function_name($_POST);
        }

        
        wp_safe_redirect($url. "/complete/");
        exit();
	}

    //Google reCaptcha判定
	if (isset($_POST['recaptcha_value']) && !empty($_POST['recaptcha_value'])){
		$verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='. RECAPTCHA_SECRET .'&response='.$_POST['recaptcha_value']);
		$reCAPTCHA = json_decode($verifyResponse);
		if ($reCAPTCHA->success){
			recaptcha_callback();

		}else{
            wp_safe_redirect( $url ."/error/". $errors );
            exit();
		}
	}else{
        wp_safe_redirect( $url ."/error/". $errors );
        exit();
	}
}


// 保存先ディレクトリ(uploads配下に限定)を返す
function ws_formdata_dir() {
    $upload_dir = wp_upload_dir();
    return array(
        'dir' => trailingslashit( $upload_dir['basedir'] ) . 'formData/',
        'url' => trailingslashit( $upload_dir['baseurl'] ) . 'formData/',
    );
}

// APIの設定
add_action( 'rest_api_init', function () {
    // 訪問者(未ログイン)からのフォーム添付を許可する想定のため未認証で公開する。
    // 安全性はコールバック内のホワイトリスト検証・パストラバーサル対策で担保する。
    register_rest_route( 'ws/v1', '/upload-image', array(
      'methods' => 'POST',
      'callback' => 'upload_image_callback',
      'permission_callback' => '__return_true',
    ));
    register_rest_route( 'ws/v1', '/unlink-image', array(
        'methods' => 'POST',
        'callback' => 'unlink_image_callback',
        'permission_callback' => '__return_true',
    ));
});

// 画像アップロード処理
function upload_image_callback(\WP_REST_Request $req) {

    $img = $req->get_file_params()['img'] ?? null;

    // リクエスト・アップロードエラーの検証
    if ( ! $img || empty( $img['tmp_name'] ) || ! is_uploaded_file( $img['tmp_name'] ) || ! empty( $img['error'] ) ) {
        return new WP_Error( 'upload_error', 'アップロードに失敗しました。', array( 'status' => 400 ) );
    }

    // サイズ制限(10MB / フロント側のmaxsize属性と同値)
    $max_size = 10 * 1024 * 1024;
    if ( ! empty( $img['size'] ) && $img['size'] > $max_size ) {
        return new WP_Error( 'too_large', 'ファイルサイズが大きすぎます。', array( 'status' => 413 ) );
    }

    // 画像・PDF・ZIP限定のホワイトリスト検証(拡張子 + 実ファイルの中身)
    $allowed = array(
        'jpg|jpeg|jpe' => 'image/jpeg',
        'png'          => 'image/png',
        'gif'          => 'image/gif',
        'webp'         => 'image/webp',
        'pdf'          => 'application/pdf',
        'zip'          => 'application/zip',
    );
    $checked = wp_check_filetype_and_ext( $img['tmp_name'], $img['name'], $allowed );
    if ( empty( $checked['ext'] ) || empty( $checked['type'] ) ) {
        return new WP_Error( 'invalid_type', '画像ファイル(jpg/png/gif/webp)・PDF・ZIPのみアップロードできます。', array( 'status' => 400 ) );
    }
    if ( 'application/pdf' === $checked['type'] ) {
        // 先頭に%PDFシグネチャがあるか確認(拡張子偽装の防止)
        $head = (string) file_get_contents( $img['tmp_name'], false, null, 0, 5 );
        if ( 0 !== strpos( $head, '%PDF-' ) ) {
            return new WP_Error( 'invalid_pdf', '有効なPDFファイルではありません。', array( 'status' => 400 ) );
        }
    } elseif ( 'application/zip' === $checked['type'] ) {
        // 先頭にPKシグネチャ(通常/空/分割)があるか確認(拡張子偽装の防止)
        $head       = (string) file_get_contents( $img['tmp_name'], false, null, 0, 4 );
        $signatures = array( "PK\x03\x04", "PK\x05\x06", "PK\x07\x08" );
        if ( ! in_array( $head, $signatures, true ) ) {
            return new WP_Error( 'invalid_zip', '有効なZIPファイルではありません。', array( 'status' => 400 ) );
        }
    } elseif ( getimagesize( $img['tmp_name'] ) === false ) {
        // 実際に画像として読み込めるか二重チェック(拡張子偽装の防止)
        return new WP_Error( 'invalid_image', '有効な画像ファイルではありません。', array( 'status' => 400 ) );
    }

    // 安全なファイル名を生成(拡張子は検証済みの値に固定)
    $ext      = ( 'jpeg' === $checked['ext'] || 'jpe' === $checked['ext'] ) ? 'jpg' : $checked['ext'];
    $filename = date( 'Ymd-His' ) . '-' . md5( uniqid( '', true ) ) . '.' . $ext;

    // 保存先(uploads/formData 配下に限定)
    $paths = ws_formdata_dir();
    if ( ! file_exists( $paths['dir'] ) ) {
        wp_mkdir_p( $paths['dir'] );
    }
    $file_loc = $paths['dir'] . $filename;

    if ( move_uploaded_file( $img['tmp_name'], $file_loc ) ) {
        @chmod( $file_loc, 0644 );
        return array(
            'status' => true,
            'url'    => $paths['url'] . $filename,
            'name'   => sanitize_file_name( $img['name'] ),
        );
    }

    return new WP_Error( 'save_error', 'ファイルの保存に失敗しました。', array( 'status' => 500 ) );
}

// 画像削除処理
function unlink_image_callback(\WP_REST_Request $req) {

    $url   = (string) ( $req->get_params()['url'] ?? '' );
    $paths = ws_formdata_dir();

    // formData配下のURLのみ許可
    if ( '' === $url || 0 !== strpos( $url, $paths['url'] ) ) {
        return new WP_Error( 'invalid_url', '削除対象が不正です。', array( 'status' => 400 ) );
    }

    // URLからファイル名のみ抽出(basenameでパストラバーサルを除去)
    $filename = basename( substr( $url, strlen( $paths['url'] ) ) );
    $target   = $paths['dir'] . $filename;

    // realpathで保存ディレクトリ配下であることを厳密に検証(二重防御)
    $real_base   = realpath( $paths['dir'] );
    $real_target = realpath( $target );
    if ( false === $real_base || false === $real_target
        || 0 !== strpos( $real_target, trailingslashit( $real_base ) ) ) {
        return new WP_Error( 'not_found', '対象ファイルが見つかりません。', array( 'status' => 404 ) );
    }

    if ( is_file( $real_target ) ) {
        unlink( $real_target );
    }

    return array( 'status' => true );
}
