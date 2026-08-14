<?php

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

/**
 * LibOnboardingWizard Composer Plugin.
 *
 * When the lib-onboarding-wizard package is installed, this plugin:
 * - Replaces all the Updraftplus\All_In_One_Wp_Security_And_Firewall with the extracted namespace from composer package.
 * - Deletes itself and composer-plugin-extras folder after execution.
 */
class LibOnboardingWizard implements PluginInterface {
	private static $namespace_placeholder =  'Updraftplus\All_In_One_Wp_Security_And_Firewall';
	
	private static $textdomain_placeholder = 'all-in-one-wp-security-and-firewall';
	
	/**
	 * This function converts the package name from composer.json in to valid php namespace.
	 * @param $package_name string The package name obtained from composer.json
	 * @return string
	 */
	private static function get_php_namespace($package_name) {
		$parts = explode('/', $package_name); // Split vendor and package
		$namespaceParts = array_map(function ($part) {
			$clean = preg_replace('/[^a-zA-Z0-9_]/', '_', $part); // Remove everything except letters, numbers, and underscores
			$segments = preg_split('/_+/', $clean);              // Split by underscores
			$segments = array_map('ucfirst', $segments);          // Capitalize each segment
			return implode('_', $segments);                       // Join back with underscore
		}, $parts);
		return implode('\\', $namespaceParts); // Combine with backslash to form final namespace
	}
	
	/**
	 * Activates the Composer plugin.
	 *
	 * This method is called by Composer when the plugin is first activated.
	 * It can be used to perform initialization, register event subscribers,
	 * or modify the Composer environment dynamically.
	 *
	 * @param Composer $composer The Composer instance giving access to configuration, repositories, and more.
	 * @param IOInterface $io The IO interface for input/output operations, allowing interaction with the user.
	 *
	 * @return void
	 */
	public function activate(Composer $composer, IOInterface $io) {
        $this->remove_tests_directory($composer, $io);
        $this->replace_namespace($composer, $io);
		$this->replace_textdomain($composer, $io);
	}
	/**
	 * Deactivates the Composer plugin.
	 *
	 * This method is called when the plugin is deactivated during the Composer lifecycle.
	 * Although this implementation does nothing, the method must be defined to fulfill
	 * the interface or abstract class requirements.
	 *
	 * @param Composer $composer The Composer instance.
	 * @param IOInterface $io The IO interface for input/output operations.
	 *
	 * @return void
	 */
	public function deactivate(Composer $composer, IOInterface $io) {
		// do nothing, empty body needed to extend the abstract methods.
	}
	
	/**
	 * Uninstalls the Composer plugin.
	 *
	 * This method is called when the plugin is removed from the Composer environment.
	 * It provides an opportunity to clean up any configurations or state.
	 * In this implementation, no action is performed.
	 *
	 * @param Composer $composer The Composer instance.
	 * @param IOInterface $io The IO interface for input/output operations.
	 *
	 * @return void
	 */
	public function uninstall(Composer $composer, IOInterface $io) {
		// do nothing, empty body needed to extend the abstract methods.
	}
	
	/**
	 * This method replaces the php namespace placeholder
	 * present in onboading wizard source with valid php namespace.
	 *
	 * @param Composer $composer The Composer instance.
	 * @param IOInterface $io The IO interface for input/output operations.
	 * @return void
	 */
	public function replace_namespace(Composer $composer, IOInterface $io): void {
		$php_namespace = self::get_php_namespace($composer->getPackage()->getName());
		$file_recursive_iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__)));
		foreach ($file_recursive_iterator as $file) {
			if (!$file->isFile() || $file->getExtension() !== 'php') continue;
			$path = $file->getRealPath();
			$content = file_get_contents($path);
			if (strpos($content, self::$namespace_placeholder) !== false) {
				file_put_contents($path, str_replace(self::$namespace_placeholder, $php_namespace, $content));
				$io->write(sprintf('Replaced %s namespace placeholder with %s in file: %s', self::$namespace_placeholder, $php_namespace, $path));
			}
		}
	}
	
	/**
	 * This method replaces the textdomain placeholder
	 * present in onboading wizard source with valid textdomain.
	 *
	 * @param Composer $composer The Composer instance.
	 * @param IOInterface $io The IO interface for input/output operations.
	 * @return void
	 */
	private function replace_textdomain(Composer $composer, IOInterface $io) {
		$package_name = $composer->getPackage()->getName();
		// Use the root package name, for example if its team-updraft/updraftplus
		// then the text domain would be replaced with `updraftplus`.
		$parts = explode('/', $package_name);
		$replacement = $parts[1];
		$file_recursive_iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(dirname(__DIR__)));
		foreach ($file_recursive_iterator as $file) {
			if (!$file->isFile() || !in_array($file->getExtension(), array('php', 'js'))) continue;
			$path = $file->getRealPath();
			$content = file_get_contents($path);
			if (strpos($content, self::$textdomain_placeholder) !== false) {
				file_put_contents($path, str_replace(self::$textdomain_placeholder, $replacement, $content));
				$io->write(sprintf('Replaced %s namespace placeholder with %s in file: %s', self::$textdomain_placeholder, $replacement, $path));
			}
		}
	}

    /**
     * Remove the tests directory from the package.
     *
     * @param Composer $composer The Composer instance.
     * @param IOInterface $io The IO interface for input/output operations.
     * @return void
     */
    private function remove_tests_directory(Composer $composer, IOInterface $io): void {
        $rootDir = dirname(__DIR__);
        $testsDir = $rootDir . DIRECTORY_SEPARATOR . 'tests';

        if (is_dir($testsDir)) {
            $this->delete_directory($testsDir);
            $io->write(sprintf('Removed tests directory: %s', $testsDir));
        }
    }

    /**
     * Recursively delete a directory and its contents.
     *
     * @param string $dir Path to the directory to delete.
     * @return void
     */
    private function delete_directory(string $dir): void {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }


}
