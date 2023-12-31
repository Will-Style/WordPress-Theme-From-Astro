<?php

namespace WebpConverter\Error\Detector;

use WebpConverter\Conversion\Format\FormatFactory;
use WebpConverter\Error\Notice\PassthruExecutionNotice;
use WebpConverter\Loader\LoaderAbstract;
use WebpConverter\Loader\PassthruLoader;
use WebpConverter\PluginData;
use WebpConverter\PluginInfo;
use WebpConverter\Settings\Option\LoaderTypeOption;

/**
 * Checks for configuration errors about disabled file supports Pass Thru loader.
 */
class PassthruExecutionDetector implements DetectorInterface {

	/**
	 * @var PluginInfo
	 */
	private $plugin_info;

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * @var FormatFactory
	 */
	private $format_factory;

	public function __construct( PluginInfo $plugin_info, PluginData $plugin_data, FormatFactory $format_factory ) {
		$this->plugin_info    = $plugin_info;
		$this->plugin_data    = $plugin_data;
		$this->format_factory = $format_factory;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_error() {
		$plugin_settings = $this->plugin_data->get_plugin_settings();
		if ( $plugin_settings[ LoaderTypeOption::OPTION_NAME ] !== PassthruLoader::LOADER_TYPE ) {
			return null;
		}

		do_action( LoaderAbstract::ACTION_NAME, true, true );

		$has_error = false;
		if ( $this->if_passthru_execution_allowed() !== true ) {
			$has_error = true;
		}

		do_action( LoaderAbstract::ACTION_NAME, true );

		return ( $has_error ) ? new PassthruExecutionNotice() : null;
	}

	/**
	 * Checks if PHP file required for Passthru loader is available.
	 *
	 * @return bool Verification status.
	 */
	private function if_passthru_execution_allowed(): bool {
		$loader = new PassthruLoader( $this->plugin_info, $this->plugin_data, $this->format_factory );
		if ( $loader->is_active_loader() !== true ) {
			return true;
		}

		$url = $loader::get_loader_url() . '?nocache=1';
		$ch  = curl_init( $url );
		if ( $ch === false ) {
			return false;
		}

		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_NOBODY, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 3 );
		curl_exec( $ch );
		$code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		return ( $code === 200 );
	}
}
