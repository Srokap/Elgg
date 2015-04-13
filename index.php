<?php
/**
 * Elgg front controller entry point
 *
 * @package Elgg
 * @subpackage Core
 */

$autoloader = (require_once __DIR__ . '/autoloader.php');

$app = new \Elgg\Application();

return $app->run();
