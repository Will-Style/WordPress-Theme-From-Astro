/**
 * Deactivation Dialog Script for UpdraftPlus Plugin
 * This script customizes the deactivation dialog by adding specific classes to buttons
 */

(function($) {
	$(document).on('dialogopen', '#updraftplus-deinstall-dialog', function() {
		var $dialog = $(this);
		$('body').addClass('udp-no-scroll');
		var btn = $('.updraftplus-ui-deinstall-dialog .ui-dialog-buttonset button:first-child');
		$dialog.on('click', '.udp-toggle-container', function() {
			btn.text($(this).find('input:checked').length ? upraftplusdialog.remove : upraftplusdialog.deactivate);
		});
		// Show spinner on click.
		btn[0].addEventListener('click', function() {
			if (btn.hasClass('udp-loading')) return;
			btn.addClass('udp-loading').html('<span class="udp-spinner" aria-hidden="true"></span>' + btn.text());
			$dialog.dialog('option', 'beforeClose', function() {
				return false;
			});
		}, true);
		$dialog.on('dialogclose', function() {
			$('body').removeClass('udp-no-scroll');
		});
	});

})(jQuery);
