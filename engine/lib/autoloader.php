<?php
/**
 * Elgg autoloader
 * Facilities for class/interface/trait autoloading.
 *
 * @package    Elgg.Core
 * @subpackage Autoloader
 */

/**
 * @param \Elgg\Application $app Elgg Application object used for initializing the service provider (required on
 *                               first call, then ignored)
 * @return \Elgg\Di\ServiceProvider
 * @access private
 */
function _elgg_services(\Elgg\Application $app = null) {
	static $provider;
	if (null === $provider) {
		$provider = new \Elgg\Di\ServiceProvider($app);
	}
	return $provider;
}

/**
 * Delete the autoload system cache
 *
 * @access private
 */
function _elgg_delete_autoload_cache() {
	_elgg_services()->autoloadManager->deleteCache();
}

/**
 * Get Elgg's class loader
 *
 * @return \Elgg\ClassLoader
 */
function elgg_get_class_loader() {
	return _elgg_services()->classLoader;
}

/**
 * Register a directory tree for autoloading classes/interfaces/traits.
 *
 * For BC with 1.8, all .php files in the top-level directory are scanned
 * and added to the class map (only on the first request), then lower-level
 * directories are registered for standard PSR-0 autoloading.
 *
 * @param string $dir The dir to look in
 *
 * @return void
 * @since 1.8.0
 */
function elgg_register_classes($dir) {
	_elgg_services()->autoloadManager->addClasses($dir);
}

/**
 * Register a classname to a file.
 *
 * @param string $class    The name of the class
 * @param string $location The location of the file
 *
 * @return bool true
 * @since 1.8.0
 */
function elgg_register_class($class, $location) {
	_elgg_services()->autoloadManager->setClassPath($class, $location);
	return true;
}

return function(\Elgg\EventsService $events, \Elgg\HooksRegistrationService $hooks) {
	$events->registerHandler('upgrade', 'all', '_elgg_delete_autoload_cache');
};
