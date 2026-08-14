/**
 * Initialize global object for onboarding actions if it doesn't exist
 */
if (!window.pluginOnboardingActions) {
	window.pluginOnboardingActions = {};
}

/**
 * Initialize global object for filters if it doesn't exist
 */
if (!window.updraftOnboardingFilters) {
	window.updraftOnboardingFilters = {};
}

/**
 * Adds a filter function to a specific tag.
 *
 * @param {string} tag The name of the filter.
 * @param {function} callback The function to be called when the filter is applied.
 */
window.pluginOnboardingActions.addFilter = function(tag, callback) {
	if (!window.updraftOnboardingFilters[tag]) {
		window.updraftOnboardingFilters[tag] = [];
	}
	window.updraftOnboardingFilters[tag].push(callback);
};

/**
 * Applies all filter functions registered for a specific tag to a value.
 *
 * @param {string} tag The name of the filter.
 * @param {any} value The value to filter.
 * @returns {any} The filtered value.
 */
window.pluginOnboardingActions.applyFilters = function(tag, value) {
	const args = Array.prototype.slice.call(arguments, 2);

	if (window.updraftOnboardingFilters[tag]) {
		window.updraftOnboardingFilters[tag].forEach(function(callback) {
			value = callback.apply(null, [value].concat(args));
		});
	}

	return value;
};

/**
 * Helper function to get a setting value from the settings array.
 *
 * @param {Array<object>} settings The entire form settings array from Zustand.
 * @param {string} id The ID of the setting to retrieve.
 * @param {any} defaultValue The default value if the setting is not found.
 * @returns {any} The value of the setting or the default value.
 */
const updraftGetSettingValue = function(settings, id, defaultValue) {
	if (typeof defaultValue === 'undefined') {
		defaultValue = '';
	}

	const found = settings.find(function(s) {
		return s.id === id;
	});

	if (found && typeof found.value !== 'undefined') {
		return found.value;
	}

	return defaultValue;
};

/**
 * Simple email validation function.
 *
 * @param {string} email The email string to validate.
 * @returns {boolean} True if the email is valid, false otherwise.
 */
const updraftIsValidEmail = function(email) {
	const trimmed = (email || "").trim();
	return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed);
};

/**
 * Creates callback objects (success and error) for storage connection testing.
 * This helps keep the code DRY when there are many connection testing functions.
 *
 * @param {object} options Options to configure the callbacks.
 * @param {string} options.groupId The group ID (e.g., 'backblaze', 'azure').
 * @param {string} options.methodLabel A user-friendly label for the storage method (e.g., 'Backblaze', 'Azure').
 * @param {function} options.setAlertState Function to update connection status in Zustand store.
 * @param {function} options.setValue Function to update a field's value in Zustand for completion status.
 * @returns {{successCallback: Function, errorCallback: Function}} An object containing the callback functions.
 */
window.pluginOnboardingActions.createTestConnectionCallbacks = function({groupId, methodLabel, setAlertState, setValue}) {
	const successCallback = function(response, status) {
		if (response && response.data && response.data.success) {
			let message = wp.i18n.sprintf(updraftplus_onboarding.connected, updraftplus_onboarding.remote_storages[methodLabel]);

			setAlertState(groupId, {
				responseMessage: message,
				responseSuccess: true,
				responseCode: 'success',
				isUpdating: false,
			});
			if (setValue) {
				setValue(`${groupId}_completed`, true);
				console.log(`[${methodLabel}] Calling setValue for ${groupId}_completed to true`);
			}
		} else {
			response.output = response.output.replaceAll('&quot;', '"');
			setAlertState(groupId, {
				responseMessage: wp.i18n.sprintf(updraftlion.settings_test_result, updraftplus_onboarding.remote_storages[methodLabel]) + ' ' + response.output,
				responseSuccess: false,
				responseCode: 'danger',
				isUpdating: false,
			});
			if (setValue) {
				setValue(`${groupId}_completed`, false);
				console.log(`[${methodLabel}] Calling setValue for ${groupId}_completed to false (failure)`);
			}
		}
	};

	const errorCallback = function(response, status, error_code, resp) {
		let errorMessage = wp.i18n.sprintf(updraftlion.settings_test_result, updraftplus_onboarding.remote_storages[methodLabel]);

		if (typeof resp !== 'undefined' && resp.hasOwnProperty('fatal_error')) {
			errorMessage = resp.fatal_error_message;
			console.error(resp.fatal_error_message);
		} else if (response && response.output) {
			errorMessage = response.output.replaceAll('&quot;', '"');
		} else {
			errorMessage = `updraft_send_command: error: ${status} (${error_code})`;
			console.log(errorMessage);
		}
		setAlertState(groupId, {
			responseMessage: errorMessage,
			responseSuccess: false,
			responseCode: 'danger',
			isUpdating: false,
		});
		if (setValue) {
			setValue(`${groupId}_completed`, false);
			console.log(`[${methodLabel}] Calling setValue for ${groupId}_completed to false (error)`);
		}
	};

	return { successCallback, errorCallback };
};

/**
 * Generic function to test connections to various remote storage destinations.
 *
 * @param {object} field The definition object of the clicked button field.
 * @param {function} setAlertState Function to update connection status in Zustand store.
 * @param {string} methodLabel A user-friendly label for the storage method (e.g., 'Backblaze', 'Azure').
 * @param {object} dataPayload The complete data payload to send to the backend.
 * @param {function} setValue Function to update a field's value in Zustand for completion status.
 */
window.pluginOnboardingActions.testRemoteStorageConnection = async function(
	field,
	setAlertState,
	methodLabel,
	dataPayload,
	setValue
) {
	console.log(`[${methodLabel}] Initiating remote storage connection test...`);

	// Use field.group_id if available, otherwise fallback to field.id
	const groupId = field.group_id || field.id;
	const message = wp.i18n.sprintf(updraftplus_onboarding.testing_remote_storage, updraftplus_onboarding.remote_storages[methodLabel]);
	setAlertState(groupId, {
		isUpdating: true,
		responseSuccess: false,
		responseCode: 'loading',
		responseMessage: message,
	});

	// Use the utility function to create callbacks
	const { successCallback, errorCallback } = window.pluginOnboardingActions.createTestConnectionCallbacks({
		groupId,
		methodLabel: methodLabel,
		setAlertState,
		setValue
	});

	window.updraft_send_command(
		'test_storage_settings',
		dataPayload,
		successCallback,
		{ error_callback: errorCallback }
	);
};

/**
 * Function to transform data for Backblaze connection.
 * Splits the full backup path into bucket name and backup path.
 *
 * @param {object} data The raw form data for Backblaze.
 * @returns {object} The transformed data.
 */
function updraftDataForBackblaze(data) {
	const fullPathValue = data.bucket_name || ''; // Ensure it's a string to prevent indexOf error

	let bucketName = '';
	let backupPath = '';

	const firstSlashIndex = fullPathValue.indexOf('/');
	if (firstSlashIndex !== -1) {
		bucketName = fullPathValue.substring(0, firstSlashIndex);
		backupPath = fullPathValue.substring(firstSlashIndex + 1);
	} else {
		bucketName = fullPathValue;
		backupPath = '';
	}

	data.bucket_name = bucketName;
	data.backup_path = backupPath;

	return data;
}

// Register dataForBackblaze as a filter
window.pluginOnboardingActions.addFilter('dataForBackblaze', updraftDataForBackblaze);

/**
 * Function to transform data for FTP connection.
 * Converts the boolean 'passive' value to an integer (1 or 0).
 *
 * @param {object} data The raw form data for FTP.
 * @returns {object} The transformed data.
 */
function updraftDataForFtp(data) {
	if (typeof data.passive === 'boolean') {
		data.passive = data.passive ? 1 : 0;
	}
	return data;
}

// Register dataForFtp as a filter
window.pluginOnboardingActions.addFilter('dataForFtp', updraftDataForFtp);

/**
 * Function to transform data for WebDAV connection.
 * Combines individual fields into a single 'url' parameter.
 *
 * @param {object} data The raw form data for WebDAV.
 * @returns {object} The transformed data with a single 'url' field.
 */
function updraftDataForWebdav(data) {
	const protocol = data.protocol || 'webdav://';
	const user = data.user || '';
	const pass = data.pass || '';
	const host = data.host || '';
	const port = data.port; // Can be 0, empty string, or a number
	const path = data.path || '';
	data.enable_chunk = data.enable_chunk ? 1 : 0;
	data.webdav = protocol;

	let credentials = '';
	if (user && pass) {
		credentials = `${user}:${pass}@`;
	} else if (user) {
		credentials = `${user}@`;
	}

	let portString = '';
	if (port) {
		const defaultWebdavPort = (protocol === 'webdavs://') ? 443 : 80;
		if (parseInt(port, 10) !== defaultWebdavPort) {
			portString = `:${port}`;
		}
	}

	// Ensure path starts with a slash if it's not empty
	const formattedPath = path.startsWith('/') || !path ? path : `/${path}`;

	data.url = `${protocol}${credentials}${host}${portString}${formattedPath}`;

	// Return an object with the constructed URL and other relevant fields
	return data;
}

// Register dataForWebdav as a filter
window.pluginOnboardingActions.addFilter('dataForWebdav', updraftDataForWebdav);

/**
 * Generic function to test connections to various remote storage destinations.
 * Gathers data dynamically and then calls the generic `testRemoteStorageConnection` function.
 *
 * @param {object} field The definition object of the clicked button field.
 * @param {Array<object>} settings The entire form settings array from Zustand.
 * @param {function} setAlertState Function to update connection status in Zustand store.
 * @param {function} setValue Function to update a field's value in Zustand for completion status.
 */
window.pluginOnboardingActions.testConnection = async function(
	field,
	settings,
	setAlertState,
	setValue
) {
	// Use field.group_id if available, otherwise fallback to field.id
	const groupId = field.group_id || field.id;
	const methodLabel = field.method_label || groupId;

	let formData = {};
	// Collect relevant settings for the current destination
	settings.forEach(function(setting) {
		if (setting.id.indexOf(groupId + '_') === 0) {
			const key = setting.id.substring((groupId + '_').length);
			formData[key] = (setting && typeof setting.value !== 'undefined') ? setting.value : '';
		}
	});

	// Apply specific data transformations based on groupId using filters
	const filterTag = `dataFor${groupId.charAt(0).toUpperCase() + groupId.slice(1)}`;
	formData = window.pluginOnboardingActions.applyFilters(filterTag, formData);

	// Prepare payload
	const dataForUpdraftCommand = Object.assign({}, formData, {
		useservercerts: 0,
		disableverify: 0,
		nossl: 0,
		method: groupId
	});

	await window.pluginOnboardingActions.testRemoteStorageConnection(
		field,
		setAlertState,
		methodLabel,
		dataForUpdraftCommand,
		setValue
	);
};

/**
 * Function to handle OAuth connections for remote storage.
 * This will trigger a click on the existing authentication button for the specified destination.
 *
 * @param {object} field The definition object of the clicked button field.
 * @param {Array<object>} settings The entire form settings array from Zustand.
 * @param {function} setAlertState Function to update connection status in Zustand store.
 * @param {function} setValue Function to update a field's value in Zustand.
 */
window.pluginOnboardingActions.oauth = async function(
	field,
	settings,
	setAlertState,
	setValue
) {
	// Use field.group_id if available, otherwise fallback to field.id
	const groupId = field.group_id || field.id;
	const methodLabel = field.method_label || groupId;
	let message = wp.i18n.sprintf(updraftplus_onboarding.oauth_pre_connection, updraftplus_onboarding.remote_storages[methodLabel]);

	setAlertState(groupId, {
		isUpdating: true,
		responseSuccess: true, // Optimistic, will be updated
		responseCode: 'loading',
		responseMessage: message,
	});

	let authSuccessReceived = false;

	// Promise to wait for the 'auth_success' message from the popup
	const waitForAuthSuccess = new Promise(function(resolve, reject) {
		const messageHandler = function(event) {
			if (event.origin !== window.location.origin && !event.origin.includes('updraftplus.com')) {
				return;
			}

			if (event.data && event.data.type === 'auth_success') {
				authSuccessReceived = true;
				window.removeEventListener('message', messageHandler);
				resolve();
			}
		};
		window.addEventListener('message', messageHandler);

		// Also handle popup closure as a potential failure if auth_success isn't received
		const popupClosedHandler = function() {
			window.removeEventListener('updraftAuthPopupClosed', popupClosedHandler);
			if (!authSuccessReceived) {
				window.removeEventListener('message', messageHandler); // Clean up message listener too
				reject(new Error('OAuth popup closed without successful authentication.'));
			}
		};
		window.addEventListener('updraftAuthPopupClosed', popupClosedHandler);
	});

	// Trigger a click on the authentication button for the specific destination
	jQuery(`.updraftplusmethod.${groupId} .updraft_authlink[data-remote_method="${groupId}"]`)?.trigger('click', {
		is_requesting_popup_auth: true
	});

	try {
		await waitForAuthSuccess; // Wait for the auth_success message

		// If we reach here, auth_success was received
		setAlertState(groupId, {
			isUpdating: false,
			responseSuccess: true,
			responseCode: 'success',
			responseMessage: wp.i18n.sprintf(updraftplus_onboarding.connected, updraftplus_onboarding.remote_storages[methodLabel]),
		});
		setValue(`${groupId}_completed`, true);
	} catch (error) {
		// If we reach here, the popup was closed without auth_success
		console.error(`OAuth failed for ${methodLabel}:`, error.message);
		setAlertState(groupId, {
			isUpdating: false,
			responseSuccess: false,
			responseCode: 'danger',
			responseMessage: wp.i18n.sprintf(updraftplus_onboarding.remote_storage_not_connected, updraftplus_onboarding.remote_storages[methodLabel]),
		});
		setValue(`${groupId}_completed`, false);
	}
};

/**
 * Handles the 'Save and continue' action for remote storage settings.
 * This function will iterate through selected groups and trigger their connection tests.
 *
 * @param {object} currentStep The current step object.
 * @param {Array<object>} settings The entire form settings array from Zustand.
 * @param {function} setAlertState Function to update connection status in Zustand store.
 * @param {function} setValue Function to update a field's value in Zustand for completion status.
 * @returns {Promise<{success: boolean, message: string}>} Result of the operation.
 */
window.pluginOnboardingActions.saveAndContinueRemoteStorage = async function(
	currentStep,
	settings,
	setAlertState,
	setValue
) {
	const completedGroups = {};
	let foundCompletedGroup = false;

	// Get selected destinations from settings
	let selectedDestinations = updraftGetSettingValue(settings, 'selected_destinations', []);

	// If selected_destinations is empty, try to get from single_group
	if (!Array.isArray(selectedDestinations)) {
		selectedDestinations = [selectedDestinations];
	}

	if (selectedDestinations.length === 0) {
		console.error('No selected remote storage group found. Returning error.');
		return { success: false };
	}

	for (const groupId of selectedDestinations) {
		const completionFieldId = `${groupId}_completed`;
		const isCompleted = updraftGetSettingValue(settings, completionFieldId, false);

		if (isCompleted) {
			foundCompletedGroup = true;
			let groupSettings = {};
			settings.forEach(function(setting) {
				if (setting.id.indexOf(groupId + '_') === 0 && setting.id !== completionFieldId) {
					const key = setting.id.substring((groupId + '_').length);
					groupSettings[key] = (setting && typeof setting.value !== 'undefined') ? setting.value : '';
				}
			});


			// Apply specific data transformations based on groupId using filters
			const filterTag = `dataFor${groupId.charAt(0).toUpperCase() + groupId.slice(1)}`;
			groupSettings = window.pluginOnboardingActions.applyFilters(filterTag, groupSettings);
			console.log(`Collected settings for ${groupId} after filter ${filterTag}:`, groupSettings);

			completedGroups[groupId] = groupSettings;
		}
	}

	if (!foundCompletedGroup) {
		console.error('No completed remote storage group found. Returning error.');
		return { success: false };
	}

	window.updraft_send_command(
		'update_backup_and_storage_settings',
		{ current_step: currentStep.id, remote_storages: completedGroups },
		null
	);

	return { success: true };
};

/**
 * Handles the 'Save and continue' action for backup settings.
 *
 * @param {object} currentStep The current step object.
 * @param {Array<object>} settings The entire form settings array from Zustand.
 * @param {function} setAlertState Function to update connection status in Zustand store.
 * @param {function} setValue Function to update a field's value in Zustand for completion status.
 * @returns {Promise<{success: boolean, message: string}>} Result of the operation.
 */
window.pluginOnboardingActions.saveAndContinueBackupSettings = async function(
	currentStep,
	settings,
	setAlertState,
	setValue
) {
	const backupFrequency = updraftGetSettingValue(settings, 'backup_frequency');
	const keepLastBackups = updraftGetSettingValue(settings, 'keep_last_backups');

	const backupSettings = {
		backup_frequency: backupFrequency,
		keep_last_backups: keepLastBackups,
	};

	console.log('Backup Settings:', backupSettings);

	window.updraft_send_command(
		'update_backup_and_storage_settings',
		{ current_step: currentStep.id, backup_settings: backupSettings },
		null
	);

	return { success: true };
};

/**
 * Promise-based wrapper for window.updraft_send_command.
 *
 * @param {string} command - The command name sent to Updraft.
 * @param {Object} payload - The payload data sent with the command.
 * @returns {Promise<{
 *   success: boolean,
 *   response?: Object,
 *   status?: string,
 *   error_code?: string
 * }>} The command execution result.
 */
const updraftSendCommandWithPromise = async function(command, payload) {
	return new Promise(function (resolve) {
		window.updraft_send_command(
			command,
			payload,
			function (response) {
				resolve({ success: true, response });
			},
			{
				error_callback: function (response, status, error_code) {
					resolve({
						success: false,
						response,
						status,
						error_code,
					});
				},
			}
		);
	});
}

/**
 * Handles UpdraftVault connection or quota refresh,
 * including UI state updates, form value updates, and onboarding global state.
 *
 * @param {string} command - The command name sent to Updraft.
 * @param {Object} payload - The payload data for the command.
 * @param {string} groupId - Alert/state group ID to be updated.
 * @param {string} methodLabel - The method label (e.g., "UpdraftVault").
 * @param {Function} setAlertState - Setter for the alert UI state.
 * @param {Function} setValue - Setter for form values.
 * @param {boolean} isConnect - Indicates whether this is an initial connection or a quota refresh.
 * @returns {Promise<{
 *   success: boolean,
 *   message: string
 * }>} The final status of the process.
 */
async function handleUpdraftVaultConnection(
	command,
	payload,
	groupId,
	methodLabel,
	setAlertState,
	setValue,
	isConnect
) {
	let message = isConnect ? updraftplus_onboarding.connecting : updraftplus_onboarding.refreshing;
	message = wp.i18n.sprintf(message, 'UpdraftVault');

	if (typeof setAlertState === 'function') {
		setAlertState(groupId, {
			isUpdating: true,
			responseSuccess: false,
			responseCode: 'loading',
			responseMessage: message,
		});
	}

	const result = await updraftSendCommandWithPromise(command, payload);

	if (result.success && result.response && result.response.connected) {
		const response = result.response;
		let emailDisplay = '';
		let quotaDisplay = '';

		if (response && typeof response === 'object') {
			emailDisplay = payload.email;
			if (response.quota) {
				quotaDisplay = response.quota;
			}
		}

		if (emailDisplay) {
			setValue('updraftvault_email_display', emailDisplay);
		}
		if (quotaDisplay) {
			setValue('updraftvault_quota_display', quotaDisplay);
		}
		if (isConnect) {
			setValue('updraftvault_completed', true);
		}

		if (typeof setAlertState === 'function') {
			setAlertState(groupId, {
				isUpdating: false,
				responseSuccess: true,
				responseCode: false,
				responseMessage: '',
			});
		}

		return { success: true, message: '' };
	}

	if (result.success) {
		if (isConnect) {
			message = updraftplus_onboarding.remote_storage_not_connected;
		} else {
			message = updraftplus_onboarding.cannot_refresh_updraftvault;
		}
	} else {
		if (isConnect) {
			message = updraftplus_onboarding.connection_error;
		} else {
			message = updraftplus_onboarding.refresh_error;
		}

		message += result.status + ' (' + result.error_code + ')';
	}

	message = wp.i18n.sprintf(message, 'UpdraftVault');

	if (typeof setAlertState === 'function') {
		setAlertState(groupId, {
			isUpdating: false,
			responseSuccess: false,
			responseCode: 'danger',
			responseMessage: message,
		});
	}

	if (isConnect) {
		setValue('updraftvault_completed', false);
	}

	return { success: false };
}

/**
 * Handles the connect to UpdraftVault from an onboarding step.
 *
 * @param {object} field The current step object.
 * @param {Array<object>} settings The entire form settings array from Zustand.
 * @param {function} setAlertState Function to update connection status in Zustand store.
 * @param {function} setValue Function to update a field's value in Zustand for completion status.
 * @returns {Promise<{success: boolean, message: string}>} Result of the operation.
 */
window.pluginOnboardingActions.connectUpdraftVault = async function(
	field,
	settings,
	setAlertState,
	setValue
) {
	const groupId = field.group_id || field.id || 'updraftvault';
	const methodLabel = field.method_label || 'UpdraftVault';

	const email = updraftGetSettingValue(settings, 'updraftvault_email', '');
	const password = updraftGetSettingValue(settings, 'updraftvault_password', '');

	if (!email || !updraftIsValidEmail(email)) {
		setAlertState(groupId, {
			isUpdating: false,
			responseSuccess: false,
			responseCode: 'danger',
			responseMessage: updraftplus_onboarding.email_not_valid,
		});
		setValue('updraftvault_completed', false);
		return { success: false };
	}

	if (!password) {
		setAlertState(groupId, {
			isUpdating: false,
			responseSuccess: false,
			responseCode: 'danger',
			responseMessage: updraftplus_onboarding.password_cannot_empty,
		});
		setValue('updraftvault_completed', false);
		return { success: false };
	}

	return handleUpdraftVaultConnection(
		'vault_connect',
		{
			email: email,
			pass: password,
			return_data_only: true
		},
		groupId,
		methodLabel,
		setAlertState,
		setValue,
		true
	);
};

/**
 * Handles the recount quota for UpdraftVault from an onboarding step.
 *
 * @param {object} field The current step object.
 * @param {Array<object>} settings The entire form settings array from Zustand.
 * @param {function} setAlertState Function to update connection status in Zustand store.
 * @param {function} setValue Function to update a field's value in Zustand for completion status.
 * @returns {Promise<{success: boolean, message: string}>} Result of the operation.
 */
window.pluginOnboardingActions.recountQuotaUpdraftVault = async function(
	field,
	settings,
	setAlertState,
	setValue
) {
	const groupId = 'updraftvault_connected';
	const methodLabel = field.method_label || 'UpdraftVault';

	return handleUpdraftVaultConnection(
		'vault_recountquota',
		{
			return_data_only: true
		},
		groupId,
		methodLabel,
		setAlertState,
		setValue,
		false
	);
};

/**
 * Handles the disconnect to UpdraftVault from an onboarding step.
 *
 * @param {object} field The current step object.
 * @param {Array<object>} settings The entire form settings array from Zustand.
 * @param {function} setAlertState Function to update connection status in Zustand store.
 * @param {function} setValue Function to update a field's value in Zustand for completion status.
 * @returns {Promise<{success: boolean, message: string}>} Result of the operation.
 */
window.pluginOnboardingActions.disconnectUpdraftVault = function(
	field,
	settings,
	setAlertState,
	setValue
) {
	const groupId = 'updraftvault_connected';
	const methodLabel = field.method_label || 'UpdraftVault';

	try {
		setValue('updraftvault_completed', false);
		return handleUpdraftVaultConnection(
			'vault_disconnect',
			{
				return_data_only: true
			},
			groupId,
			methodLabel,
			setAlertState,
			setValue,
			false
		);
	} catch (e) {
		console.error('Error in disconnectUpdraftVault:', e);
		return { success: false };
	}
};
