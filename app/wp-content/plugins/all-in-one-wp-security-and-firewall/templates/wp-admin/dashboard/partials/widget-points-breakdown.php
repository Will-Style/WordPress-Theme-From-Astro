<?php if (!defined('ABSPATH')) die('No direct access.'); ?>

<div class="aiowps-feature-category-achievement-container">
	<?php
	$show_premium_upsell = false;
	foreach ($categories as $category) {
		$total = $category['achievable_points'];
		$achieved = $category['achieved_points'];
		$percentage = $total > 0 ? ($achieved / $total) * 100 : 0;
		$show_locked_icon = isset($category['show_locked_icon']) ? $category['show_locked_icon'] : false;
		if ($show_locked_icon) $show_premium_upsell = true;
		?>
		<div class="aiowps-feature-category-progress-row">
			<div class="aiowps-feature-category-name-wrapper">
				<div class="aiowps-feature-category-name">
					<?php echo '<a href="' . esc_url(admin_url('admin.php?page=' . $category['url'])) . '" target="_blank">' . esc_html($category['name']) . '</a>';?>
				</div>

				<div class="aiowps-feature-category-percentage">
					<?php echo esc_html(round($percentage)); ?>% (<?php echo esc_html($achieved . '/' . $total . ($show_locked_icon ? '+' : '')); ?>)
				</div>
			</div>

			<div class="aiowps-feature-category-circles-container">
				<?php
				$circles_to_show = $show_locked_icon ? 4 : 5;

				for ($i = 0; $i < $circles_to_show; $i++) {
					$fill_amount = min(max(($percentage - ($i * 20)) / 20, 0), 1);
					$style = '';

					if ($fill_amount >= 0.9) {
						$fill_class = 'full';
					} elseif ($fill_amount <= 0.1) {
						$fill_class = 'empty';
					} elseif ($fill_amount >= 0.4 && $fill_amount <= 0.6) {
						$fill_class = 'half';
					} else {
						$fill_class = '';
						$style = 'transform: scaleX(' . $fill_amount . ');';
					}
					?>
					<div class="aiowps-feature-category-circle">
						<div class="aiowps-feature-category-circle-fill <?php echo esc_attr($fill_class); ?>" style="<?php echo esc_attr($style); ?>"></div>
					</div>
				<?php }

				if ($show_locked_icon) {
					?>
					<div class="aiowps-feature-category-circle aiowps-feature-category-premium-lock">
						<span class="dashicons dashicons-lock"></span>
					</div>
				<?php } ?>
			</div>
		</div>
	<?php } if ($show_premium_upsell) {?>
		<!-- Premium Explainer Row -->
		<div class="aiowps-feature-category-progress-row aiowps-feature-category-premium-explainer-row">
			<div class="aiowps-feature-category-name-wrapper">
				<div class="aiowps-feature-category-circle aiowps-feature-category-premium-lock explainer-lock">
					<span class="dashicons dashicons-lock"></span>
				</div>
				<div class="aiowps-feature-category-premium-explainer-text">
					<span class="aiowps-feature-category-premium-explainer-title"><?php esc_html_e('Premium Features', 'all-in-one-wp-security-and-firewall'); ?></span>
					<span class="aiowps-feature-category-premium-explainer-description">
						<?php
						$premium_url = admin_url('admin.php?page=' . AIOWPSEC_MAIN_MENU_SLUG . '&tab=premium-upgrade');

						/* translators: %1$s and %2$s are the opening and closing tags of a link to the Premium upgrade page. */
						printf(
							esc_html__('Unlock more features with %1$sPremium%2$s', 'all-in-one-wp-security-and-firewall'),
							'<strong><a href="' . esc_url($premium_url) . '" target="_blank">',
							'</a></strong>'
						);
						?>
					</span>
				</div>
			</div>
		</div>
	<?php } ?>
</div>