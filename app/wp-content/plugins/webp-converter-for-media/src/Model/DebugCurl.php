<?php

namespace WebpConverter\Model;

/**
 * Stores information about cURL request for debugging.
 */
class DebugCurl {

	private int $response_length = 0;

	private int $response_code = 0;

	private ?string $response_content_type = null;

	private ?string $response_effective_url = null;

	private ?string $curl_error = null;

	/**
	 * @param resource|null $curl_handle   .
	 * @param string|null   $curl_response .
	 */
	public function __construct( $curl_handle, ?string $curl_response = null ) {
		if ( $curl_handle === null ) {
			return;
		}

		$this->response_length        = ( $curl_response !== null ) ? strlen( $curl_response ) : 0;
		$this->response_code          = curl_getinfo( $curl_handle, CURLINFO_HTTP_CODE );
		$this->response_content_type  = curl_getinfo( $curl_handle, CURLINFO_CONTENT_TYPE ) ?: null;
		$this->response_effective_url = curl_getinfo( $curl_handle, CURLINFO_EFFECTIVE_URL );
		$this->curl_error             = curl_error( $curl_handle ) ?: null;
	}

	public function get_length(): int {
		return ( $this->response_code === 200 ) ? $this->response_length : 0;
	}

	public function get_raw_length(): int {
		return $this->response_length;
	}

	public function get_http_code(): int {
		return $this->response_code;
	}

	public function get_content_type(): ?string {
		return ( $this->response_code === 200 ) ? $this->response_content_type : null;
	}

	public function get_raw_content_type(): ?string {
		return $this->response_content_type;
	}

	public function get_url(): ?string {
		return $this->response_effective_url;
	}

	public function get_error(): ?string {
		return $this->curl_error;
	}
}
