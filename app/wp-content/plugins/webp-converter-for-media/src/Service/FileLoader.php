<?php

namespace WebpConverter\Service;

use WebpConverter\Model\DebugCurl;

/**
 * Returns size of image downloaded based on server path or URL.
 */
class FileLoader {

	const GLOBAL_LOGS_VARIABLE = 'webpc_logs';

	private string $test_version;

	public function __construct() {
		$this->reset_test_version();
	}

	public function reset_test_version(): void {
		$this->test_version = uniqid();
	}

	/**
	 * @param string      $url             URL of image.
	 * @param bool        $set_webp_header Whether to send headers to confirm that browser supports WebP?
	 * @param string|null $debug_context   .
	 *
	 * @return DebugCurl
	 */
	public function get_file_by_url( string $url, bool $set_webp_header = true, ?string $debug_context = null ): DebugCurl {
		$request_url     = $this->get_curl_url( $url, $this->test_version );
		$request_headers = $this->get_curl_headers( $set_webp_header );
		$connect         = $this->get_curl_connection( $request_url, $request_headers );
		if ( $connect === null ) {
			return new DebugCurl( null );
		}

		$response   = curl_exec( $connect );
		$debug_file = new DebugCurl( $connect, ( is_string( $response ) ? $response : null ) );

		if ( $debug_context !== null ) {
			$this->log_request( $debug_context, $request_url, $set_webp_header, $debug_file );
		}

		return $debug_file;
	}

	/**
	 * @param string $path Server path of file.
	 *
	 * @return int Size of file.
	 */
	public function get_file_size_by_path( string $path ): int {
		return ( file_exists( $path ) ) ? ( filesize( $path ) ?: 0 ) : 0;
	}

	/**
	 * @param string      $url       URL of image.
	 * @param string|null $ver_param Additional GET param.
	 *
	 * @return string
	 */
	private function get_curl_url( string $url, ?string $ver_param = null ): string {
		$image_url = $url;
		if ( $ver_param !== null ) {
			$image_url = add_query_arg( 'ver', $ver_param, $image_url );
		}
		if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'wccp-pro/preventer-index.php' ) ) {
			$image_url = add_query_arg( 'wccp_pro_watermark_pass', '', $image_url );
		}

		return apply_filters( 'webpc_debug_image_url', $image_url );
	}

	/**
	 * @param bool $set_webp_header Whether to send headers to confirm that browser supports WebP?
	 *
	 * @return string[]
	 */
	private function get_curl_headers( bool $set_webp_header ): array {
		$headers = ( $set_webp_header )
			? [ 'Accept: image/webp,image/*' ]
			: [ 'Accept: image/*' ];

		foreach ( wp_get_nocache_headers() as $header_key => $header_value ) {
			$headers[] = sprintf( '%s: %s', $header_key, $header_value );
		}
		return $headers;
	}

	/**
	 * @param string   $url     .
	 * @param string[] $headers .
	 *
	 * @return resource|null
	 */
	private function get_curl_connection( string $url, array $headers ) {
		if ( ! function_exists( 'curl_init' ) ) {
			return null;
		}

		$ch = curl_init( $url );
		if ( $ch === false ) {
			return null;
		}

		if ( isset( $_SERVER['PHP_AUTH_USER'] ) && isset( $_SERVER['PHP_AUTH_PW'] ) ) {
			curl_setopt( $ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
			curl_setopt( $ch, CURLOPT_USERPWD, sprintf( '%1$s:%2$s', $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
		curl_setopt( $ch, CURLOPT_FRESH_CONNECT, true );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 10 );
		curl_setopt( $ch, CURLINFO_HEADER_OUT, true );
		curl_setopt( $ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_REFERER, PathsGenerator::get_site_url() );

		return $ch;
	}

	/**
	 * @param string    $debug_context       .
	 * @param string    $request_url         .
	 * @param bool      $request_accept_webp .
	 * @param DebugCurl $debug_file          .
	 */
	private function log_request(
		string $debug_context,
		string $request_url,
		bool $request_accept_webp,
		DebugCurl $debug_file
	): void {
		if ( ! isset( $GLOBALS[ self::GLOBAL_LOGS_VARIABLE ] ) ) {
			$GLOBALS[ self::GLOBAL_LOGS_VARIABLE ] = [];
		}

		$GLOBALS[ self::GLOBAL_LOGS_VARIABLE ][] = [
			'context'               => $debug_context,
			'request_url'           => $request_url,
			'request_accept_webp'   => $request_accept_webp,
			'response_http_code'    => $debug_file->get_http_code(),
			'response_length'       => $debug_file->get_raw_length(),
			'response_url'          => $debug_file->get_url(),
			'response_content_type' => $debug_file->get_raw_content_type(),
			'curl_error'            => $debug_file->get_error(),
		];
	}
}
