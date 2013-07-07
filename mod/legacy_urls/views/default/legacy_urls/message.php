<?php
/**
 * Redirect message
 *
 * @uses elgg_get_site_url() URL we're redirecting the user to (note: not relying on legacy site URL injection)
 */

$link = elgg_view('output/url', array(
	'text' => elgg_normalize_url(elgg_get_site_url()),
	'href' => elgg_get_site_url(),
));
$message = elgg_echo('legacy_urls:message', array($link));

echo "<h2>$message</h2>";
