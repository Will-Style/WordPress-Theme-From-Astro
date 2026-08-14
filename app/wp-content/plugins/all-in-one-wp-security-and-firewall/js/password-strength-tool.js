(function($) {
	$.fn.extend({
		pwdstr: function(crack_time_calculation, crack_time_message, hibp_message) {
			return this.each(function() {
				var hibpTimer;
				
				$(this).on('input', function() {
					var user_pw = $(this).val();
					var crack_time = zxcvbn(user_pw);
					var crack_time_str = crack_time.crack_times_display.offline_slow_hashing_1e4_per_second;

					crack_time_str = crack_time_str.replace('centuries', aios_pwtool_trans.centuries);
					crack_time_str = crack_time_str.replace('less than a second', aios_pwtool_trans.less_than_a_second);

					crack_time_str = crack_time_str.replace('days', aios_pwtool_trans.days);
					crack_time_str = crack_time_str.replace('years', aios_pwtool_trans.years);
					crack_time_str = crack_time_str.replace('months', aios_pwtool_trans.months);
					crack_time_str = crack_time_str.replace('hours', aios_pwtool_trans.hours);
					crack_time_str = crack_time_str.replace('minutes', aios_pwtool_trans.minutes);
					crack_time_str = crack_time_str.replace('seconds', aios_pwtool_trans.seconds);
					
					$(crack_time_calculation).text(crack_time_str);
					$(crack_time_message).show();
					$(hibp_message).hide();
										
					var meterFill = $('#aios_meter_fill');
					if (user_pw.length === 0) {
						meterFill.css('width', '0').css('background-color', 'transparent');
					} else {
						switch (crack_time.score) {
							case 1:
								meterFill.css('width', '20%').css('background-color', 'red');
								break;
								
							case 2:
								meterFill.css('width', '50%').css('background-color', 'orange');
								break;
							
							case 3:
								meterFill.css('width', '75%').css('background-color', 'yellow');
								break;
							
							case 4:
								meterFill.css('width', '100%').css('background-color', 'green');
								break;
								
							default:
								meterFill.css('width', '5%').css('background-color', 'red');
						}
					}
					
					clearTimeout(hibpTimer);
					hibpTimer = setTimeout(() => {
						aios_send_command('hibp_check_password', {password: user_pw}, function(response) {
							if (response.pwned) {
								$(crack_time_message).hide();
								$(hibp_message).show();
							}
						});
					}, 600);
				});
			});
		}
	});
	$(document).ready(function() {
		$('#aiowps_password_test').pwdstr('#aiowps_password_crack_time_calculation', '#aiowps_password_crack_info_text', '#aiowps_password_hibp_info_text');
	});
})(jQuery);

