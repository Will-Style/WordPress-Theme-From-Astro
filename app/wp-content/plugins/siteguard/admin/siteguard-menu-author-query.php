<?php

class SiteGuard_Menu_Author_Query extends SiteGuard_Base {
	const   OPT_NAME_FEATURE = 'block_author_query_enable';
	const   OPT_NAME_RESTAPI = 'disable_restapi_enable';
	const   OPT_NAME_EXCLUDE = 'disable_restapi_exclude';

	function __construct() {
		$this->render_page();
	}
	function render_page() {
		global $siteguard_config, $siteguard_author_query;

		$opt_val_feature = $siteguard_config->get( self::OPT_NAME_FEATURE );
		$opt_val_restapi = $siteguard_config->get( self::OPT_NAME_RESTAPI );
		$opt_val_exclude = $this->cvt_camma2ret( $siteguard_config->get( self::OPT_NAME_EXCLUDE ) );
		if ( isset( $_POST['update'] ) && check_admin_referer( 'siteguard-menu-block-author-query-submit' ) ) {
			$error  = false;
			$errors = siteguard_check_multisite();
			if ( is_wp_error( $errors ) ) {
				echo '<div class="error settings-error"><p><strong>';
				echo esc_html( $errors->get_error_message() );
				echo '</strong></p></div>';
				$error = true;
			}
			if ( false === $error && false === $this->is_switch_value( $_POST[ self::OPT_NAME_FEATURE ] ) ) {
				echo '<div class="error settings-error"><p><strong>';
				esc_html_e( 'ERROR: Invalid input value.', 'siteguard' );
				echo '</strong></p></div>';
				$error = true;
			}
			if ( false === $error ) {
				$old_opt_val_feature = $opt_val_feature;
				$old_opt_val_restapi = $opt_val_restapi;
				$old_opt_val_exclude = $opt_val_exclude;
				$opt_val_feature     = sanitize_text_field( $_POST[ self::OPT_NAME_FEATURE ] );
				if ( isset( $_POST[ self::OPT_NAME_RESTAPI ] ) ) {
					$opt_val_restapi = '1';
				} else {
					$opt_val_restapi = '0';
				}
				$opt_val_exclude = stripslashes( sanitize_textarea_field( $_POST[ self::OPT_NAME_EXCLUDE ] ) );
				$siteguard_config->set( self::OPT_NAME_FEATURE, $opt_val_feature );
				$siteguard_config->set( self::OPT_NAME_RESTAPI, $opt_val_restapi );
				$siteguard_config->set( self::OPT_NAME_EXCLUDE, $this->cvt_ret2camma( $opt_val_exclude ) );
				$siteguard_config->update();
				$result = true;
				if ( true === $result ) {
					$opt_val_exclude = $this->cvt_camma2ret( $opt_val_exclude );
					?>
					<div class="updated"><p><strong><?php esc_html_e( 'Options saved.', 'siteguard' ); ?></strong></p></div>
					<?php
				} else {
					$opt_val_feature = $old_opt_val_feature;
					$opt_val_restapi = $old_opt_val_restapi;
					$opt_val_exclude = $old_opt_val_exclude;
					$siteguard_config->set( self::OPT_NAME_FEATURE, $opt_val_feature );
					$siteguard_config->set( self::OPT_NAME_RESTAPI, $opt_val_restapi );
					$siteguard_config->set( self::OPT_NAME_EXCLUDE, $this->cvt_ret2camma( $opt_val_exclude ) );
					$siteguard_config->update();
					echo '<div class="error settings-error"><p><strong>';
					esc_html_e( 'ERROR: Failed to .htaccess update.', 'siteguard' );
					echo '</strong></p></div>';
				}
			}
		}

		echo '<div class="wrap">';
		echo '<img src="' . SITEGUARD_URL_PATH . 'images/sg_wp_plugin_logo_40.png" alt="SiteGuard Logo" />';
		echo '<h2>' . esc_html__( 'Block Author Query', 'siteguard' ) . '</h2>';
		echo '<div class="siteguard-description">'
		. esc_html__( 'You can find docs about this function on ', 'siteguard' )
		. '<a href="' . esc_url( __( 'https://www.jp-secure.com/siteguard_wp_plugin_en/howto/author_query/', 'siteguard' ) )
		. '" target="_blank">'
		. esc_html__( 'here', 'siteguard' )
		. '</a>'
		. esc_html__( '.', 'siteguard' )
		. '</div>';
		?>
		<form name="form1" method="post" action="">

		<table class="form-table">
		<tr>
		<th scope="row" colspan="2">
			<ul class="siteguard-radios">
			<li>
			<input type="radio" name="<?php echo self::OPT_NAME_FEATURE; ?>" id="<?php echo self::OPT_NAME_FEATURE . '_on'; ?>" value="1" <?php checked( $opt_val_feature, '1' ); ?> >
			<label for="<?php echo self::OPT_NAME_FEATURE . '_on'; ?>"><?php echo esc_html_e( 'ON', 'siteguard' ); ?></label>
			</li><li>
			<input type="radio" name="<?php echo self::OPT_NAME_FEATURE; ?>" id="<?php echo self::OPT_NAME_FEATURE . '_off'; ?>" value="0" <?php checked( $opt_val_feature, '0' ); ?> >
			<label for="<?php echo self::OPT_NAME_FEATURE . '_off'; ?>"><?php echo esc_html_e( 'OFF', 'siteguard' ); ?></label>
			</li>
			</ul>
			<?php
			$error = siteguard_check_multisite();
			if ( is_wp_error( $error ) ) {
				echo '<p class="description">';
				echo esc_html( $error->get_error_message() );
				echo '</p>';
			}
			?>
		</th>
		</tr><tr>
				<th scope="row"><?php esc_html_e( 'Option', 'siteguard' ); ?></th>
				<td>
						<input type="checkbox" name="<?php echo self::OPT_NAME_RESTAPI; ?>" id="<?php echo self::OPT_NAME_RESTAPI; ?>" value="1" <?php checked( $opt_val_restapi, '1' ); ?> >
						<label for="<?php echo self::OPT_NAME_RESTAPI; ?>"><?php esc_html_e( 'Disable REST API', 'siteguard' ); ?></label>
				</td>

		</tr>
		</tr><tr>
		<th scope="row"><label for="<?php echo self::OPT_NAME_EXCLUDE; ?>"><?php echo esc_html_e( 'Exclude Plugins', 'siteguard' ); ?></label></th>
		<td><textarea name="<?php echo self::OPT_NAME_EXCLUDE; ?>" id="<?php echo self::OPT_NAME_EXCLUDE; ?>" class="siteguard-box-300" cols=40 rows=10 ><?php echo esc_textarea( $opt_val_exclude ); ?></textarea>
		<p class="description"><?php esc_html_e( 'Please specify the plugin. To specify more than one, separate them with new line. ', 'siteguard' ); ?></p></br>
		<script>
		function add_value(){
			const crlf = String.fromCharCode(13) + String.fromCharCode(10)
			const plugins = document.form1.plugins;
			const excludes = document.form1.disable_restapi_exclude;
			const num = plugins.selectedIndex;
			const sel = plugins.options[num]
			const str = sel.value;
			if(num > -1){
				excludes.value = excludes.value + crlf + str;
				plugins.removeChild(sel);
			}
		}
		</script>
				<select name="plugins" class="siteguard-box-300" size="15">
		<?php
			$val_excludes = explode( "\r\n", $opt_val_exclude );
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			$plugins = get_plugins();
		if ( ! empty( $plugins ) ) {
			foreach ( $plugins as $path => $plugin ) {
				if ( is_plugin_active( $path ) ) {
					$text_domain = $plugin['TextDomain'];
					if ( ! in_array( $text_domain, $val_excludes ) && 'siteguard' != $text_domain ) {
						?>
						<option value="<?php esc_attr_e( $text_domain ); ?>"><?php echo esc_html( $text_domain ); ?></option>
						<?php
					}
				}
			}
		}
		?>
		</select>
		<input type="button" value="<?php esc_html_e( 'Add Exclude Plugin', 'siteguard' ); ?>" onclick="add_value()" />
		<p class="description"><?php esc_html_e( 'This is a list of valid plugins. You can select it and add it to the exclude plugins.', 'siteguard' ); ?></p></br>
		</td>
		</tr>
		</table>
		<input type="hidden" name="update" value="Y">
		<div class="siteguard-description">
		<?php esc_html_e( 'Prevents leakage of user names due to "/?author=<number>" access. You can also disable the REST API to prevent username leaks via the REST API. If there are plugins that do not work due to the REST API being disabled, please add the plugin name to the excluded plugins. You can add the plugin name from the list of enabled plugin names.', 'siteguard' ); ?>
		</div>
		<hr />
		<?php
		wp_nonce_field( 'siteguard-menu-block-author-query-submit' );
		submit_button();
		?>
		</form>
		</div>
		<?php
	}
}
