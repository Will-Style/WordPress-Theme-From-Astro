<?php 
/**
 * 自動返信メールを送信するクラス
 * wp mail関数を利用しているため、MXレコードが違う場合はWP Mail SMTPにてSMTPの設定を行う必要がある。
 */
class SendAutoMail {

    public $from = "テスト送信者 <info@willstyle.co.jp>";

    /**
     * contactの場合の設定$_GET["sendmail"]で受け取った値で実行される
     */
    public function contact( $to, $data ){

        // メールのタイトル
        $subject = "【テスト株式会社】お問い合わせを受付いたしました。";
        $headers = [];
        // メールのFromアドレス
        $headers[] = "From: テスト送信者 <info@willstyle.co.jp>";
        
        //// 返信先
        $headers[] = 'Reply-To: テスト送信者 <okuda@willstyle.co.jp>';

        //// CCがあれば
        // $headers[] = 'Cc: copy_to_1@gmail.com';
        // $headers[] = 'Cc: copy_to_2@gmail.com';
        
        //// BCCがあれば
        // $headers[] = 'Bcc: bcc_to_1@naver.com';
        // $headers[] = 'Bcc: bcc_to_2@naver.com';

        ob_start();
        // インクルード先
        include( __FUNCTION__."/auto-mail.php");
        $body = ob_get_clean();
        
        // 本文内の変数を置換
        foreach ($data as $k => $v) {
            $body = str_replace('{$' . $k . '}', $v, $body);
        }
        // Flamingo Pluginにて保存するメソッド
        if(defined("FLAMINGO_PLUGIN")){
            // 削除するフィールドがあればunsetする
            unset($_POST['recaptcha_value']);

            $message = new Flamingo_Inbound_Message();
            $message->add( [
                "subject" => $_POST['name']. "様 よりお問い合わせ",
                "from" => $_POST['email'],
                "from_name" => $_POST['name'],
                "from_email" => $_POST['email'],
                "fields" => $_POST,
                // ※フィールドのkey名は英語のみ使用可能なため注意。
                // ※配列で指定すればフィールドを保存可能
            ] );
        }


        wp_mail ( $to , $subject, $body ,$headers );
    }

    public function example( $to, $data ){

        // メールのタイトル
        $subject = "【テスト株式会社】お問い合わせを受付いたしました。";
        $headers = [];
        // メールのFromアドレス
        $headers[] = "From: テスト送信者 <info@willstyle.co.jp>";
        
        //// 返信先
        $headers[] = 'Reply-To: テスト送信者 <okuda@willstyle.co.jp>';

        //// CCがあれば
        // $headers[] = 'Cc: copy_to_1@gmail.com';
        // $headers[] = 'Cc: copy_to_2@gmail.com';
        
        //// BCCがあれば
        // $headers[] = 'Bcc: bcc_to_1@naver.com';
        // $headers[] = 'Bcc: bcc_to_2@naver.com';

        ob_start();
        // インクルード先
        include( __FUNCTION__."/auto-mail.php");
        $body = ob_get_clean();
        
        // 本文内の変数を置換
        foreach ($data as $k => $v) {
            $body = str_replace('{$' . $k . '}', $v, $body);
        }
        // Flamingo Pluginにて保存するメソッド
        if(defined("FLAMINGO_PLUGIN")){
            // 削除するフィールドがあればunsetする
            unset($_POST['recaptcha_value']);

            $message = new Flamingo_Inbound_Message();
            $message->add( [
                "subject" => $_POST['name']. "様 よりお問い合わせ",
                "from" => $_POST['email'],
                "from_name" => $_POST['name'],
                "from_email" => $_POST['email'],
                "fields" => $_POST,
                // ※フィールドのkey名は英語のみ使用可能なため注意。
                // ※配列で指定すればフィールドを保存可能
            ] );
        }


        wp_mail ( $to , $subject, $body ,$headers );
    }
    
}