<?php
namespace Updraftplus\All_In_One_Wp_Security_And_Firewall\Wizard\Onboarding;

defined( 'ABSPATH' ) || die();

use Updraftplus\All_In_One_Wp_Security_And_Firewall\Wizard\Installer\Installer;
use Updraftplus\All_In_One_Wp_Security_And_Firewall\Wizard\RestResponse\RestResponse;

/**
 * The onboarding class enqueues the react app and handles the REST API requests.
 * A trait is used to add plugin specific functionality.
 * The class itself is as much as possible independent of the plugin, so it can be used in other plugins with only little changes.
 *
 * There are three scenarios where the onboarding is active:
 * - Free plugin: no license activation, and on the finish page, an upsell to premium is shown.
 * - Pro plugin, first time onboarding: license activation is required, and on the finish page, some confirmation of the activation of additional features is shown.
 * - Pro plugin, onboarding already completed in free: only license activation, possibly pro feature configuration, and no plugins installation, no email signup.
 */
class Onboarding {

    private $steps;
    private $onboarding_path;
    private $onboarding_url;
    private $is_all_plugins_installed = false;
	public $version;
	public $prefix;
	public $plugin_name = '';
    public $privacy_statement_url;
    public $privacy_url_label = '';
	public $forgot_password_url = 'https://teamupdraft.com/my-account/lost-password/';
	public $caller_slug;
	public $capability;
	public $support_url;
	public $faqs_url = '';
	public $documentation_url = '';
	public $upgrade_url;
    public $mailing_list;
	public $mailing_list_endpoint;
	public $page_prefix;
	public $languages_dir;
	public $text_domain;
	public $logo_path;
	public $is_pro = false;
    public $reload_settings_page_on_finish = false;
    public $udmupdater_nonce = 'udmupdater-ajax-nonce';
    public $udmupdater_muid = 2;
	public $udmupdater_slug  = '';
	public $udmupdater_mothership = 'https://teamupdraft.com/plugin-info/';
    public $exit_wizard_text;

	/**
	 * Initialize hooks and filters
	 */
	public function init(): void {
		if ( ! self::is_compatible() ) {
			return;
		}

		$this->onboarding_path = __DIR__;
		$this->onboarding_url  = plugin_dir_url( __FILE__ );

        if (empty($this->privacy_url_label)) {
            $this->privacy_url_label = __( 'Privacy Statement', 'all-in-one-wp-security-and-firewall'  );
        }

        $this->maybe_flush_rewrite_rules();

		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
		add_action( 'wp_ajax_' . $this->prefix . '_onboarding_rest_api_fallback', [ $this, 'rest_api_fallback' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_enqueue_onboarding' ] );
		add_action( 'admin_footer', [ $this, 'add_root_html' ] );

	}

    /**
     * Flush rewrite rules on first onboarding initialization
     * As we're registering the rest route conditionally, we need to flush the rewrite rules once.
     */
    private function maybe_flush_rewrite_rules(): void {
        $flushed_option = $this->prefix . '_onboarding_flushed_rewrite_rules';

        if ( ! get_site_option( $flushed_option ) ) {
            flush_rewrite_rules();
            update_site_option( $flushed_option, time() );
        }
    }

	/**
	 * Maybe load the plugin wizard, to load on every page of the caller plugin
	 *
	 * @return void
	 */
	public function maybe_enqueue_onboarding(): void {
		$screen = \get_current_screen();
		if ( stripos( $screen->id, $this->page_prefix ) !== false) {
			$this->enqueue_onboarding_scripts();
		}
	}

	/**
	 * Add values and defaults to fields in steps
	 *
	 * @param array $steps array of onboarding steps.
	 * @return array<int, array{
	 *     id: string,
	 *     title: string,
	 *     subtitle?: string,
	 *     button?: array{id: string, label: string, icon?: string},
	 *     fields?: array<int, array<string, mixed>>
	 * }>
	 */
	private function add_fields_data_to_steps( array $steps ): array {
		foreach ( $steps as $step_index => $step ) {
			if ( isset( $step['fields'] ) && is_array( $step['fields'] ) ) {
				foreach ( $step['fields'] as $field_index => $field ) {
					// update values and defaults based on plugin specific functions.
					// using prefixed hook.
                    // phpcs:ignore
					$field = apply_filters( $this->prefix . '_onboarding_field', $field, $step['id'] );
					if ( $field['type'] === 'email' ) {
                        $current_user = wp_get_current_user();
                        $current_user_email = $current_user->user_email;
						$field['default'] = $current_user_email;
						$field['value']   = $current_user_email;
					}
					if ( $field['id'] === 'plugins' ) {
						$field['options'] = $this->get_recommended_plugins();
						$field['value']   = $this->get_recommended_plugins( true );
                        if ($this->is_all_plugins_installed){
                            $field['label'] = '';
                            if (isset($step['title_conditional']) && !empty($step['title_conditional'])) {
                                if (isset($step['title_conditional']['all_installed']) && !empty($step['title_conditional']['all_installed'])) {
                                    $steps[$step_index]['title'] = $steps[$step_index]['title_conditional']['all_installed'];
                                }
                                if (isset($step['subtitle_conditional']['all_installed']) && !empty($step['subtitle_conditional']['all_installed'])) {
                                    $steps[$step_index]['subtitle'] = $steps[$step_index]['subtitle_conditional']['all_installed'];
                                }
                            }
                            $steps[ $step_index ]['button']['label'] = __('Continue', 'all-in-one-wp-security-and-firewall');
                        }
					}
					$steps[ $step_index ]['fields'][ $field_index ] = $field;
				}
			}
		}
		return $steps;
	}

	/**
	 * Conditionally drop steps
	 *
	 * @param array $steps array of onboarding steps.
	 * @return array<int, array{
	 *      id: string,
	 *      type: string,
     *      icon?: string,
	 *      title: string,
	 *      subtitle?: string,
	 *      button?: array{id: string, label: string, icon?: string},
	 *      fields?: array<int, array<string, mixed>>,
	 *      solutions?: array<int, string>,
	 *      bullets?: array<int, string>,
     *      intro_bullets?: array{title: string, desc: string, icon?: string},
	 *      documentation?: string,
	 *  }>
	 */
	private function conditionally_drop_steps( array $steps ): array {
		$is_pro_with_onboarding_free_completed = $this->is_pro_with_onboarding_free_completed();
		foreach ( $steps as $step_index => $step ) {
			// if this is the pro plugin onboarding,  and user has completed the onboarding in the free plugin, we can skip first_run_only steps.
			$first_run_only = isset( $step['first_run_only'] ) && (bool) $step['first_run_only'];
			if ( $is_pro_with_onboarding_free_completed && $first_run_only ) {
				unset( $steps[ $step_index ] );
				continue;
			}

			if ( $step['id'] === 'license' ) {
				// using prefixed hook.
                // phpcs:ignore
                $license_is_valid = (bool) apply_filters( $this->prefix . '_license_is_valid', false );
				if ( $license_is_valid || ! $this->is_pro ) {
					unset( $steps[ $step_index ] );
					continue;
				}
			}

			if ( $is_pro_with_onboarding_free_completed ) {
				if ( isset( $step['title_upgrade'] ) ) {
					$steps[ $step_index ]['title'] = $step['title_upgrade'];
				}
				if ( isset( $step['subtitle_upgrade'] ) ) {
					$steps[ $step_index ]['subtitle'] = $step['subtitle_upgrade'];
				}
			}
		}
		// reset keys.
		return array_values( $steps );
	}

	/**
	 * Extract the used fields from the onboarding steps, so react can filter the applicable fields.
	 *
	 * @param array $steps array of onboarding steps.
	 * @return array<int, array{
	 *       id: string,
	 *       title: string,
	 *       subtitle?: string,
	 *       button?: array{id: string, label: string, icon?: string},
	 *       fields?: array<int, array<string, mixed>>
	 *   }>
	 */
	private function extract_fields_from_steps( array $steps ): array {
		$fields = [];
		foreach ( $steps as $step ) {
			if ( isset( $step['fields'] ) && is_array( $step['fields'] ) ) {
				foreach ( $step['fields'] as $index => $field ) {
					if ( isset( $field['id'] ) ) {
						$fields[] = $field;
					}
				}
			}
		}
		return $fields;
	}

	/**
	 * Get the fields from a specific step.
	 *
	 * @param string $step The step ID to extract fields from.
	 * @return array<int, array<string, mixed>> List of fields (each field is an assoc array).
	 */
	private function extract_fields_from_step( string $step ): array {
		$step = $this->get_step_by_id( $this->sanitize_step_id( $step ) );
		if ( empty( $step ) ) {
			return [];
		}
		return ! empty( $step['fields'] ) ? $step['fields'] : [];
	}

	/**
	 * Sanitize the step ID to ensure it exists in the steps array.
	 */
	private function sanitize_step_id( string $step_id ): string {
		$steps = $this->get_steps();
		foreach ( $steps as $step ) {
			if ( isset( $step['id'] ) && $step['id'] === $step_id ) {
				return $step_id;
			}
		}
		return '';
	}

	/**
	 * Check if the current environment is compatible with the onboarding app.
	 */
	private static function is_compatible(): bool {
		if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
			return false;
		}
		// check the WordPress version.
		global $wp_version;
		if ( version_compare( $wp_version, '6.2', '<' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if the onboarding is active
	 */
	public static function is_onboarding_active( string $prefix, string $caller_slug ): bool {
		if ( ! self::is_compatible() ) {
			return false;
		}

		$skipped   = (bool) get_site_option( $prefix . '_skipped_onboarding' );
		$started   = (bool) get_site_option( $prefix . '_start_onboarding' );
		$completed = (bool) get_site_option( $prefix . '_completed_onboarding' );

		$current_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// Don't Reopen the wizard if the user skipped the wizard.
		if ($skipped || $completed) {
            delete_site_option( $prefix . '_skipped_onboarding' );
            delete_site_option( $prefix . '_start_onboarding' );
            delete_site_option( $prefix . '_completed_onboarding' );
            delete_site_option( $prefix . '_onboarding_flushed_rewrite_rules' );
			return false;
		}

		// If onboarding is started or in progress or being called via REST
		return $started
			|| strpos( $current_uri, $prefix . '/v1/onboarding/do_action/' ) !== false
			|| strpos( $current_uri, $prefix . '_onboarding_rest_api_fallback' ) !== false;
	}

	/**
	 * Add root HTML element for the onboarding app
	 */
	public function add_root_html(): void {
		echo '<div id="teamupdraft-onboarding"></div>';
	}
	/**
	 * Register REST API routes
	 */
	public function register_rest_routes(): void {
		register_rest_route(
			$this->prefix . '/v1/onboarding',
			'do_action/(?P<action>[a-z\_\-]+)',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'handle_rest_request' ],
				'permission_callback' => [ $this, 'has_permission' ],
			]
		);
	}

	/**
	 * Check if user has required capability
	 */
	public function has_permission(): bool {
		return current_user_can( $this->capability );
	}

	/**
	 * Handle REST API requests
	 */
	public function handle_rest_request( \WP_REST_Request $request ): \WP_REST_Response {
		if ( ! $this->has_permission() ) {
			return $this->response( false, [], 'You do not have permission to do this.', 403 );
		}
		$action = sanitize_text_field( $request->get_param( 'action' ) );
		$data   = $request->get_json_params();
		if ( ! wp_verify_nonce( $data['nonce'], $this->prefix . '_nonce' ) ) {
			return $this->response( false, [], 'Nonce verification failed', 403 );
		}
		return $this->handle_onboarding_action( $action, $data );
	}

	/**
	 * Handle AJAX fallback requests, when the REST API is not available
	 */
	public function rest_api_fallback(): void {
		if ( ! $this->has_permission() ) {
			wp_send_json_error( 'Unauthorized', 403 );
		}
		$data = json_decode( file_get_contents( 'php://input' ), true );
		$data = $data['data'] ?? [];
		if ( ! wp_verify_nonce( $data['nonce'], $this->prefix . '_nonce' ) ) {
			$response          = new RestResponse();
			$response->message = 'Nonce verification failed';
			wp_send_json( $response );
			exit;
		}

        /**
         * Determine action — prefer JSON body, fallback to GET
         * Sanitized and unslashed
         */
        if ( isset( $data['path'] ) ) {
            $action = sanitize_title( wp_unslash( $data['path'] ) );
        } else {
            $action = isset($_GET['rest_action']) ? sanitize_text_field( wp_unslash( $_GET['rest_action'] ) ) : '';
        }
		preg_match( '/do_action[\/|\-]([a-z\_\-]+)$/', $action, $matches );
		if ( isset( $matches[1] ) ) {
			$action = $matches[1];
		}

		$response = $this->handle_onboarding_action( $action, $data );
		wp_send_json( $response );
		exit;
	}

	/**
	 * Standardized response format
	 */
	protected function response( bool $success = false, array $data = [], string $message = '', int $code = 200 ): \WP_REST_Response {
		if ( ob_get_length() ) {
			ob_clean();
		}

		return new \WP_REST_Response(
			[
				'success'         => $success,
				'message'         => $message,
				'data'            => $data,
				// can be used to check if the response in react actually contains this array.
				'request_success' => true,
			],
			$code
		);
	}

	/**
	 * Get step by id
	 *
	 * @return ?array{
	 *        id: string,
	 *        title: string,
	 *        subtitle?: string,
	 *        button?: array{id: string, label: string, icon?: string},
	 *        fields?: array<int, array<string, mixed>>
	 *    }
	 */
	private function get_step_by_id( string $id ): ?array {
		$steps = $this->get_steps();
		foreach ( $steps as $step ) {
			if ( isset( $step['id'] ) && $step['id'] === $id ) {
				return $step;
			}
		}
		return null;
	}

	/**
	 * Handle onboarding actions
	 *
	 * @param string $action The onboarding action to handle.
	 * @param array  $data   The data associated with the action.
	 */
	private function handle_onboarding_action( string $action, array $data ): \WP_REST_Response {
		$response = $this->response( false );
		switch ( $action ) {
            case 'user_skipped_wizard':
                update_site_option($this->prefix . '_skipped_onboarding', true );
                $message = __('User skipped the wizard', 'all-in-one-wp-security-and-firewall');
                $response = $this->response( true, [], $message);
                break;
            case 'user_completed_wizard':
                update_site_option($this->prefix . '_completed_onboarding', true );
                $message = __('User Completed the wizard', 'all-in-one-wp-security-and-firewall');
                $response = $this->response( true, [], $message);
                break;
			case 'activate_license':
				// using prefixed hook.
                // phpcs:ignore
				$license_data = apply_filters( $this->prefix . '_license_activation', [], $data );
				$response     = $this->response( $license_data['success'], [], $license_data['message'] );
				break;
			case 'update_settings':
				// Get current step fields, so we only update these fields.
				$step_fields = isset( $data['step'] ) ? $this->extract_fields_from_step( $data['step'] ) : [];
				if ( ! empty( $step_fields ) ) {
					// sanitized in save functions.
					// using prefixed hook.
                    // phpcs:ignore
					do_action( $this->prefix . '_onboarding_update_options', $data['settings'], $step_fields );
				}
				$response = $this->response( true );
				break;
			case 'download':
				if ( isset( $data['plugin'] ) ) {
					$installer   = new Installer( $this->caller_slug, $data['plugin'] );
                    // Avoid re-downloading if already downloaded/installed or activated.
                    if ( $installer->plugin_is_activated( $data['plugin'] ) ) {
                        // Already active: nothing to do.
                        $response = $this->response( true, [ 'next_action' => 'installed' ] );
                    } elseif ( $installer->plugin_is_downloaded( $data['plugin'] ) ) {
                        // Already downloaded: next step is activation.
                        $response = $this->response( true, [ 'next_action' => 'activate' ] );
                    } else {
                        $success     = $installer->download_plugin();
                        $next_action = $success ? 'activate' : 'installed';
                        $response    = $this->response( (bool) $success, [ 'next_action' => $next_action ] );
                    }
                }
				break;
			case 'activate':
				if ( isset( $data['plugin'] ) ) {
					$installer = new Installer( $this->caller_slug, $data['plugin'] );
                    // Avoid re-activating if already active.
                    if ( $installer->plugin_is_activated( $data['plugin'] ) ) {
                        $response = $this->response( true, [ 'next_action' => 'installed' ] );
                    } else {
                        $success  = $installer->activate_plugin();
                        $response = $this->response( (bool) $success, [ 'next_action' => 'installed' ] );
                    }
                }
				break;

			case 'update_email':
				$step_fields = isset( $data['step'] ) ? $this->extract_fields_from_step( $data['step'] ) : [];
				if ( isset( $data['email'] ) && is_email( $data['email'] ) ) {
					$email = sanitize_email( $data['email'] );
					if ( ! empty( $email ) ) {
						$reporting_email_field_name   = '';
						$mailinglist_email_field_name = '';
						foreach ( $step_fields as $field ) {
							if ( isset( $field['type'] ) && $field['type'] === 'email' ) {
								$reporting_email_field_name = $field['id'] ?? '';
							}
							if ( isset( $field['type'] ) && $field['type'] === 'checkbox' ) {
								$mailinglist_email_field_name = $field['id'] ?? '';
							}
						}

						if ( ! empty( $reporting_email_field_name ) ) {
							// using prefixed hook.
                            // phpcs:ignore
							do_action( $this->prefix . '_onboarding_update_single_option', $reporting_email_field_name, $email );
						}
						if ( ! empty( $mailinglist_email_field_name ) ) {
							$include_tips = isset( $data['tips_tricks'] ) && (bool) $data['tips_tricks'];
							// using prefixed hook.
                            // phpcs:ignore

							do_action( $this->prefix . '_onboarding_update_single_option', 'tips_tricks_mailinglist', $email );

							if ( $include_tips ) {
								$this->signup_for_mailinglist( $email );
							}
						}
					}
                    $response = $this->response( true );
				}
				break;
			default:
				$response = $this->response( false, [], 'Unknown action', 400 );
		}

		return $response;
	}

    /**
     * Signup for a mailing list
     */
    private function signup_for_mailinglist( string $email ) {
        $endpoint = $this->mailing_list_endpoint;

        if (!empty($endpoint)) {
            $api_params = [
                'email' => sanitize_email($email),
                'tags'   => $this->mailing_list,
            ];
            wp_remote_post(
                $endpoint,
                [
                    'timeout'   => 15,
                    'sslverify' => true,
                    'body'      => $api_params
                ]
            );
        }
    }

    /**
	 * Get onboarding steps
	 *
	 * @return array<int, array{
	 *      id: string,
	 *      type: string,
     *      icon?: string,
	 *      title: string,
	 *      subtitle?: string,
	 *      button?: array{id: string, label: string, icon?: string},
	 *      fields?: array<int, array<string, mixed>>,
	 *      solutions?: array<int, string>,
	 *      bullets?: array<int, string>,
     *      intro_bullets?: array{title: string, desc: string, icon?: string},
	 *      documentation?: string,
	 *  }> The onboarding steps array.
	 */
	public function get_steps(): array {
		if ( ! empty( $this->steps ) ) {
			return $this->steps;
		}

        // phpcs:ignore
		$steps = apply_filters( $this->prefix . '_onboarding_steps', [] );
		// Hook name based on prefix.
        // phpcs:ignore
		$steps       = apply_filters( $this->prefix . '_onboarding_steps', $steps );
		$steps       = $this->add_fields_data_to_steps( $steps );
		$steps       = $this->conditionally_drop_steps( $steps );
		$this->steps = $steps;
		return $this->steps;
	}

	/**
	 * Get recommended plugins for onboarding
	 *
	 * @return array<int, array{
	 *      slug: string,
	 *      file: string,
	 *      constant_free: string,
	 *      premium: array{
	 *          type: string,
	 *          value: string
	 *      },
	 *      wordpress_url: string,
	 *      upgrade_url: string,
	 *      title: string
	 *  }>
	 */
	private function get_recommended_plugins( bool $keys = false ): array {
		$installer = new Installer( $this->caller_slug );
		$plugins   = $installer->get_plugins( false, 3 );
        $this->is_all_plugins_installed = $installer->all_installed;
		if ( $keys ) {
			// just return the slugs as a value, value , value array.
			return array_column( $plugins, 'slug' );
		}
		return $plugins;
	}

	/**
	 * Check if the user has completed the onboarding in the free version.
	 * At least an hour ago, so we don't drop steps for the curren premium installing user.
	 */
	private function is_pro_with_onboarding_free_completed(): bool {
		// if the pro plugin is active, and the free plugin has completed onboarding, we can skip some parts of the onboarding.
		$free_completed_time            = get_site_option( "{$this->prefix}_onboarding_free_completed" );
		$now                            = time();
		$free_completed_over_1_hour_ago = $free_completed_time && ( $now - $free_completed_time > HOUR_IN_SECONDS );
		return $this->is_pro && $free_completed_over_1_hour_ago;
	}

	/**
	 * Enqueue onboarding scripts and styles
	 */
	public function enqueue_onboarding_scripts(): void {
		$steps      = $this->get_steps();
		$asset_file = include $this->onboarding_path . '/build/index.asset.php';

		wp_set_script_translations( 'teamupdraft_onboarding', 'all-in-one-wp-security-and-firewall', $this->languages_dir );

		wp_enqueue_script(
			'teamupdraft_onboarding',
			$this->onboarding_url . 'build/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_enqueue_style(
			$this->prefix . '_onboarding',
			$this->onboarding_url . "build/Onboarding.css",
			[],
			$asset_file['version']
		);

        wp_enqueue_style(
            'google-font-inter',
            'https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap',
            [],
            null
        );

        wp_localize_script(
			'teamupdraft_onboarding',
			'teamupdraft_onboarding',
			[
				'logo'                  => esc_url($this->logo_path),
				'prefix'                => $this->prefix,
				'plugin_name'           => $this->plugin_name,
				'version'               => $this->version,
				'steps'                 => $steps,
				'nonce'                 => wp_create_nonce( $this->prefix . '_nonce' ),
				'fields'                => $this->extract_fields_from_steps( $this->get_steps() ),
				'rest_url'              => get_rest_url(),
				'site_url'              => get_site_url(),
				'support'               => esc_url( $this->support_url ),
				'faqs'                  => esc_url( $this->faqs_url ),
				'documentation'         => esc_url( $this->documentation_url ),
				'upgrade'               => esc_url( $this->upgrade_url ),
                'privacy_statement_url' => esc_url( $this->privacy_statement_url ),
				'privacy_url_label'     => $this->privacy_url_label,
                'forgot_password_url'   => esc_url( $this->forgot_password_url ),
				'admin_ajax_url'        => add_query_arg( [ 'action' => $this->prefix . '_onboarding_rest_api_fallback' ], admin_url( 'admin-ajax.php' ) ),
				'is_pro'                => $this->is_pro,
				'network_link'          => network_site_url( 'plugins.php' ),
                'reload_on_finish'      => $this->reload_settings_page_on_finish,
                'text_domain'           => $this->text_domain,
                'udmupdater_nonce'      => wp_create_nonce($this->udmupdater_nonce),
                'udmupdater_muid'       => $this->udmupdater_muid,
                'udmupdater_slug'       => $this->udmupdater_slug,
                'udmupdater_mothership' => esc_url( $this->udmupdater_mothership ),
                'is_all_plugins_installed' => $this->is_all_plugins_installed,
                'exit_wizard_text'      => !empty($this->exit_wizard_text) ? $this->exit_wizard_text : __('Exit setup', 'all-in-one-wp-security-and-firewall'),

			]
		);
		// remember if user has completed the onboarding in the free plugin.
		if ( $this->is_pro ) {
            update_site_option( "{$this->prefix}_onboarding_free_completed", time() );
		}
	}
}
