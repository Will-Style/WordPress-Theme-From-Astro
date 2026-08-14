var XOFieaturedImageTool;

(function ($) {
	XOFieaturedImageTool = function (parameters) {
		let
			self = this,
			total_count = parameters.post_ids.length,
			counter = 0,
			successes_counter = 0,
			error_counter = 0,
			abort_flag = false;

		this.sprintf = function () {
			let _arg = $.makeArray(arguments), template = _arg.shift(), i;
			for (let i in _arg) {
				template = template.replace('%s', _arg[i]);
			}
			return template;
		};

		this.updateStatus = function (response) {
			counter += response.count;
			successes_counter += (response.count - response.error_count);
			error_counter += response.error_count;

			$("#xo-featured-image-bar").progressbar("value", (counter / total_count) * 100);
			$("#xo-featured-image-bar-percent").html(Math.round((counter / total_count) * 100) + "%");

			$("#xo-featured-image-success-count").html(successes_counter);
			$("#xo-featured-image-error-count").html(error_counter);
			for (let message of response.messages) {
				$("#xo-featured-image-msg").append("<li>" + message + "</li>");
			}
		};

		this.finalization = function () {
			let $message = $("#message");
			let s;
	  
			$("#xo-featured-image-stop-bottun").hide();
			$("#xo-featured-image-message").hide();
			$("#xo-featured-image-back-link").show();
	  
			if ( abort_flag ) {
			  s = parameters.about_message;
			  $message.addClass("notice-success");
			} else {
				if (0 === parameters.post_ids.length) {
					if (0 === error_counter) {
						s = parameters.success_message;
					} else {
						s = this.sprintf(parameters.failure_message, error_counter);
					}
					$message.addClass("notice-success");
				} else {
					s = parameters.error_message;
					$message.addClass("notice-warning");
				}
			}
			$message.html("<p><strong>" + s + "</strong></p>");
			$message.show();
		};

		this.init = function () {
			$("#xo-featured-image-bar").progressbar();
			$("#xo-featured-image-bar-percent").html("0%");

			$("#xo-featured-image-stop-bottun").on("click", function(e) {
				abort_flag = true;
				$(e.target).prop("disabled", true);
				$(e.target).val(parameters.stop_button_message);
			});
		};

		this.start = function (ids) {
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: { action: 'xo_featured_image_tools', nonce: parameters.nonce, ids: ids, 'external_image': parameters.external_image, 'default_image': parameters.default_image },
			}).done(function(response) {
				if (response !== Object(response)) {
					response = new Object;
					response.count = 0;
				}
				self.updateStatus(response);
				if (parameters.post_ids.length && !abort_flag && response.count === ids.length) {
					self.start(parameters.post_ids.splice(0, parameters.count_per_step));
				} else {
					self.finalization();
				}
			});
		};

		this.init();
		this.start(parameters.post_ids.splice(0, parameters.count_per_step));
	};
}(jQuery));
