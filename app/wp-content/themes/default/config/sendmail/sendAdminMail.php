<?php 
/**
 * 管理者宛メールを送信するクラス
 * wp mail関数を利用しているため、MXレコードが違う場合はWP Mail SMTPにてSMTPの設定を行う必要がある。
 */
class SendAdminMail {

    public $from = "テスト送信者 <info@willstyle.co.jp>";


    /**
     * contactの場合の設定$_GET["sendmail"]で受け取った値で実行される
     */
    public function contact( $data ){

        // 管理者宛メールの送付先
        $to = "okuda@willstyle.co.jp";
        // 管理者宛メールのタイトル
        $subject = "【テスト送信】お問い合わせがありました。";
        $headers = [];

        // FromはWP SMTPの設定で強制にチェックが入っていればこの設定は意味がない        
        $headers[] = "From: {$this->from}";
        // 返信先の設定
        // $headers[] = 'Reply-To: Reply Name <replyname@example.jp>';

        //// CCがあれば
        // $headers[] = 'Cc: account@willstyle.co.jp';
        // $headers[] = 'Cc: copy_to_2@gmail.com';
        
        //// BCCがあれば
        // $headers[] = 'Bcc: info@willstyle.co.jp';
        // $headers[] = 'Bcc: bcc_to_2@naver.com';

        ob_start();
        include( __FUNCTION__."/admin-mail.php");
        $body = ob_get_clean();
        
        foreach ($data as $k => $v) {
            $body = str_replace('{$' . $k . '}', $v, $body);
        }


        wp_mail ( $to , $subject, $body ,$headers );
    }

    public function example( $data ){

        // 管理者宛メールの送付先
        $to = "okuda@willstyle.co.jp";
        // 管理者宛メールのタイトル
        $subject = "【テスト送信】お問い合わせがありました。";
        $headers = [];

        // FromはWP SMTPの設定で強制にチェックが入っていればこの設定は意味がない        
        $headers[] = "From: {$this->from}";
        // 返信先の設定
        // $headers[] = 'Reply-To: Reply Name <replyname@example.jp>';

        //// CCがあれば
        // $headers[] = 'Cc: account@willstyle.co.jp';
        // $headers[] = 'Cc: copy_to_2@gmail.com';
        
        //// BCCがあれば
        // $headers[] = 'Bcc: info@willstyle.co.jp';
        // $headers[] = 'Bcc: bcc_to_2@naver.com';

        ob_start();
        include( __FUNCTION__."/admin-mail.php");
        $body = ob_get_clean();
        
        foreach ($data as $k => $v) {
            $body = str_replace('{$' . $k . '}', $v, $body);
        }


        wp_mail ( $to , $subject, $body ,$headers );
    }
    
}