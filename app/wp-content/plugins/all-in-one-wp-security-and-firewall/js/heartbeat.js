var WP_Security_Heartbeat_Agents = {};

/**
 * Attach to WordPress heartbeat API. Has a fallback method if core API is disabled
 *
 * @returns {object} WP_Security_Heartbeat exports
 */
var WP_Security_Heartbeat = function () {
	var $ = jQuery;
	var agent_idle_ttl_in_seconds = 30; // retry after 30 seconds without a response
	var aios_fallback;
	var _setup = false;

	/**
	 * Generate a unique ID to be used as agents IDs
	 *
	 * @returns {string}
	 */
	var guid = function() {
		var s4 = function() {
			return Math.floor((1 + Math.random()) * 0x10000)
				.toString(16)
				.substring(1);
		}
		return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
	}

	/**
	 * Configure the heartbeat events, if heartbeat API is missing, setup fallback
	 *
	 * @returns {void}
	 */
	function setup() {
		if (false === _setup) {
			_setup = true;

			$(document).on('heartbeat-send', function(event, data) {
				if ('undefined' !== typeof aios_heartbeat_ajax && aios_heartbeat_ajax.aios_nonce) {
					data.aios_heartbeat_nonce = aios_heartbeat_ajax.aios_nonce;
				}

				for (var uid in WP_Security_Heartbeat_Agents) {
					var agent = WP_Security_Heartbeat_Agents[uid];

					if (!agent.sent) {
						if ('command_data' in agent) {
							data[uid] = {};
							data[uid][agent.command] = agent.command_data;
						} else {
							data[uid] = agent.command;
						}

						agent.sent_time = new Date().getTime();
						agent.sent = true;
						agent.retry_count = agent.retry_count || 0;
					}

					// Check for timeout
					var seconds = ((new Date()).getTime() - agent.sent_time) / 1000;
					if (seconds > (agent.timeout || agent_idle_ttl_in_seconds)) {
						handleTimeout(uid, agent);
					}
				}
			});

			$(document).on('heartbeat-tick', function(event, data) {
				if ('object' === typeof(data.callbacks)) {
					for (var uid in data.callbacks) {
						if (is_aios_heartbeat(uid)) {
							var response;
							try {
								response = JSON.parse(data.callbacks[uid]);
							} catch (e) {
								response = data.callbacks[uid];
							}

							if ('undefined' !== typeof(response.result) && false === response.result && ('undefined' === typeof(response.skip_notice) || false === response.skip_notice)) {
								WP_Security_Heartbeat.notices.show_notice(response.error_code, response.error_message);
							} else {
								if ('undefined' !== typeof(WP_Security_Heartbeat_Agents[uid]) && WP_Security_Heartbeat_Agents[uid].callback instanceof Function) {
									WP_Security_Heartbeat_Agents[uid].callback(response);
								}
							}

							if ('undefined' !== typeof WP_Security_Heartbeat_Agents[uid]) {
								delete WP_Security_Heartbeat_Agents[uid];
							}
						}
					}
				}
			});

			if (is_heartbeat_api_disabled()) {
				aios_fallback = WP_Security_Heartbeat_Fallback();
			} else {
				wp.heartbeat.disableSuspend();
			}
		}
	}

	/**
	 * Handle timeout for an agent
	 *
	 * @param {string} uid Agent unique identifier
	 * @param {object} agent Agent object
	 */
	function handleTimeout(uid, agent) {
		// Reset sent status to allow immediate retry
		agent.sent = false;
		agent.retry_count++;

		// If max retries specified and exceeded, handle failure
		if (agent.max_retries && agent.retry_count > agent.max_retries) {
			if (agent.onTimeout instanceof Function) {
				agent.onTimeout();
			}
			delete WP_Security_Heartbeat_Agents[uid];
			return;
		}

		// Trigger immediate retry
		if (agent.immediate_retry !== false) {
			trigger_heartbeat();
		}
	}

	/**
	 * Add a heartbeat agent with enhanced timeout handling
	 *
	 * @param {boolean} [data._wait] Whether to wait for the next heartbeat tick before sending
	 * @param {boolean} [data._keep] Whether to keep the agent after execution
	 * @param {boolean} [data._unique] Whether the agent should be unique (prevent duplicates)
	 * @param {string} [data.command] The command to be executed
	 * @param {object} [data.command_data] Additional data for the command
	 * @param {function} [data.callback] Callback function to handle the response
	 *
	 * @returns {string|null}
	 */
	function add_agent(data) {
		var already_scheduled = Object.keys(WP_Security_Heartbeat_Agents).some(function(key) {
			return do_agents_match(WP_Security_Heartbeat_Agents[key], data);
		});

		if (already_scheduled && ('undefined' === typeof(data._unique) || (true === data._unique))) {
			return null;
		}

		var agent_id = 'aios-heartbeat-' + guid();
		data.sent = false;
		data.retry_count = 0;
		WP_Security_Heartbeat_Agents[agent_id] = data;

		if ('undefined' !== typeof(data._wait) && false === data._wait) {
			trigger_heartbeat();
		}

		return agent_id;
	}

	/**
	 * Checks if agent is already scheduled
	 *
	 * @param {object} agent Agent object
	 * @param {object} data Data to match against the agent
	 *
	 * @returns {boolean} True if agents match, false otherwise
	 */
	function do_agents_match(agent, data) {
		var command_matches = agent.command === data.command;
		var subaction = agent.command_data && agent.command_data.subaction ? agent.command_data.subaction : null;
		var data_action = data.command_data && data.command_data.subaction ? data.command_data.subaction : null;

		var subaction_matches = (subaction === data_action) || (!subaction && !data_action);

		return command_matches && subaction_matches;
	}

	/**
	 * Trigger a heartbeat by code
	 *
	 * @returns {void}
	 */
	function trigger_heartbeat() {
		if (is_heartbeat_api_disabled()) {
			aios_fallback.do_heartbeat();
		} else {
			setTimeout(function() {
				wp.heartbeat.connectNow();
			}, 50);
		}
	}

	/**
	 * Remove agent from list if `_keep:false`. Defaults to `true`, not cancel
	 * A method called cancel_agents that by default does not cancel anything is controversial,
	 * but in practice only things like informational requests can be really cancelled,
	 * otherwise you get strange inconsistent results when things get wiped out and callbacks are not being called.
	 *
	 * @param {string} agent_id The id of the agent to be removed
	 * @returns {void}
	 */
	function cancel_agent(agent_id) {
		var agent = WP_Security_Heartbeat_Agents[agent_id];
		if ('undefined' !== typeof(agent)) {
			if ('undefined' !== typeof(agent._keep) && (false === agent._keep)) {
				delete WP_Security_Heartbeat_Agents[agent_id];
			}
		}
	}

	/**
	 * Cancel a group of agents all at once
	 *
	 * @param {array} agents_ids The list of agent ids to cancel
	 */
	function cancel_agents(agents_ids) {
		agents_ids.forEach(function(agent_id) {
			cancel_agent(agent_id);
		});
	}

	/**
	 * Check if heartbeat action is a AIOS action or something else that we should ignore
	 *
	 * @param {string} uid The UID of the agent
	 * @returns {boolean}
	 */
	function is_aios_heartbeat(uid) {
		 return /^aios-heartbeat-/.test(uid) && ('undefined' !== typeof WP_Security_Heartbeat_Agents[uid]);
	}

	/**
	 * Check if native heartbeat API is available
	 *
	 * @returns {boolean}
	 */
	function is_heartbeat_api_disabled() {
		return 'undefined' === typeof(wp.heartbeat);
	}

	var notices = {
		errors: [],
		show_notice: function(error_code, error_message) {
			if (jQuery('#aios-wrap').length) {
				if (!this.notice) this.add_notice();
				this.notice.show();
				if (!this.errors[error_code]) {
					this.errors[error_code] = jQuery('<p/>').text(error_message).appendTo(this.notice).data('error_code', error_code);
				}
			} else if (window.wp && wp.hasOwnProperty('data')) {
				wp.data.dispatch('core/notices').createNotice(
					'error',
					'AIOS: ' + error_message,
					{ isDismissible: true }
				);
			} else {
				alert('AIOS: ' + error_message);
			}
		},
		add_notice: function() {
			var dismiss_text = window.commonL10n && window.commonL10n.dismiss ? window.commonL10n.dismiss : 'Dismiss';

			this.notice_container = jQuery('<div class="aios-main-error-notice"></div>').prependTo('#aios-wrap');
			this.notice = jQuery('<div class="notice notice-error aios-notice is-dismissible"><button type="button" class="notice-dismiss"><span class="screen-reader-text"></span></button></div>');
			this.notice.find('.screen-reader-text').text(dismiss_text);
			this.notice.appendTo(this.notice_container);
			this.notice.on('click', '.notice-dismiss', function() {
				this.notice.hide().find('p').remove();
				this.errors = [];
			}.bind(this));
		}
	};

	return {
		setup: setup,
		add_agent: add_agent,
		cancel_agents: cancel_agents,
		cancel_agent: cancel_agent,
		notices: notices
	};
}

/**
 * Fallback for the heartbeat api
 *
 * @returns {object} WP_Security_Heartbeat_Fallback exports
 */
var WP_Security_Heartbeat_Fallback = function() {
	var timeout_handler;

	var payload = {
		"data": {},
		"interval": aios_heartbeat_ajax.interval,
		"_nonce": aios_heartbeat_ajax.nonce,
		"action": "heartbeat",
		"screen_id": window.pagenow,
		"has_focus": false
	};

	/**
	 * Actually trigger the standard AJAX call to run a heartbeat event
	 *
	 * @param {int} interval How many seconds until next heartbeat
	 * @returns {void}
	 */
	function do_heartbeat(interval) {
		interval = 'undefined' === typeof(interval) ? payload.interval : interval;

		var this_payload = Object.assign({}, payload);
		var data = {};

		jQuery(document).trigger('heartbeat-send', data);

		this_payload.data = data;

		jQuery.ajax({
			type: "post",
			dataType: "json",
			url: aios_heartbeat_ajax.ajaxurl,
			data: this_payload,
			success: function(response) {
				if ('undefined' != typeof(response.callbacks)) {
					jQuery(document).trigger('heartbeat-tick', response);
				}
			},
			error: function() {
				// Reset sent state so agents can retry
				for (var uid in WP_Security_Heartbeat_Agents) {
					if (WP_Security_Heartbeat_Agents[uid].sent) {
						WP_Security_Heartbeat_Agents[uid].sent = false;
					}
				}
			}

		});

		if (timeout_handler) {
			clearTimeout(timeout_handler);
		}

		timeout_handler = setTimeout(do_heartbeat, interval * 1000, interval);
	}

	timeout_handler = setTimeout(do_heartbeat, payload.interval * 1000, payload.interval);

	return {
		do_heartbeat: do_heartbeat
	};
}
