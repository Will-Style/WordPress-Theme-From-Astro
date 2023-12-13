<?php

include_once(get_template_directory(). "/config/sendmail/sendAutoMail.php");
include_once(get_template_directory(). "/config/sendmail/sendAdminMail.php");



if(isset($_GET['sendmail'])){
    
    $sendmail = $_GET['sendmail'];
    $url = $_SERVER['HTTP_REFERER'];

    function recaptcha_callback(){
		
        $sendmail = $_GET['sendmail'];
        $function_name = str_replace("-","_",$sendmail);
        $function_name = str_replace("/","",$function_name);
        
        $url = $_SERVER['HTTP_REFERER'];
        
        setcookie('SEND_'. strtoupper($sendmail) .'_COMPLETE', 1, time() + 2000,"/");

        
        $SendAutoMail = new SendAutoMail();
        if( method_exists( $SendAutoMail ,$function_name ) ){
            $SendAutoMail->$function_name($_POST['email'],$_POST);
        }
        
        $SendAdminMail = new SendAdminMail();
        if( method_exists( $SendAdminMail , $function_name ) ){
            $SendAdminMail->$function_name($_POST);
        }

        
        header("Location: ". $url. "complete/");
        exit();
	}

    //Google reCaptcha判定
	if (isset($_POST['recaptcha_value']) && !empty($_POST['recaptcha_value'])){
		$verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret='. RECAPTCHA_SECRET .'&response='.$_POST['recaptcha_value']);
		$reCAPTCHA = json_decode($verifyResponse);
		if ($reCAPTCHA->success){
			recaptcha_callback();

		}else{
            header("Location: ". $url ."error/". $errors );
            exit();
		}
	}else{
        header("Location: ". $url ."error/". $errors );
        exit();
	}
}


// ファイル名のリネーム
function rename_file_md5($fileName) {
    $i = strrpos($fileName, '.');
    if ($i) $Exts = '.'.substr($fileName, $i + 1);
    else $Exts = '';
    $fileName = md5(time().$fileName).$Exts;
    return strtolower($fileName);
}

// APIの設定
add_action( 'rest_api_init', function () {
    register_rest_route( 'ws/v1', '/upload-image', array(
      'methods' => 'POST',
      'callback' => 'upload_image_callback',
    ));
    register_rest_route( 'ws/v1', '/unlink-image', array(
        'methods' => 'POST',
        'callback' => 'unlink_image_callback',
    ));
});

// 画像アップロード処理
function upload_image_callback(\WP_REST_Request $req) {
    
    
    $img = $req->get_file_params()['img'];
    $name = rename_file_md5($img['name']);
    $datetime =  date('Ymd-His', time());
    
    $directory_path = "wp-content/uploads/formData/";
    if(!file_exists(ABSPATH.$directory_path)){
        if(mkdir(ABSPATH.$directory_path, 0777)){
            chmod(ABSPATH.$directory_path, 0777);
        }
    }
    $url = $directory_path.$datetime."-".$name;
    $fileLoc= ABSPATH.$url;
  
    if(copy($img['tmp_name'],$fileLoc)){
        return array("status"=>true,"url"=> site_url("/").$url ,"name" =>$img['name']);
    } else {
        return array("status"=>false,"msg"=>"File Copy Error:".error_get_last());
    }
}

// 画像削除処理
function unlink_image_callback(\WP_REST_Request $req) {
    
    $url = $req->get_params()["url"];
    $url = str_replace(site_url("/"),"",$url);
    unlink(ABSPATH.$url);
  
    return true;
}
