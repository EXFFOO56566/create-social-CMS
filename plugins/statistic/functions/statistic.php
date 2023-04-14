<?php
function statistic_get_site() {
	$statistics = array(
		'members' => array(
			'count' => count_table_rows('users'),
			'title' => lang('members'),
			'icon' => 'ion-ios-people-outline',
			'link' => url_to_pager('admin-members-list'),
		),
		'online-members' => array(
			'count' => count_online_members(),
			'title' => lang('online-members'),
			'icon' => 'ion-ios-people',
			'link' => url_to_pager('admin-members-list').'?filter=online',
		),
	);
	$statistics = fire_hook('admin.statistics', $statistics);
	$excludes = fire_hook('site.statistics.excludes', array('comments', 'feedback', 'pendinglistings', 'suscribers', 'pendingpolls', 'pendingproperties'));
	foreach($excludes as $exclude) {
		if(isset($statistics[$exclude])) {
			unset($statistics[$exclude]);
		}
	}
	$links = fire_hook('site.statistics.links', array(
		'members' => url('people'),
		'online-members' => url('people'),
		'blogs' => url('blogs'),
		'events' => url('events'),
		'feeds' => url('feed'),
		'forum' => url('forum'),
		'games' => url('games'),
		'giftshop' => url('giftshop'),
		'groups' => url('groups'),
		'marketplace' => url('marketplace'),
		'pages' => url('pages'),
		'poll' => url('polls'),
		'property' => url('properties'),
		'question' => url('questions'),
		'video' => url('videos'),
	));
	foreach($links as $id => $link) {
		if(isset($statistics[$id])) {
			$site_statistics[$id] = $statistics[$id];
			$site_statistics[$id]['link'] = $link;
		}
	}
	return $site_statistics;
}

function statistic_get_ip() {
	$ip = server('REMOTE_ADDR');
	$private = preg_match('/^192\.168/', $ip);
	$valid = ip2long($ip) !== false;
	if(!$valid || $private) {
		try {
			$response = @file_get_contents('https://api.ipify.org/?format=json');
			$ip_info = json_decode($response);
			$ip = isset($ip_info->ip) ? $ip_info->ip : false;
		} catch(Exception $e) {
			$ip = false;
		}
	}
	return $ip;
}

function statistic_get_ip_info($ip = null) {
	$ip = isset($ip) ? $ip : statistic_get_ip();
	try {
		$response = @file_get_contents('http://ip-api.com/json/'.$ip);
		$ip_info = json_decode($response);
	} catch(Exception $e) {
		exit($e->getMessage());
		$ip_info = false;
	}
	return $ip_info;
}

function statistic_get_OS() {
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	$OS_platform = "Unknown OS Platform";
	$OS_array = array(
		'/windows nt 10/i' => 'Windows 10',
		'/windows nt 6.3/i' => 'Windows 8.1',
		'/windows nt 6.2/i' => 'Windows 8',
		'/windows nt 6.1/i' => 'Windows 7',
		'/windows nt 6.0/i' => 'Windows Vista',
		'/windows nt 5.2/i' => 'Windows Server 2003/XP x64',
		'/windows nt 5.1/i' => 'Windows XP',
		'/windows xp/i' => 'Windows XP',
		'/windows nt 5.0/i' => 'Windows 2000',
		'/windows me/i' => 'Windows ME',
		'/win98/i' => 'Windows 98',
		'/win95/i' => 'Windows 95',
		'/win16/i' => 'Windows 3.11',
		'/macintosh|mac os x/i' => 'Mac OS X',
		'/mac_powerpc/i' => 'Mac OS 9',
		'/linux/i' => 'Linux',
		'/ubuntu/i' => 'Ubuntu',
		'/iphone/i' => 'iPhone',
		'/ipod/i' => 'iPod',
		'/ipad/i' => 'iPad',
		'/android/i' => 'Android',
		'/blackberry/i' => 'BlackBerry',
		'/webos/i' => 'Mobile'
	);
	foreach($OS_array as $regex => $value) {
		if(preg_match($regex, $user_agent)) {
			$OS_platform = $value;
		}
	}
	return $OS_platform;
}

function statistic_get_browser() {
	$user_agent = $_SERVER['HTTP_USER_AGENT'];
	$browser = "Unknown Browser";
	$browser_array = array(
		'/msie/i' => 'Internet Explorer',
		'/firefox/i' => 'Firefox',
		'/safari/i' => 'Safari',
		'/chrome/i' => 'Chrome',
		'/edge/i' => 'Edge',
		'/opera/i' => 'Opera',
		'/netscape/i' => 'Netscape',
		'/maxthon/i' => 'Maxthon',
		'/konqueror/i' => 'Konqueror',
		'/mobile/i' => 'Handheld Browser'
	);
	foreach($browser_array as $regex => $value) {
		if(preg_match($regex, $user_agent)) {
			$browser = $value;
		}
	}
	return $browser;
}