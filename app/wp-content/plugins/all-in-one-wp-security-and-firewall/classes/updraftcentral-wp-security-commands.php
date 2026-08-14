<?php
if (!defined('ABSPATH')) die('Access denied.');
/**
 * This is a small glue class, which makes available all the commands in AIOWPSecurity_Commands, and translates the response from AIOWPSecurity_Commands (which is either data to return, or a WP_Error) into the format used by UpdraftCentral.
 */
class UpdraftCentral_WP_Security_Commands extends UpdraftCentral_Commands {

	private $commands;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->commands = new AIOWPSecurity_Commands();
	}

	/**
	 * Magic method to pass on the command to AIOWPSecurity_Commands
	 *
	 * @param string $name		- command name
	 * @param array	 $arguments	- command parameters
	 *
	 * @return array - response
	 */
	public function __call($name, $arguments) {
		if (!is_callable(array($this->commands, $name))) {
			return $this->_generic_error_response('aios_no_such_command', $name);
		}

		$result = call_user_func_array(array($this->commands, $name), $arguments);

		if (is_wp_error($result)) {
			return $this->_generic_error_response($result->get_error_code(), $result->get_error_data());
		} else {
			return $this->_response($result);
		}
	}
}
