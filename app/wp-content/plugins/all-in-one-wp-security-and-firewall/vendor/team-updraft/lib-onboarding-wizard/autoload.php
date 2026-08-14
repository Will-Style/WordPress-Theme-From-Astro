<?php

spl_autoload_register(function ($class) {

	$prefix = 'Updraftplus\All_In_One_Wp_Security_And_Firewall\\';
	
	// Only handle classes with namespace starting with prefix.
	if (strpos($class, $prefix) !== 0) {
		return;
	}
	
	// Remove the namespace prefix
	$relative_class = substr($class, strlen($prefix));
	$relative_path = str_replace('\\', DIRECTORY_SEPARATOR, $relative_class) . '.php';
	$file = __DIR__ . DIRECTORY_SEPARATOR . $relative_path;
	
	// Require the file if it exists.
	if (file_exists($file)) {
		require_once $file;
	}
});
