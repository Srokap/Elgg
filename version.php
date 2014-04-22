<?php
/**
 * Elgg version number.
 * This file determines the current version of the core Elgg code being used.
 * This is compared against the values stored in the database to determine
 * whether upgrades should be performed. Version string is in format YYYYMMDDXX
 * where YYYYMMDD stands for release date and XX is an interim incrementer.
 * It reads the data from composer.json configuration file and uses version field
 * as release string and elgg-version set in extra data.
 *
 * @see        https://getcomposer.org/doc/04-schema.md#extra
 * @package    Elgg
 * @subpackage Core
 */

$composerJson = file_get_contents(dirname(__FILE__) . "/composer.json");
if ($composerJson === false) {
	throw new Exception("Unable to read composer.json file!");
}

$composer = json_decode($composerJson);
if ($composer === null) {
	throw new Exception("JSON parse error while reading composer.json!");
}

// Human-friendly version name
if (!isset($composer->version)) {
	throw new Exception("Version field must be set in composer.json!");
}
$release = $composer->version;

// YYYYMMDD = Elgg Date
// XX = Interim incrementer
if (!isset($composer->extra) || !isset($composer->extra->{'elgg-version'})) {
	throw new Exception("Extra field 'elgg-version' must be set in composer.json!");
}
$version = $composer->extra->{'elgg-version'};
