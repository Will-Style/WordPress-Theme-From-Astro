<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * XO Featured Image Tools admin class.
 *
 * @package xo-featured-image-tools
 * @since 0.1.0
 */

/**
 * XO Featured Image Tools admin class.
 */
class XO_Featured_Image_Tools_Admin {
	/**
	 * XO Featured Image Tools main.
	 *
	 * @var XO_Featured_Image_Tools_main
	 */
	private $parent;

	/**
	 * Tools page id.
	 *
	 * @var int
	 */
	private $tools_page_id;

	/**
	 * Settings page id.
	 *
	 * @var int
	 */
	private $settings_page_id;

	/**
	 * Options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Construction.
	 *
	 * @since 0.1.0
	 *
	 * @param XO_Featured_Image_Tools_main $parent_object XO Featured Image Tools_main.
	 */
	public function __construct( $parent_object ) {
		$this->parent  = $parent_object;
		$this->options = get_option( 'xo_featured_image_tools_options' );
		if ( false === $this->options ) {
			$this->options = $this->parent->get_default_options();
		}

		add_action( 'plugins_loaded', array( $this, 'setup' ) );
	}

	/**
	 * Set up processing in the administration panel.
	 *
	 * @since 0.1.0
	 */
	public function setup() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_ajax_xo_featured_image_tools', array( $this, 'ajax_featured_image' ) );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );

		// Post List.
		if ( isset( $this->options['list_posts'] ) ) {
			foreach ( $this->options['list_posts'] as $post_type ) {
				add_filter( "manage_edit-{$post_type}_columns", array( $this, 'add_columns' ), 1000 );
				add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'custom_columns' ), 10, 2 );
			}
		}
		add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts' ) );
		add_filter( 'parse_query', array( $this, 'parse_query' ) );
	}

	/**
	 * Add a menu to the administration panel.
	 *
	 * @since 0.1.0
	 */
	public function admin_menu() {
		// Tools menu.
		$this->tools_page_id = add_management_page(
			__( 'Featured Image', 'xo-featured-image-tools' ),
			__( 'Featured Image', 'xo-featured-image-tools' ),
			'manage_options',
			'xo-featured-image-tools',
			array( $this, 'tools_page' )
		);
		// Options menu.
		$this->settings_page_id = add_options_page(
			__( 'XO Featured Image', 'xo-featured-image-tools' ),
			__( 'XO Featured Image', 'xo-featured-image-tools' ),
			'manage_options',
			'xo_featured_image',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Enqueue styles and scripts in the administration panel.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function admin_enqueue_scripts( $hook_suffix ) {
		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		if ( $hook_suffix === $this->tools_page_id ) {
			wp_enqueue_style( 'xo-featured-image-tools', plugins_url( '/admin-tools.css', __FILE__ ), false, XO_FEATURED_IMAGE_TOOLS_VERSION );
			wp_enqueue_script( 'jquery-ui-progressbar' );
			wp_enqueue_script( 'xo-featured-image-tools', plugins_url( "/admin-tools{$min}.js", __FILE__ ), array( 'jquery-ui-progressbar' ), XO_FEATURED_IMAGE_TOOLS_VERSION, false );
		} elseif ( $hook_suffix === $this->settings_page_id ) {
			wp_enqueue_media();
			wp_enqueue_style( 'xo-featured-image-options', plugins_url( '/admin-options.css', __FILE__ ), false, XO_FEATURED_IMAGE_TOOLS_VERSION );
			wp_enqueue_script( 'xo-featured-image-options', plugins_url( "/admin-options{$min}.js", __FILE__ ), array( 'jquery' ), XO_FEATURED_IMAGE_TOOLS_VERSION, false );
		} elseif ( 'edit.php' === $hook_suffix ) {
			wp_enqueue_style( 'xo-featured-image-edit-list', plugins_url( '/admin-edit-list.css', __FILE__ ), false, XO_FEATURED_IMAGE_TOOLS_VERSION );
		}
	}

	/**
	 * Get attachment file from GUID.
	 *
	 * @since 1.1.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @param string $guid GUID.
	 * @return int Attachment post ID, 0 on failure.
	 */
	private function get_attachment_id_by_guid( $guid ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$attachment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND guid = %s LIMIT 1;",
				$guid
			)
		);
		return (int) $attachment_id;
	}

	/**
	 * Download the image of the specified URL and register it in the media library.
	 *
	 * @since 1.1.0
	 *
	 * @param string $url URL.
	 * @param int    $post_ID Post ID.
	 * @param int    $timeout The timeout for the request to download the file default 10 seconds.
	 * @return int|WP_Error ID of the attachment or a WP_Error object on failure.
	 */
	private function insert_attachment_from_url( $url, $post_ID = 0, $timeout = 10 ) {
		if ( ! function_exists( 'media_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$tmp_file = download_url( $url, $timeout );
		if ( is_wp_error( $tmp_file ) ) {
			return $tmp_file;
		}

		$file = array(
			'name'     => basename( $url ),
			'tmp_name' => $tmp_file,
		);

		$attachment_id = media_handle_sideload( $file, $post_ID, null, array( 'guid' => $url ) );

		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $tmp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink, WordPress.PHP.NoSilencedErrors.Discouraged
		}

		return $attachment_id;
	}

	/**
	 * Automatically set featured image.
	 *
	 * @since 1.10.0
	 *
	 * @param int     $post_ID Post id.
	 * @param WP_Post $post Post.
	 * @param bool    $external_image External image.
	 * @param int     $exclude_small_image_size Exclude small image size.
	 * @param int     $default_image_id Default imageid.
	 * @param bool    $skip_draft Skip draft flag.
	 * @return int Set attachment ID, -1 for error, 0 for no set.
	 */
	private function set_featured_image( $post_ID, $post, $external_image, $exclude_small_image_size, $default_image_id, $skip_draft = false ) {
		$content       = $post->post_content;
		$attachment_id = 0;

		/**
		 * Filters the post content.
		 *
		 * @since 1.11.0
		 *
		 * @param string $content The post content.
		 */
		$content = apply_filters( 'xo_featured_image_tools_post_content', $content, $post );

		if ( isset( $this->options['pattern_content'] ) && $this->options['pattern_content'] ) {
			if ( function_exists( 'do_blocks' ) ) {
				$content = do_blocks( $content );
			}
		}

		if ( isset( $this->options['shortcode_content'] ) && $this->options['shortcode_content'] ) {
			$content = do_shortcode( $content );
		}

		$home_url = get_home_url();

		$matches = array();
		preg_match_all( '/<img .*?src\s*=\s*[\"|\'](.*?)[\"|\'].*?>/i', $content, $matches );
		foreach ( $matches[0] as $key => $img ) {
			$attachment_id = 0;
			$url           = $matches[1][ $key ];

			/**
			 * Filters Image URL.
			 *
			 * @since 1.9.0
			 *
			 * @param string|bool $url Image URL. false to skip.
			 */
			$url = apply_filters( 'xo_featured_image_tools_image_url', $url );
			if ( false === $url ) {
				continue;
			}

			// Remove URL query.
			$url = strtok( $url, '?' );

			// Check filename.
			if ( ! empty( $this->options['exclude_filenames'] ) ) {
				$url_items = explode( '/', $url );
				$filename  = end( $url_items );
				foreach ( (array) $this->options['exclude_filenames'] as $exclude_filename ) {
					if ( fnmatch( $exclude_filename, $filename ) ) {
						continue 2;
					}
				}
			}

			// Get the ID from the wp-image-{$id} class.
			$class_matches = array();
			if ( preg_match( '/class\s*=\s*[\"|\'].*?wp-image-([0-9]*).*?[\"|\']/i', $img, $class_matches ) ) {
				if ( 0 === strpos( $url, $home_url ) ) {
					$id = (int) $class_matches[1];
					if ( ! empty( wp_get_attachment_image_url( $id ) ) ) {
						$attachment_id = $id;
					}
				}
			}

			// Get ID from URL.
			if ( ! $attachment_id ) {
				$attachment_id = $this->attachment_url_to_postid( $url );
			}

			// Get ID from GUID URL.
			if ( ! $attachment_id ) {
				if ( 0 === strpos( $url, $home_url ) ) {
					$attachment_id = $this->get_attachment_id_by_guid( $url );
				}
			}

			if ( $attachment_id ) {
				// Check size.
				if ( 0 < $exclude_small_image_size ) {
					$meta_data = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
					if ( ! is_array( $meta_data ) || ! isset( $meta_data['height'] ) || ! isset( $meta_data['width'] ) ||
						$meta_data['height'] <= $exclude_small_image_size ||
						$meta_data['width'] <= $exclude_small_image_size
					) {
						continue;
					}
				}
			} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
				// Get external image.
				if ( $external_image ) {
					// Check size.
					if ( 0 < $exclude_small_image_size ) {
						$size = $this->get_image_size( $url );
						if ( ! $size || $size[0] <= $exclude_small_image_size || $size[1] <= $exclude_small_image_size ) {
							continue;
						}
					}

					$attachment_id = $this->insert_attachment_from_url( $url, $post_ID );
					if ( is_wp_error( $attachment_id ) ) {
						return -1;
					}
				}
			}

			if ( $attachment_id ) {
				if ( update_post_meta( $post_ID, '_thumbnail_id', $attachment_id ) ) {
					return $attachment_id;
				} else {
					return -1;
				}
				break;
			}
		}

		// Gallery.
		if ( ! $attachment_id ) {
			$matches = array();
			if ( preg_match( '/\[\s*gallery\s.*ids\s*=\s*[\"|\']([0-9\s]+).*?[\"|\'].*?\]/i', $content, $matches ) ) {
				$attachment_id = $matches[1];
				if ( update_post_meta( $post_ID, '_thumbnail_id', $attachment_id ) ) {
					return $attachment_id;
				} else {
					return -1;
				}
			}
		}

		if ( ! $attachment_id ) {
			if ( $default_image_id ) {
				if ( ! $skip_draft || ! in_array( $post->post_status, array( 'auto-draft', 'draft' ), true ) ) {
					if ( update_post_meta( $post_ID, '_thumbnail_id', $default_image_id ) ) {
						return $default_image_id;
					} else {
						return -1;
					}
				}
			}
		}

		return 0;
	}

	/**
	 * Set the featured image in AJAX.
	 *
	 * @since 0.1.0
	 */
	public function ajax_featured_image() {
		check_ajax_referer( 'xo-featured-image-tools-tool', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			die( -1 );
		}

		if ( ! isset( $_REQUEST['ids'] ) ) {
			die( -1 );
		}

		set_time_limit( 600 );

		header( 'Content-type: application/json' );

		$post_ids            = array_map( 'absint', (array) $_REQUEST['ids'] );
		$external_image      = isset( $_REQUEST['external_image'] ) ? 'true' === $_REQUEST['external_image'] : false;
		$default_image       = isset( $_REQUEST['default_image'] ) ? (int) $_REQUEST['default_image'] : 0;
		$skip_draft          = isset( $this->options['skip_draft'] ) ? $this->options['skip_draft'] : false;
		$exclude_small_image = isset( $this->options['exclude_small_image'] ) ? $this->options['exclude_small_image'] : false;

		if ( $exclude_small_image ) {
			$exclude_small_image_size = isset( $this->options['exclude_small_image_size'] ) ? (int) $this->options['exclude_small_image_size'] : 0;
		} else {
			$exclude_small_image_size = 0;
		}

		$counter       = 0;
		$error_counter = 0;
		$messages      = array();

		try {
			foreach ( $post_ids as $post_id ) {
				$is_error = false;
				++$counter;

				$attachment_id = get_post_thumbnail_id( $post_id );
				if ( false === $attachment_id ) {
					$is_error = true;
				} elseif ( ! empty( $attachment_id ) ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
					// Already set.
				} else { // phpcs:ignore Universal.ControlStructures.DisallowLonelyIf.Found
					if ( get_post_meta( $post_id, 'disable_featured_image', true ) ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
						// Skipped.
					} else {
						$post   = get_post( $post_id );
						$result = $this->set_featured_image( $post_id, $post, $external_image, $exclude_small_image_size, $default_image, $skip_draft );
						if ( 0 === $result ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
							// No image.
						} elseif ( 0 < $result ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement
							// Set image.
						} else {
							$is_error = true;
						}
					}
				}

				if ( $is_error ) {
					$title = ( mb_strlen( $post->post_title ) > 34 ) ? mb_substr( $post->post_title, 0, 32 ) . '&hellip;' : $post->post_title;
					/* translators: 1: Post title, 2: Post id. */
					$messages[] = esc_html( sprintf( __( '"%1$s" (ID %2$d) failed.', 'xo-featured-image-tools' ), $title, $post_id ) );
					++$error_counter;
				}
			}
		} catch ( Exception $e ) {
			$messages[] = $e->getMessage();
		}

		die(
			wp_json_encode(
				array(
					'count'       => $counter,
					'error_count' => $error_counter,
					'messages'    => $messages,
				)
			)
		);
	}

	/**
	 * Output Featured Image Tools page.
	 *
	 * @since 0.1.0
	 */
	public function tools_page() {
		global $wpdb;

		$default_image = isset( $this->options['default_image'] ) ? (int) $this->options['default_image'] : 0;

		echo '<div id="message" class="updated fade" style="display:none;"></div>';
		echo '<div class="wrap xo-featured-image-tools">';
		echo '<h1>' . esc_html__( 'Featured Image Tools', 'xo-featured-image-tools' ) . '</h1>';

		if ( isset( $_POST['featured-image-delete-button'] ) && isset( $_REQUEST['featured-image-post-type'] ) ) {
			check_admin_referer( 'xo-featured-image-tools' );

			$post_type = sanitize_text_field( wp_unslash( $_REQUEST['featured-image-post-type'] ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND post_id IN (SELECT id FROM {$wpdb->posts} WHERE post_type = %s);",
					$post_type
				)
			);

			if ( false !== $result ) {
				echo '<div class="notice notice-success is-dismissible"><p><strong>'
					. esc_html__( 'Deleted.', 'xo-featured-image-tools' ) . '</strong></p></div>';
			} else {
				echo '<div class="notice notice-error is-dismissible"><p><strong>'
					. esc_html__( 'Failed to delete.', 'xo-featured-image-tools' ) . '</strong></p></div>';
			}
		}

		if ( isset( $_POST['featured-image-create-button'] ) && isset( $_REQUEST['featured-image-post-type'] ) ) {
			check_admin_referer( 'xo-featured-image-tools' );

			$post_type        = sanitize_text_field( wp_unslash( $_REQUEST['featured-image-post-type'] ) );
			$post_type_object = get_post_type_object( $post_type );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$posts = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s " .
					"AND (post_status = 'publish' OR post_status = 'private' OR post_status = 'future' OR post_status = 'draft' OR post_status = 'pending') " .
					"AND not exists (SELECT post_id FROM {$wpdb->postmeta} WHERE {$wpdb->postmeta}.meta_key = '_thumbnail_id' AND {$wpdb->postmeta}.post_id = {$wpdb->posts}.id ) " .
					'ORDER BY ID DESC;',
					$post_type
				)
			);

			if ( ! $posts || count( $posts ) === 0 ) {
				/* translators: %s: Post type. */
				echo '<p>' . esc_html( sprintf( __( '"%s" with no featured image set was not found.', 'xo-featured-image-tools' ), $post_type_object->label ) ) . '</p>';
				echo '<p><a href="' . esc_url( admin_url( 'tools.php?page=xo-featured-image-tools' ) ), '">' . esc_html__( '&laquo; Back to Tools page', 'xo-featured-image-tools' ) . '</a></p>';
				echo '</div>'; // .wrap
				return;
			}

			$post_ids = array();
			foreach ( $posts as $post ) {
				$post_ids[] = $post->ID;
			}

			$external_image = ( ! empty( $_REQUEST['featured-image-external-image'] ) );
			$default_image  = ( ! empty( $_REQUEST['featured-image-default-image'] ) ) ? $default_image : 0;
			$post_count     = count( $post_ids );

			/**
			 * Filters Count per step.
			 *
			 * @since 1.15.0
			 *
			 * @param int $count_per_step Count per step.
			 */
			$count_per_step = (int) apply_filters( 'xo_featured_image_tools_count_per_step', 10 );

			$xo_featured_image_tools_values = array(
				'nonce'               => wp_create_nonce( 'xo-featured-image-tools-tool' ),
				'post_ids'            => $post_ids,
				'post_count'          => $post_count,
				'count_per_step'      => $count_per_step,
				'external_image'      => $external_image,
				'default_image'       => $default_image,
				'stop_button_message' => __( 'Abort...', 'xo-featured-image-tools' ),
				'success_message'     => __( 'Completed. There is no failure.', 'xo-featured-image-tools' ),
				/* translators: %s: Failure message. */
				'failure_message'     => __( 'Completed. %s failed.', 'xo-featured-image-tools' ),
				'error_message'       => __( 'Aborted due to an error that cannot continue.', 'xo-featured-image-tools' ),
				'about_message'       => __( 'Aborted.', 'xo-featured-image-tools' ),
			);

			echo '<div id="xo-featured-image-back-link" style="display: none;">'
				. '<p><a href="' . esc_url( admin_url( 'tools.php?page=xo-featured-image-tools' ) ) . '">' . esc_html__( '&laquo; Back to Tools page', 'xo-featured-image-tools' ) . '</a></p>'
				. '</div>';

			echo '<p><span id="xo-featured-image-message">' . esc_html__( 'It may take some time. Please do not move from this page until it is completed.', 'xo-featured-image-tools' ) . '</span></p>';
			echo '<div id="xo-featured-image-bar" style="position:relative; height:25px;">';
			echo '<div id="xo-featured-image-bar-percent" style="position:absolute; left:50%;top:50%; width:300px; margin-left:-150px; height:25px; margin-top:-9px; font-weight:bold; text-align:center;"></div>';
			echo '</div>';
			echo '<p><input type="button" class="button hide-if-no-js" name="xo-featured-image-stop-bottun" id="xo-featured-image-stop-bottun" value="' . esc_attr( __( 'Stop', 'xo-featured-image-tools' ) ) . '" /></p>';
			echo '<h3 class="title">' . esc_html__( 'Status', 'xo-featured-image-tools' ) . '</h3>';
			/* translators: %s: Post type. */
			echo '<p>' . esc_html( sprintf( __( 'Post Type: %s', 'xo-featured-image-tools' ), $post_type_object->label ) ) . '</p>';
			/* translators: %s: Post count. */
			echo '<p>' . esc_html( sprintf( __( 'Total: %s', 'xo-featured-image-tools' ), $post_count ) ) . '</p>';
			/* translators: %s: Success count. */
			echo '<p>' . sprintf( esc_html__( 'Success: %s', 'xo-featured-image-tools' ), '<span id="xo-featured-image-success-count">0</span>' ) . '</p>';
			/* translators: %s: Error count. */
			echo '<p>' . sprintf( esc_html__( 'Failure: %s', 'xo-featured-image-tools' ), '<span id="xo-featured-image-error-count">0</span>' ) . '</p>';
			echo '<ol id="xo-featured-image-msg"></ol>';

			echo '<script type="text/javascript">';
			echo 'new XOFieaturedImageTool(' . wp_json_encode( $xo_featured_image_tools_values, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ');';
			echo '</script>' . "\n";
		} else {
			echo '<form method="post" action="">';

			wp_nonce_field( 'xo-featured-image-tools' );

			// Post type.
			$post_types = get_post_types( array(), 'objects' );
			echo '<p>' . esc_html__( 'Post type: ', 'xo-featured-image-tools' );
			echo '<select id="featured-image-post-type" name="featured-image-post-type">';
			foreach ( $post_types as $post_type ) {
				if ( post_type_supports( $post_type->name, 'thumbnail' ) ) {
					echo '<option value="' . esc_attr( $post_type->name ) . '" '
						. selected( isset( $_REQUEST['featured-image-post-type'] ) && $post_type->name === $_REQUEST['featured-image-post-type'] ) . '>'
						. esc_html( $post_type->label )
						. '</option >';
				}
			}
			echo '</select>';
			echo '</p>';

			// Generate.
			echo '<h2>' . esc_html__( 'Batch generation of featured images', 'xo-featured-image-tools' ) . '</h2>';

			$external_image = isset( $this->options['external_image'] ) ? $this->options['external_image'] : false;
			echo '<p><label><input id="featured-image-external-image" name="featured-image-external-image" type="checkbox" value="1" '
				. checked( 1, $external_image, false ) . '> '
				. esc_html__( 'Also applies to external images (images other than attachment files)', 'xo-featured-image-tools' ) . '</label></p>';

			if ( $default_image ) {
				echo '<p><label><input id="featured-image-default-image" name="featured-image-default-image" type="checkbox" value="1" checked="checked"> '
					. esc_html__( 'Use the default image', 'xo-featured-image-tools' ) . '</label></p>';
			} else {
				esc_html_e( 'To set the default image, specify the default image from the setting page.', 'xo-featured-image-tools' );
			}
			echo '<p><input type="submit" class="button button-primary hide-if-no-js" name="featured-image-create-button" id="featured-image-create-button" value="'
				. esc_attr( __( 'Generate featured image', 'xo-featured-image-tools' ) ) . '" /></p>';

			// Delete.
			echo '<h2>' . esc_html__( 'Batch deletion of featured images', 'xo-featured-image-tools' ) . '</h2>';

			echo '<p><label for="featured-image-delete-check"><input name="featured-image-delete-check" type="checkbox" id="featured-image-delete-check" value="1" '
				. 'onchange="document.getElementById(\'featured-image-delete-button\').disabled = !this.checked;"> '
				. esc_html__( 'Deletes the featured image for all posts selected in the post type. The images themselves will not be deleted from the media.', 'xo-featured-image-tools' ) . '</label>';
			printf(
				'<p><input type="submit" class="button button-danger" name="featured-image-delete-button" id="featured-image-delete-button" value="%s" disabled onclick="return confirm( \'%s\' );" /></p>',
				esc_attr( __( 'Delete featured images', 'xo-featured-image-tools' ) ),
				esc_js( __( "Deletes the featured images for all posts selected in post type.\nThis action cannot be undone.\nClick 'Cancel' to go back, 'OK' to confirm the delete.", 'xo-featured-image-tools' ) )
			);

			echo '</form>';
		}

		echo '</div>' . "\n"; // .wrap
	}

	/**
	 * The attachment_url_to_postid() subsize support version.
	 *
	 * @since 1.6.3
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param string $url The URL to resolve.
	 * @return int The found post ID, or 0 on failure.
	 */
	private function attachment_url_to_postid( $url ) {
		$attachment_id = $this->get_attachment_id_by_url( $url );
		if ( ! $attachment_id ) {
			$attachment_id = $this->get_attachment_id_by_url( $url, false );
		}
		return $attachment_id;
	}

	/**
	 * Acquire the ID from the URL of the attachment file.
	 *
	 * @since 0.2.0
	 *
	 * @global wpdb $wpdb
	 * @param string $url     Attachment file URL.
	 * @param bool   $is_full_size option. A value indicating whether it is full size. True for full size, false for different size. The default is true.
	 * @return int It returns ID (1 or more) if it succeeds, 0 if it does not exist.
	 */
	private function get_attachment_id_by_url( $url, $is_full_size = true ) {
		global $wpdb;

		$attachment_id = 0;

		// If it is a relative URL, convert it to an absolute URL.
		$parse_url = wp_parse_url( $url );
		if ( ! isset( $parse_url['host'] ) ) {
			if ( isset( $_SERVER['SERVER_NAME'] ) ) {
				$host = set_url_scheme( '//' . strtolower( sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) ) );
				$url  = rtrim( $host, '/' ) . '/' . ltrim( $parse_url['path'], '/' );
			}
		}

		$full_size_url = $url;

		if ( ! $is_full_size ) {
			// Remove the size notation (-999x999) from the URL to get the full-size URL.
			$full_size_url = preg_replace( '/(-[0-9]+x[0-9]+)(\.[^.]+){0,1}$/i', '${2}', $url );
			if ( $url === $full_size_url ) {
				// Abort because it is not a different size.
				return $attachment_id;
			}
		}

		$uploads  = wp_upload_dir();
		$base_url = $uploads['baseurl'];
		if ( strpos( $full_size_url, $base_url ) === 0 ) {
			$attached_file = str_replace( $base_url . '/', '', $full_size_url );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$attachment_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1;",
					$attached_file
				)
			);
		}

		return (int) $attachment_id;
	}

	/**
	 * When saving a post, save the first image in the content as an eye catch image.
	 *
	 * @since 0.2.0
	 *
	 * @param int     $post_ID Post id.
	 * @param WP_Post $post    Post.
	 * @filter save_post
	 */
	public function save_post( $post_ID, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_ID ) ) {
			return;
		}

		if ( ! isset( $this->options['auto_save_posts'] ) || ! in_array( $post->post_type, $this->options['auto_save_posts'], true ) ) {
			return;
		}

		if ( 'auto-draft' === $post->post_status ) {
			return;
		}

		// If the Disable_featured_image custom field is set, skip it.
		if ( get_post_meta( $post_ID, 'disable_featured_image', true ) ) {
			return;
		}

		$attachment_id = get_post_meta( $post_ID, '_thumbnail_id', true );
		if ( ! $attachment_id ) {
			$external_image      = isset( $this->options['external_image'] ) ? $this->options['external_image'] : false;
			$exclude_small_image = isset( $this->options['exclude_small_image'] ) ? $this->options['exclude_small_image'] : false;
			if ( $exclude_small_image ) {
				$exclude_small_image_size = isset( $this->options['exclude_small_image_size'] ) ? (int) $this->options['exclude_small_image_size'] : 0;
			} else {
				$exclude_small_image_size = 0;
			}
			$default_image = isset( $this->options['default_image'] ) ? (int) $this->options['default_image'] : 0;
			$skip_draft    = isset( $this->options['skip_draft'] ) ? $this->options['skip_draft'] : false;
			$this->set_featured_image( $post_ID, $post, $external_image, $exclude_small_image_size, $default_image, $skip_draft );
		}
	}

	/**
	 * Add the featured Image column.
	 *
	 * @since 0.3.0
	 *
	 * @param string[] $columns The column header labels keyed by column ID.
	 */
	public function add_columns( $columns ) {
		if ( ! is_array( $columns ) ) {
			$columns = array();
		}
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			if ( 'title' === $key ) {
				$new_columns['featured-image'] = __( 'Image', 'xo-featured-image-tools' );
			}
			$new_columns[ $key ] = $value;
		}
		return $new_columns;
	}

	/**
	 * Output the featured Image column.
	 *
	 * @since 0.3.0
	 *
	 * @param string $column  Column name.
	 * @param int    $post_ID Post id.
	 */
	public function custom_columns( $column, $post_ID ) {
		if ( 'featured-image' === $column ) {
			if ( has_post_thumbnail( $post_ID ) ) {
				echo get_the_post_thumbnail( $post_ID, 'thumbnail' );
			} else {
				echo '<div class="featured-image-none"></div>';
			}
		}
	}

	/**
	 * Output the featured image filter to the list of posts.
	 *
	 * @since 0.3.0
	 *
	 * @param string $post_type Post type.
	 */
	public function restrict_manage_posts( $post_type ) {
		if ( isset( $this->options['list_posts'] ) && in_array( $post_type, $this->options['list_posts'], true ) ) {
			$filter = isset( $_GET['featured_image_filter'] ) ? ( 'all' === $_GET['featured_image_filter'] ? 'all' : 'notset' ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			echo '<select name="featured_image_filter" id="featured_image_filter">';
			echo '<option value="all" ' . selected( 'all', $filter ) . '>' . esc_html__( 'Featured Image', 'xo-featured-image-tools' ) . '</option>';
			echo '<option value="notset" ' . selected( 'notset', $filter ) . '>' . esc_html__( 'Not set', 'xo-featured-image-tools' ) . '</option>';
			echo '</select>' . "\n";
		}
	}

	/**
	 * Add the featured image filter to the list of posts.
	 *
	 * @since 0.3.0
	 *
	 * @param WP_Query $query The WP_Query instance.
	 */
	public function parse_query( $query ) {
		global $pagenow, $post_type;
		if ( is_admin() && 'edit.php' === $pagenow && isset( $_GET['featured_image_filter'] ) && 'all' !== $_GET['featured_image_filter'] ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $this->options['list_posts'] ) && in_array( $post_type, $this->options['list_posts'], true ) ) {
				$query->query_vars['meta_key']     = '_thumbnail_id'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				$query->query_vars['meta_compare'] = 'NOT EXISTS';
				$query->query_vars['meta_value']   = ''; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			}
		}
	}

	/**
	 * Output the settings page.
	 *
	 * @since 0.3.0
	 */
	public function settings_page() {
		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'XO Featured Image Tools Settings', 'xo-featured-image-tools' ) . '</h1>';
		echo '<form method="post" action="options.php">';
		settings_fields( 'xo_featured_image_tools_group' );
		do_settings_sections( 'xo_featured_image_tools_group' );
		submit_button();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Register the settings.
	 *
	 * @since 0.3.0
	 */
	public function register_settings() {
		register_setting( 'xo_featured_image_tools_group', 'xo_featured_image_tools_options', array( $this, 'sanitize' ) );
		add_settings_section( 'xo_featured_image_tools_posts_list_section', __( 'Post List', 'xo-featured-image-tools' ), '__return_empty_string', 'xo_featured_image_tools_group' );
		add_settings_field( 'enable_edit_list', __( 'Featured Image Item', 'xo-featured-image-tools' ), array( $this, 'field_list_posts' ), 'xo_featured_image_tools_group', 'xo_featured_image_tools_posts_list_section' );

		add_settings_section( 'xo_featured_image_tools_edit_post_section', __( 'Edit Post', 'xo-featured-image-tools' ), '__return_empty_string', 'xo_featured_image_tools_group' );
		add_settings_field( 'enable_edit_list', __( 'Automatically generated', 'xo-featured-image-tools' ), array( $this, 'field_auto_save_posts' ), 'xo_featured_image_tools_group', 'xo_featured_image_tools_edit_post_section' );

		add_settings_section( 'xo_featured_image_tools_options_section', __( 'Options', 'xo-featured-image-tools' ), '__return_empty_string', 'xo_featured_image_tools_group' );
		add_settings_field( 'external_image', __( 'External image', 'xo-featured-image-tools' ), array( $this, 'field_external_image' ), 'xo_featured_image_tools_group', 'xo_featured_image_tools_options_section' );
		add_settings_field( 'exclude_small_image', __( 'Exclude small image', 'xo-featured-image-tools' ), array( $this, 'field_exclude_small_image' ), 'xo_featured_image_tools_group', 'xo_featured_image_tools_options_section' );
		add_settings_field( 'exclude_filenames', __( 'Exclude specific file names', 'xo-featured-image-tools' ), array( $this, 'field_exclude_filenames' ), 'xo_featured_image_tools_group', 'xo_featured_image_tools_options_section' );
		add_settings_field( 'default_image', __( 'Default image', 'xo-featured-image-tools' ), array( $this, 'field_default_image' ), 'xo_featured_image_tools_group', 'xo_featured_image_tools_options_section' );
		add_settings_field( 'shortcode_content', __( 'Shortcode', 'xo-featured-image-tools' ), array( $this, 'field_shortcode_content' ), 'xo_featured_image_tools_group', 'xo_featured_image_tools_options_section' );
		add_settings_field( 'pattern_content', __( 'Synced Pattern', 'xo-featured-image-tools' ), array( $this, 'field_pattern_content' ), 'xo_featured_image_tools_group', 'xo_featured_image_tools_options_section' );
	}

	/**
	 * Register the list posts field.
	 *
	 * @since 0.3.0
	 */
	public function field_list_posts() {
		$checks     = isset( $this->options['list_posts'] ) ? $this->options['list_posts'] : array();
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		echo '<fieldset>';
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'thumbnail' ) ) {
				$check = in_array( $post_type->name, $checks, true );
				echo '<label for="list_posts_' . esc_attr( $post_type->name ) . '">';
				printf( '<input id="list_posts_%1$s" type="checkbox" class="checkbox" name="xo_featured_image_tools_options[list_posts][]" value="%1$s" %2$s> %3$s (%1$s)', esc_attr( $post_type->name ), checked( $check, true, false ), esc_html( $post_type->label ) );
				echo '</label><br />';
			}
		}
		echo '<p class="description">' . esc_html__( 'Please select the post type to display featured image item.', 'xo-featured-image-tools' ) . '</p>';
		echo "</fieldset>\n";
	}

	/**
	 * Register the auto save posts field.
	 *
	 * @since 0.3.0
	 */
	public function field_auto_save_posts() {
		$checks     = isset( $this->options['auto_save_posts'] ) ? $this->options['auto_save_posts'] : array();
		$post_types = get_post_types( array( 'public' => true ), 'objects' );

		echo '<fieldset>';
		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'thumbnail' ) ) {
				$check = in_array( $post_type->name, $checks, true );
				echo '<label for="auto_save_posts_' . esc_attr( $post_type->name ) . '">';
				printf( '<input id="auto_save_posts_%1$s" type="checkbox" class="checkbox" name="xo_featured_image_tools_options[auto_save_posts][]" value="%1$s" %2$s> %3$s (%1$s)', esc_attr( $post_type->name ), checked( $check, true, false ), esc_html( $post_type->label ) );
				echo '</label><br />';
			}
		}
		echo '<p class="description">' . esc_html__( 'Please select the post type that automatically generates featured images.', 'xo-featured-image-tools' ) . '</p>';
		echo "</fieldset>\n";
	}

	/**
	 * Register the external image field.
	 *
	 * @since 1.1.0
	 */
	public function field_external_image() {
		$check = isset( $this->options['external_image'] ) ? $this->options['external_image'] : false;

		echo '<label for="external_image"><input id="external_image" name="xo_featured_image_tools_options[external_image]" type="checkbox" value="1" class="code" ' . checked( 1, $check, false ) . ' /> '
			. esc_html__( 'Also applies to external images (images other than attachment files)', 'xo-featured-image-tools' ) . '</label>';
	}

	/**
	 * Register exclude small image field.
	 *
	 * @since 1.5.0
	 */
	public function field_exclude_small_image() {
		$check = isset( $this->options['exclude_small_image'] ) ? $this->options['exclude_small_image'] : false;
		$size  = isset( $this->options['exclude_small_image_size'] ) ? $this->options['exclude_small_image_size'] : 99;

		echo '<label for="exclude_small_image"><input id="exclude_small_image" name="xo_featured_image_tools_options[exclude_small_image]" type="checkbox" value="1" class="code" ' . checked( 1, $check, false ) . ' /></label> ';
		echo '<label for="exclude_small_image_size"><input id="exclude_small_image_size" name="xo_featured_image_tools_options[exclude_small_image_size]" type="number" value="' . esc_attr( $size ) . '" class="small-text" min="0" max="9999" step="1" /> '
			. esc_html__( 'px or less', 'xo-featured-image-tools' ) . '</label>';
	}

	/**
	 * Register exclude filename.
	 *
	 * @since 1.14.0
	 */
	public function field_exclude_filenames() {
		$filenames = isset( $this->options['exclude_filenames'] ) ? implode( ',', (array) $this->options['exclude_filenames'] ) : '';

		echo '<label for="exclude_filenames"><input id="exclude_filenames" name="xo_featured_image_tools_options[exclude_filenames]" type="text" value="' . esc_attr( $filenames ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Comma-separated list of file names to exclude. Wildcards ("*", "?") are allowed.', 'xo-featured-image-tools' ) . '</p>';
	}

	/**
	 * Register the default image field.
	 *
	 * @since 1.3.0
	 */
	public function field_default_image() {
		$image_id   = isset( $this->options['default_image'] ) ? $this->options['default_image'] : 0;
		$image_src  = ( $image_id ) ? wp_get_attachment_image_src( $image_id, array( 150, 150 ) ) : false;
		$skip_draft = isset( $this->options['skip_draft'] ) ? $this->options['skip_draft'] : false;

		echo '<input id="default_image" name="xo_featured_image_tools_options[default_image]" type="hidden" value="' . esc_attr( $image_id ) . '" />';
		echo '<input class="button hide-if-no-js" name="default_image_setting" id="default_image_setting" value="' . esc_attr( __( 'Select Image', 'xo-featured-image-tools' ) ) . '" type="button" data-title="' . esc_attr( __( 'Default Image', 'xo-featured-image-tools' ) ) . '">&nbsp;';
		echo '<input class="button hide-if-no-js" name="default_image_clear" id="default_image_clear" value="' . esc_attr( __( 'Clear Image', 'xo-featured-image-tools' ) ) . '" type="button"' . disabled( false === $image_src, true, false ) . '>';

		echo '<div id="default-image-area">';
		if ( $image_src ) {
			echo '<img src="' . esc_url( $image_src[0] ) . '">';
		}
		echo '<br />';
		echo '<label for="skip_draft">';
		echo '<input id="skip_draft" name="xo_featured_image_tools_options[skip_draft]" type="checkbox" value="1" class="code" ' . checked( $skip_draft, 1, false ) . ' ' . disabled( empty( $image_src ), true, false ) . ' /> ';
		echo esc_html__( 'Exclude draft post', 'xo-featured-image-tools' );
		echo '</label>';
		echo "</div>\n"; // #default-image-area
	}

	/**
	 * Register the shortcode content field.
	 *
	 * @since 1.13.0
	 */
	public function field_shortcode_content() {
		$check = isset( $this->options['shortcode_content'] ) ? $this->options['shortcode_content'] : false;

		echo '<label for="shortcode_content"><input id="shortcode_content" name="xo_featured_image_tools_options[shortcode_content]" type="checkbox" value="1" class="code" ' . checked( 1, $check, false ) . ' /> '
			. esc_html__( 'Shortcode content', 'xo-featured-image-tools' ) . '</label>';
	}

	/**
	 * Register the pattern content field.
	 *
	 * @since 1.13.0
	 */
	public function field_pattern_content() {
		$check = isset( $this->options['pattern_content'] ) ? $this->options['pattern_content'] : false;

		echo '<label for="pattern_content"><input id="pattern_content" name="xo_featured_image_tools_options[pattern_content]" type="checkbox" value="1" class="code" ' . checked( 1, $check, false ) . ' /> '
			. esc_html__( 'Synced Pattern content', 'xo-featured-image-tools' ) . '</label>';
	}

	/**
	 * Sanitize our setting.
	 *
	 * @since 0.3.0
	 *
	 * @param array $input Input data.
	 */
	public function sanitize( $input ) {
		$input['external_image']           = ( isset( $input['external_image'] ) );
		$input['exclude_small_image_size'] = isset( $input['exclude_small_image_size'] ) ? intval( $input['exclude_small_image_size'] ) : 48;
		$input['default_image']            = ( isset( $input['default_image'] ) ) ? (int) $input['default_image'] : 0;
		$input['skip_draft']               = ( isset( $input['skip_draft'] ) );
		$input['shortcode_content']        = ( isset( $input['shortcode_content'] ) );
		$input['pattern_content']          = ( isset( $input['pattern_content'] ) );

		$exclude_filenames = array();
		foreach ( explode( ',', (string) $input['exclude_filenames'] ) as $exclude_filename ) {
			$exclude_filename = trim( $exclude_filename );
			$check_filename   = str_replace( array( '*', '?' ), 'a', $exclude_filename );
			if ( sanitize_file_name( $check_filename ) === $check_filename ) {
				$exclude_filenames[] = $exclude_filename;
			}
		}
		$input['exclude_filenames'] = $exclude_filenames;

		return $input;
	}

	/**
	 * Compat function to mimic wp_getimagesize().
	 *
	 * @since 1.8.0
	 *
	 * @see wp_getimagesize()
	 *
	 * @param string $filename   The file path.
	 * @return array|false Array of image information or false on failure.
	 */
	private function get_image_size( $filename ) {
		if ( function_exists( 'wp_getimagesize' ) ) {
			return wp_getimagesize( $filename );
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG
			&& ! defined( 'WP_RUN_CORE_TESTS' )
		) {
			$info = getimagesize( $filename );
		} else {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors
			$info = @getimagesize( $filename );
		}

		if ( false !== $info ) {
			return $info;
		}

		if ( 'image/webp' === wp_get_image_mime( $filename ) ) {
			$webp_info = wp_get_webp_info( $filename );
			$width     = $webp_info['width'];
			$height    = $webp_info['height'];

			// Mimic the native return format.
			if ( $width && $height ) {
				return array(
					$width,
					$height,
					IMAGETYPE_WEBP,
					sprintf(
						'width="%d" height="%d"',
						$width,
						$height
					),
					'mime' => 'image/webp',
				);
			}
		}

		return false;
	}

	/**
	 * Filters the action links displayed for each plugin in the Plugins list table.
	 *
	 * @since 1.12.0
	 *
	 * @param string[] $actions     An array of plugin action links.
	 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
	 * @return array An array of plugin action links.
	 */
	public function plugin_action_links( $actions, $plugin_file ) {
		if ( 'xo-featured-image-tools.php' === basename( $plugin_file ) ) {
			$actions = array_merge(
				array(
					'<a href="' . admin_url( 'options-general.php?page=xo_featured_image' ) . '">' . esc_html__( 'Settings', 'xo-featured-image-tools' ) . '</a>',
					'<a href="' . admin_url( 'tools.php?page=xo-featured-image-tools' ) . '">' . esc_html__( 'Tools', 'xo-featured-image-tools' ) . '</a>',
				),
				$actions
			);
		}
		return $actions;
	}
}
