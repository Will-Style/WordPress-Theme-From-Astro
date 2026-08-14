<?php if (!defined('ABSPATH')) die('Access denied.');
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- PCP error. Variables are not global.
?>
<div class="postbox">
	<h3 class="hndle"><label for="title"><?php esc_html_e('CAPTCHA provider', 'all-in-one-wp-security-and-firewall'); ?></label></h3>
	<div class="inside aiowps-settings">
		<?php if ($aio_wp_security->is_login_lockdown_by_const()) { ?>
			<div class="aio_red_box">
				<p>
					<?php esc_html_e('CAPTCHA will not work because you have disabled login lockout by activating the AIOS_DISABLE_LOGIN_LOCKOUT constant value in a configuration file.', 'all-in-one-wp-security-and-firewall'); ?>
					<br>
					<?php esc_html_e('To enable it, define AIOS_DISABLE_LOGIN_LOCKOUT constant value as false, or remove it.', 'all-in-one-wp-security-and-firewall'); ?>
				</p>
			</div>
		<?php } ?>
		<p>
			<?php echo esc_html__('This feature allows you to add a CAPTCHA form on various WordPress login pages and forms.', 'all-in-one-wp-security-and-firewall')
				. ' ' . esc_html__('Adding a CAPTCHA form on a login page or form is another effective yet simple "Brute Force" prevention technique.', 'all-in-one-wp-security-and-firewall');
			?>
			<br>
			<?php
			printf(
				/* translators: 1: Opening <a> tag for Cloudflare Turnstile, 2: Closing </a> tag, 3: Opening <a> tag for Google reCAPTCHA v2, 4: Closing </a> tag */
				esc_html__('You have the option of using either %1$sCloudflare Turnstile%2$s, %3$sGoogle reCAPTCHA v2%4$s or a plain maths CAPTCHA form.', 'all-in-one-wp-security-and-firewall'),
				'<a href="https://developers.cloudflare.com/turnstile/get-started/" target="_blank" rel="noopener noreferrer">',
				'</a>',
				'<a href="https://www.google.com/recaptcha" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			?>
		</p>
		<p>
			<?php
			printf(
				/* translators: 1: Opening <a> tag for Cloudflare Turnstile, 2: Closing </a> tag */
				esc_html__('We recommend %1$sCloudflare Turnstile%2$s as a more privacy-respecting option than Google reCAPTCHA', 'all-in-one-wp-security-and-firewall'),
				'<a href="https://blog.cloudflare.com/turnstile-private-captcha-alternative/" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
			?>
		</p>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php esc_html_e('Default CAPTCHA', 'all-in-one-wp-security-and-firewall'); ?>:</th>
				<td>
					<select name="aiowps_default_captcha" id="aiowps_default_captcha">
						<?php foreach ($supported_captchas as $key => $value) { ?>
							<option value="<?php echo esc_attr($key); ?>" <?php selected($key, $aiowps_default_captcha); ?>>
								<?php echo esc_html($value); ?>
							</option>							
						<?php } ?>
					</select>
				</td>
			</tr>
		</table>
		<div id="aios-cloudflare-turnstile" class="aio_grey_box captcha_settings <?php if ('cloudflare-turnstile' !== $aiowps_default_captcha) echo 'aio_hidden'; ?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="aiowps_turnstile_site_key"><?php esc_html_e('Site key', 'all-in-one-wp-security-and-firewall'); ?>:</label></th>
					<td><input id="aiowps_turnstile_site_key" type="text" size="50" name="aiowps_turnstile_site_key" value="<?php echo esc_attr($aiowps_turnstile_site_key); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="aiowps_turnstile_secret_key"><?php esc_html_e('Secret key', 'all-in-one-wp-security-and-firewall'); ?>:</label>
					</th>
					<td>
						<input id="aiowps_turnstile_secret_key" type="text" size="50" name="aiowps_turnstile_secret_key" value="<?php echo esc_attr(AIOWPSecurity_Utility::mask_string($aiowps_turnstile_secret_key)); ?>">
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="aiowps_turnstile_theme"><?php esc_html_e('Theme', 'all-in-one-wp-security-and-firewall'); ?>:</label>
					</th>
					<td>
						<select name="aiowps_turnstile_theme" id="aiowps_turnstile_theme">
							<?php foreach ($captcha_themes as $key => $value) { ?>
							<option value="<?php echo esc_attr($key); ?>" <?php selected($key, $captcha_theme); ?>>
								<?php echo esc_html($value); ?>
							</option>
							<?php } ?>
							
						</select>
					</td>
				</tr>
			</table>
		</div>
		<div id="aios-google-recaptcha-v2" class="aio_grey_box captcha_settings <?php if ('google-recaptcha-v2' !== $aiowps_default_captcha) echo 'aio_hidden'; ?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label for="aiowps_recaptcha_site_key"><?php esc_html_e('Site key', 'all-in-one-wp-security-and-firewall'); ?>:</label></th>
					<td><input id="aiowps_recaptcha_site_key" type="text" size="50" name="aiowps_recaptcha_site_key" value="<?php echo esc_attr($aiowps_recaptcha_site_key); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						<label for="aiowps_recaptcha_secret_key"><?php esc_html_e('Secret key', 'all-in-one-wp-security-and-firewall'); ?>:</label>
					</th>
					<td>
						<input id="aiowps_recaptcha_secret_key" type="text" size="50" name="aiowps_recaptcha_secret_key" value="<?php echo esc_attr(AIOWPSecurity_Utility::mask_string($aiowps_recaptcha_secret_key)); ?>">
					</td>
				</tr>
			</table>
		</div>
	</div>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound