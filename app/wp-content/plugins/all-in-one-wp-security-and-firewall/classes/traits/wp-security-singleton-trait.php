<?php
if (!defined('ABSPATH')) die('Access denied.');

if (trait_exists('AIOWPSecurity_Singleton_Trait')) return;

trait AIOWPSecurity_Singleton_Trait {

	/**
	 * Holds instances of classes using this trait, keyed by class name.
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object
	 */
	public static function get_instance() {
		$class = get_called_class();
		if (!isset(self::$instances[$class])) {
			self::$instances[$class] = new static();
		}
		return self::$instances[$class];
	}
}
