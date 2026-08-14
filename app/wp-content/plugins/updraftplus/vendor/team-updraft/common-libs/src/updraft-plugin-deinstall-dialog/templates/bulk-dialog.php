<?php if (!defined('ABSPATH')) die('No direct access allowed'); ?>
<div class="updraft-bulk-dialog-content" id="updraft-bulk-deinstall-items">
	<div class="updraft-bulk-dialog-header">
		<p>{{ data.intro }}</p>
	</div>
	<# _.each(data.plugins, function(plugin) { #>
	<div class="updraft-bulk-remove-data">
		<label class="updraft-bulk-toggle-container">
			<span class="updraft-bulk-toggle-switch">
				<input type="checkbox" name="updraft_remove_data[]" value="{{ plugin.slug }}">
				<span class="updraft-bulk-toggle-slider"></span>
			</span>
			<span class="updraft-bulk-toggle-label-text">{{ plugin.name }}</span>
		</label>
	</div>
	<# }); #>
	<# if (data.other_count > 0) { #>
	<div class="updraft-bulk-dialog-footer">
		<p>{{ data.other_message }}</p>
	</div>
	<# } #>
</div>
