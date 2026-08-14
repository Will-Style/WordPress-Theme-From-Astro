<?php
	if (!defined('ABSPATH')) die('No direct access allowed');
?>
<div class="udp-dialog-intro">
	<?php esc_html_e('Deactivating stops scheduled backups and all other plugin features, but does not delete any stored backup sets.', 'updraftplus'); ?>
</div>
<div class="udp-deinstall-dialog-content">
	<div class="udp-remove-data">
		<label class="udp-toggle-container">
			<input type="checkbox" name="updraftplus_deinstall_option" value="yes">
			<span class="udp-toggle-slider"></span>
		</label>
		<div class="udp-remove-text">
			<h4><?php esc_html_e('Remove plugin settings', 'updraftplus'); ?></h4>
			<p>
				<?php esc_html_e('Permanently deletes all UpdraftPlus settings and saved data, but not backup sets.', 'updraftplus'); ?>
				<?php esc_html_e('The data will only be deleted if the plugin is removed.', 'updraftplus'); ?>
				<?php esc_html_e('You still have a chance to undo your decision before the deletion happens.', 'updraftplus'); ?>
			</p>
		</div>
	</div>
</div>
