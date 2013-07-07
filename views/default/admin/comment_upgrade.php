<?php

// turn off time limit
set_time_limit(0);

elgg_load_js('elgg.upgrades');

$access_status = access_get_show_hidden_status();
access_show_hidden_entities(true);

$options = array(
	'annotation_names' => 'generic_comment',
	'limit' => false,
);

$options['count'] = true;
$count = elgg_get_annotations($options);
unset($options['count']);

$status_string = elgg_echo('upgrade:comments:status', array($count));

echo "<p>$status_string</p>
<span id=\"comment-upgrade-total\" class=\"hidden\">$count</span>
<span id=\"comment-upgrade-count\" class=\"hidden\">0</span>";

if ($count) {
	if ($count > 1000) {
		$warning_string = elgg_echo('upgrade:comments:warning');
		echo "<p>$warning_string</p>";
	}

	$success_count_string = elgg_echo('upgrade:comments:success_count');
	$error_count_string = elgg_echo('upgrade:comments:error_count');
	$speed_description_string = elgg_echo('upgrade:comments:upgrade_speed');

	echo <<<HTML
		<div class="elgg-progressbar mvl"><span class="elgg-progressbar-counter" id="comment-upgrade-counter">0%</span></div>
		<ul class="mvl">
			<li>$success_count_string <span id="comment-upgrade-success-count">0</span></li>
			<li>$error_count_string <span id="comment-upgrade-error-count">0</span></li>
			<li>$speed_description_string <span id="comment-upgrade-speed"></span></li>
		</ul>
		<ul class="mvl" id="comment-upgrade-messages"></ul>
HTML;

	echo elgg_view('output/url', array(
		'text' => elgg_echo('upgrade'),
		'href' => 'action/admin/site/comment_upgrade',
		'class' => 'elgg-button elgg-button-action mtl',
		'is_action' => true,
		'id' => 'comment-upgrade-run',
	));
	echo '<div id="comment-upgrade-spinner" class="elgg-ajax-loader hidden"></div>';
}

access_show_hidden_entities($access_status);