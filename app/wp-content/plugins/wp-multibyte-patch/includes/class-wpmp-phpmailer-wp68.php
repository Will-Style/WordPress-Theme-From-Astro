<?php

class WPMP_PHPMailer_WP68 extends WP_PHPMailer {
	public function encodeHeader( $str, $position = 'text' ) {
		if( 'text' === $position && ( 0 === stripos( $str , '=?UTF-8?' ) || 0 === stripos( $str , '=?ISO-2022-JP?' ) ) ) {
			return $str;
		}

		return parent::encodeHeader( $str, $position );
	}
}
