<?php
function set_page($app) {
	$offset = input('offset', 0);
	$offset = number_format((float) $offset, 2, '.', '');
	list($hours, $minutes) = explode('.', $offset);
	$seconds = $hours * 60 * 60 + $minutes * 60;
	$timezone = timezone_name_from_abbr('', $seconds, 1);
	if($timezone === false) {
		$timezone = timezone_name_from_abbr('', $seconds, 0);
	}
	setcookie("timezone", $timezone, time() + 30 * 24 * 60 * 60, config('cookie_path'));
	return json_encode($timezone);
}
 