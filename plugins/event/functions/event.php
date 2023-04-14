<?php
function create_event($val) {
	/**
	 * @var $category
	 * @var $title
	 * @var $description
	 * @var $entity
	 * @var $privacy
	 * @var $location
	 * @var $address
	 * @var $start_time
	 * @var $end_time
	 */

	extract($val);
	$entity = explode('-', $entity);
	if(count($entity) == 2) {
		$entity_type = $entity[0];
		$entity_id = $entity[1];
	}
	if(!isset($entity_type) || !isset($entity_type)) {
		return false;
	}
	$user_id = get_userid();
	$time = time();

	db()->query("INSERT INTO events(user_id, entity_type, entity_id, event_title, event_desc, category_id, privacy, location, address, start_time, end_time, time) VALUES('".$user_id."', '".$entity_type."', '".$entity_id."', '".$title."', '".$description."', '".$category."', '".$privacy."', '".$location."', '".$address."', '".strtotime($start_time)."', '".strtotime($end_time)."', '".$time."')");
	$insert_id = db()->insert_id;
	fire_hook('event.create', null, $insert_id);
	fire_hook('plugins.users.category.updater', null, array('events', $val, $insert_id, 'event_id'));
	return $insert_id;
}

function save_event($val, $event_id) {
	/**
	 * @var $category
	 * @var $title
	 * @var $description
	 * @var $location
	 * @var $address
	 * @var $entity
	 * @var $start_time
	 * @var $end_time
	 */
	extract($val);

	db()->query("UPDATE events SET category_id = '".$category."', event_title = '".$title."', event_desc = '".$description."', location = '".$location."', address = '".$address."', start_time = '".strtotime($start_time)."', end_time = '".strtotime($end_time)."' WHERE event_id = '".$event_id."'");
	fire_hook('event.updated', null, array($event_id));
	fire_hook('plugins.users.category.updater', null, array('events', $val, $event_id, 'event_id'));
	return true;
}

function count_total_events() {
	$q = db()->query("SELECT event_id FROM events");
	return $q->num_rows;
}

function get_events($type, $term = null, $limit = 5, $admin = false, $category = null, $entity_type = 'user', $entity_id = null, $param = null) {
	$sql = "SELECT * FROM events ";
	$sql = fire_hook("use.different.events.query", $sql, array());
	$friends = array(0);
	$friends = fire_hook('system.relationship.method', $friends, array('event'));
	$friends[] = 0;
	$friends = implode(',', $friends);
	$entity_id = isset($entity_id) ? $entity_id : ($entity_type == 'user' ? get_userid() : null);
	switch($type) {
		case 'saved' :
			$saved = get_user_saved('event');
			$saved[] = 0;
			$saved = implode(',', $saved);
			$sql .= " WHERE event_id IN ({$saved})";
			$sql = fire_hook("more.events.query.filter", $sql, array($entity_type, $entity_id));
			$sql = fire_hook('users.category.filter', $sql, array($sql));
		break;
		case 'upcoming':
			$my_invites = implode(',', get_my_event_invites());
			$time = time();
			$privacy_sql = fire_hook('privacy.sql', ' ');
			$sql .= "WHERE end_time > ".$time." AND (".$privacy_sql.")";
			if($entity_type && $entity_id) {
				$entity_sql = "entity_type = '".$entity_type."' AND entity_id = ".$entity_id;
				$sql .= " AND (".$entity_sql.") ";
			}
			$sql = fire_hook("more.events.query.filter", $sql, array($entity_type, $entity_id));
			if($category and $category != 'all') {
				$sql .= " AND category_id='".$category."' ";
			}
			if($term) {
				$sql .= " AND (event_title LIKE '%".$term."%' OR event_desc LIKE '%".$term."%'  OR location LIKE '%".$term."%' )";
			}
			$sql = fire_hook('users.category.filter', $sql, array($sql));
			$sql .= " ORDER BY start_time ";
		break;
		case 'invite':
			$my_invites = implode(',', get_my_event_invites());
			$sql .= "WHERE event_id IN (".$my_invites.") ORDER BY start_time ";
		break;
		case 'me':
			$entity_id = get_userid();
			$sql .= "WHERE user_id = '".$entity_id."' ";
			if($category and $category != 'all') {
				$sql .= " AND category_id = '".$category."' ";
			}
			if($term) {
				$sql .= " AND (event_title LIKE '%".$term."%' OR event_desc LIKE '%".$term."%') ";
			}
			$sql .= " ORDER BY start_time ";
		break;
		case 'past':
			$my_invites = implode(',', get_my_event_invites());
			$time = time();
			$sql .= "WHERE end_time < ".$time." AND (1";
			if($entity_type == 'user') {
				$sql .= " AND (entity_type = 'user' AND ((entity_id = '".$entity_id."' OR (user_id IN (".$friends.") AND privacy = '1')) OR event_id IN (".$my_invites.") OR privacy = '6'))";
			}
			$sql = fire_hook("more.events.query.filter", $sql, array($entity_type, $entity_id));
			$sql .= ")";
			if($category and $category != 'all') {
				$sql .= " AND category_id='".$category."' ";
			}
			if($term) {
				$sql .= " AND (event_title LIKE '%".$term."%' OR event_desc LIKE '%".$term."%'  OR location LIKE '%".$term."%' )";
			}
			$sql = fire_hook('users.category.filter', $sql, array($sql));
			$sql .= " ORDER BY start_time ";
		break;
		case 'search':
			$my_invites = implode(',', get_my_event_invites());
			$sql .= "WHERE (user_id='".$entity_id."' OR (user_id IN (".$friends.") AND privacy = '1') OR event_id IN (".$my_invites.")) AND event_title LIKE '%".$term."%' ORDER BY start_time ";
		break;
		case 'admin-search':
			$sql .= "WHERE event_title LIKE '%".$term."%' ORDER BY start_time ";
		break;
		case 'select':
			if($param == 'today') {
				$sql .= "WHERE MONTH(FROM_UNIXTIME(end_time)) = MONTH(NOW()) AND DAY(FROM_UNIXTIME(end_time)) = DAY(NOW()) AND YEAR(FROM_UNIXTIME(end_time)) >= YEAR(NOW())";
			} elseif($param == 'tomorrow') {
				$sql .= "WHERE MONTH(FROM_UNIXTIME(end_time)) >= MONTH(NOW()) AND DAY(FROM_UNIXTIME(end_time)) = DAY(NOW()+ INTERVAL 1 DAY) AND YEAR(FROM_UNIXTIME(end_time)) >= YEAR(NOW())";
			} elseif($param == 'week') {
				$lastDayOfTheWeek = mktime(0, 0, 0, date("n"), date("j") - date("N") + 7);
				$dayofweek = date('N', strtotime(time()));
				$sql .= "WHERE (end_time >= '".time()."' AND end_time <= '{$lastDayOfTheWeek}')";
			} elseif($param == 'date-range') {
				$dateRange = input('daterange');
				$date = explode('-', $dateRange);
				$start = strtotime($date['0']." 12:00AM");
				$end = strtotime($date['1']." 11:59PM");
				$sql .= "WHERE (end_time >= '".$start."' AND end_time <= '{$end}')";
			}
		break;
		default:
			if(!$admin) {
				return false;
			}
		break;
	}
	return paginate($sql, $limit);
}

function event_get_today_birthdays() {
	$friends = array(0);
	$friends = fire_hook('system.relationship.method', $friends, array('event'));
	$friends[] = 0;
	$friends = implode(',', $friends);
	$cMonth = strtolower(date('F'));
	$cDay = date('j');
	$q = db()->query("SELECT id,username,first_name,last_name,birth_day,birth_month,avatar,gender FROM users WHERE (id IN ({$friends}) AND birth_month='{$cMonth}' AND birth_day = '{$cDay}' AND activated='1' AND active='1')");
	$users = fetch_all($q);
	return $users;
}

function event_get_month_birthdays() {
	$days = implode(',', event_get_days());
	$friends = get_friends();
	$friends[] = 0;
	$friends = implode(',', $friends);
	$cMonth = strtolower(date('F'));
	$today = date('j');

	$q = db()->query("SELECT id,username,first_name,last_name,birth_day,birth_month,avatar,gender FROM users WHERE (id IN ({$friends}) AND birth_month='{$cMonth}' AND birth_day IN ({$days}) AND birth_day > {$today} AND activated='1' AND active='1')");
	$users = fetch_all($q);
	return $users;
}

function event_get_comingup_birthdays() {
	$results = array();

	$days = array();
	$n = 1;
	for($i = 1; $i <= 7; $i++) {
		$day = date('j') + $i;
		$month = date('n');
		if(($day == 31 and !monthReach31($month)) or $day > 31) {
			$day = $n;
			$month = $month + 1;
			if($month > 12) {
				$month = 1;
			}
			$n++;
		}
		$days[$day] = $month;
	}
	$friends = array(0);
	$friends = fire_hook('system.relationship.method', $friends, array('event'));
	$friends[] = 0;
	$friends = implode(',', $friends);
	$sql = "SELECT id,username,first_name,last_name,birth_day,birth_month,avatar,gender FROM users WHERE (id IN ({$friends}) AND activated='1' AND active='1')";

	$sql .= "AND (";
	$ad = "";
	foreach($days as $day => $month) {
		$month = event_get_month_name($month);
		$ad .= ($ad) ? " OR (birth_day='{$day}' AND birth_month='{$month}')" : "(birth_day='{$day}' AND birth_month='{$month}')";
	}

	$sql .= $ad.')';
	//print_r($sql);
	$q = db()->query($sql);
	return fetch_all($q);
}

function monthReach31($month) {
	$months = array(1, 3, 5, 7, 8, 10, 12);
	if(in_array($month, $months)) return true;
	return false;
}

function event_get_user_months_birthdays() {
	$results = array();
	$months = array();
	$currentMonth = date('n') + 1;

	for($i = 1; $i <= 12; $i++) {
		if($currentMonth > 12) {
			$c = date('n') - 1;
			$y = 1;
			while($y <= $c) {
				$months[] = $y;
				$y++;
			}
			break;
		} else {
			$months[] = $currentMonth;
		}
		$currentMonth++;
	}
	$days = implode(',', event_get_days());
	$friends = array(0);
	$friends = fire_hook('system.relationship.method', $friends, array('event'));
	$friends[] = 0;
	$friends = implode(',', $friends);
	foreach($months as $month) {
		$monthName = event_get_month_name($month);
		$q = db()->query("SELECT id,username,first_name,last_name,birth_day,birth_month,avatar FROM users WHERE id IN ({$friends}) AND birth_month='{$monthName}' AND birth_day IN ({$days})  AND activated='1' AND active='1'");
		$users = fetch_all($q);
		if($users) $results[$month] = $users;
	}

	return $results;
}

function event_get_month_name($month) {
	$months = array(
		1 => 'january',
		2 => 'february',
		3 => 'march',
		4 => 'april',
		5 => 'may',
		6 => 'june',
		7 => 'july',
		8 => 'august',
		9 => 'september',
		10 => 'october',
		11 => 'november',
		12 => 'december'
	);
	return $months[$month];
}

function event_get_days() {
	$days = array();
	for($i = 1; $i <= 31; $i++) {
		$days[] = $i;
	}
	return $days;
}

function get_event_logo($event) {
	$event = arrange_event($event);
	if($event['event_cover_resized']) {
		return url_img($event['event_cover_resized'], 920);
	} else {
		return $event['host']['avatar'];
	}
}

function get_my_event_invites() {
	$userid = get_userid();
	$q = db()->query("SELECT event_id FROM event_invites WHERE user_id='{$userid}'");
	$a = array(0);
	while($fetch = $q->fetch_assoc()) {
		$a[] = $fetch['event_id'];
	}

	return $a;
}

function find_event($id) {
	$sql = "SELECT * FROM events WHERE event_id='{$id}'";
	$query = db()->query($sql);
	return arrange_event($query->fetch_assoc());
}

function arrange_event($event) {
	if(!$event) return false;
	$category = get_event_category($event['category_id']);
	if($category) $event['category'] = $category;
	$event = fire_hook('event.arrange', $event);
	if($event['entity_type'] == 'user') {
		$user = find_user($event['entity_id']);
		$event['host'] = array(
			'id' => $user['username'],
			'name' => get_user_name($user),
			'avatar' => get_avatar(200, $user),
			'user' => $user
		);
	} else {
		$event['host'] = fire_hook('entity.data', array(false), array($event['entity_type'], $event['entity_id']));
	}
	return $event;
}

function event_url($slug = null, $event = null) {
	return url_to_pager("event-profile", array('slug' => $event['event_id'])).'/'.$slug;
}

function get_event_date($event, $which = 'month', $no = 'M', $type = 'start') {
	$table = array('D' => '%a', 'l' => '%A', 'd' => '%d', 'j' => '%e', 'N' => '%u', 'w' => '%w', 'W' => '%W', 'M' => '%b', 'F' => '%B', 'm' => '%m', 'o' => '%G', 'y' => '%y', 'Y' => '%Y', 'H' => '%H', 'h' => '%I', 'g' => '%l', 'a' => '%P', 'A' => '%p', 'i' => '%M', 's' => '%S', 'U' => '%s', 'O' => '%z', 'T' => '%Z');
	$time = $type == 'start' ? $event['start_time'] : $event['end_time'];
	return $time ? (isset($table[$no]) ? strftime($table[$no], $time) : date($no, $time)) : false;
}

function event_get_invite_friends($term = null, $limit = 20, $offset = 0) {
	$friends = array(0);
	$friends = fire_hook('system.relationship.method', $friends, array('event'));
	$friends[] = 0;
	$friends = implode(',', $friends);

	$sql = "SELECT id,first_name,last_name,avatar,username FROM users WHERE id IN ({$friends})";

	if(!$term) {
		$invited = get_event_invited(app()->profileEvent['event_id']);
		$invited[] = 0;
		$invited = implode(',', $invited);
		$sql .= " AND id NOT IN ({$invited}) ";
	}

	if($term) $sql .= "  AND (first_name LIKE '%{$term}%' OR last_name LIKE '%{$term}%' OR username LIKE '%{$term}%' OR email_address LIKE '%{$term}%')";
	$sql .= " LIMIT {$offset},{$limit}";
	$query = db()->query($sql);
	return fetch_all($query);
}

function get_user_events($user_id = null) {
	$user_id = $user_id ? $user_id : get_userid();
	$db = db();
	$query = $db->query("SELECT event_id FROM events WHERE user_id = '".$user_id."'");
	$ids = array();
	while($row = $query->fetch_row()) {
		$ids[] = $row[0];
	}
	return $ids;
}

function get_invited_events($user_id = null) {
	$user_id = $user_id ? $user_id : get_userid();
	$db = db();
	$query = $db->query("SELECT event_id FROM event_invites WHERE user_id = '".$user_id."'");
	$ids = array();
	while($row = $query->fetch_row()) {
		$ids[] = $row[0];
	}
	return $ids;
}

function get_event_invited($id) {
	$cacheName = "event-invites-".$id;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$query = db()->query("SELECT user_id FROM event_invites WHERE event_id='{$id}'");
		$ids = array();
		while($fetch = $query->fetch_assoc()) {
			$ids[] = $fetch['user_id'];
		}

		set_cacheForever($cacheName, $ids);
		return $ids;
	}
}

function event_invite_user($eventId, $userid) {
	$invited = get_event_invited($eventId);
	if(!in_array($userid, $invited)) {
		db()->query("INSERT INTO event_invites (event_id,user_id)VALUES('{$eventId}','{$userid}')");
		forget_cache("event-invites-".$eventId);
		return true;
	}

	return false;
}

function event_rsvp($eventId, $rsvp) {
	$userid = get_userid();
	if($rsvp > 0) {
		$event = find_event($eventId);
		if($event['user_id'] != get_userid()) send_notification($event['user_id'], 'event.rsvp', $eventId, array('rsvp' => $rsvp));
	}
	if(event_already_invited($eventId, $userid)) {
		db()->query("UPDATE event_invites SET rsvp='{$rsvp}' WHERE event_id='{$eventId}' AND user_id='{$userid}'");
	} else {
		db()->query("INSERT INTO event_invites (event_id,user_id,rsvp)VALUES('{$eventId}','{$userid}','{$rsvp}')");
	}

	forget_cache("event-invites-".$eventId);
	return true;
}

function event_interested($eventId, $rsvp) {
	$userid = get_userid();
	if($rsvp == 3) {
		$event = find_event($eventId);
		if($event['user_id'] != get_userid()) send_notification($event['user_id'], 'event.rsvp', $eventId, array('rsvp' => $rsvp));
	}
	if(event_already_invited($eventId, $userid)) {
		db()->query("UPDATE event_invites SET interested='{$rsvp}' WHERE event_id='{$eventId}' AND user_id='{$userid}'");
	} else {
		db()->query("INSERT INTO event_invites (event_id,user_id,interested)VALUES('{$eventId}','{$userid}','{$rsvp}')");
	}

	forget_cache("event-invites-interested".$eventId);
	return true;
}

function user_interests($eventId) {
	$userId = get_userid();
	$query = db()->query("SELECT user_id FROM event_invites WHERE user_id = '{$userId}' AND event_id='{$eventId}' AND interested='1'");
	return $query->num_rows;
}

function count_event_invited($id) {
	return count(get_event_invited($id));
}

function count_event_going($eventId) {
	$query = db()->query("SELECT user_id FROM event_invites WHERE event_id='{$eventId}' AND rsvp='1'");
	return $query->num_rows + 1; //we added the hoster
}

function count_event_interested($eventId) {
	$query = db()->query("SELECT user_id FROM event_invites WHERE event_id='{$eventId}' AND interested='1'");
	return $query->num_rows + 1; //we added the hoster
}

function count_event_maybe($eventId) {
	$query = db()->query("SELECT user_id FROM event_invites WHERE event_id='{$eventId}' AND rsvp='2'");
	return $query->num_rows;
}

function get_event_my_rsvp($eventId) {
	$userid = get_userid();
	$query = db()->query("SELECT rsvp FROM event_invites WHERE event_id='{$eventId}' AND user_id='{$userid}'");
	if($query->num_rows) {
		$fetch = $query->fetch_assoc();
		return $fetch['rsvp'];
	}

	return 0;
}

function event_already_invited($eventId, $userid) {
	$invited = get_event_invited($eventId);

	if(in_array($userid, $invited)) {
		return true;
	}

	return false;
}

function get_event_cover($event = null, $original = true, $size = 920) {
	$default = img("images/cover.jpg");
	if(!$original and !empty($event['event_cover_resized'])) return url_img($event['event_cover_resized'], $size);
	if(!empty($event['event_cover'])) return url_img($event['event_cover'], $size);
	return ($original) ? '' : $default;
}

function get_event_details($index, $event = null) {
	$event = ($event) ? $event : app()->profileEvent;
	if(isset($event[$index])) return $event[$index];
	return false;
}

function is_event_admin($event) {
	if(!is_loggedIn()) return false;
	if(is_admin()) return true;
	if(get_userid() == $event['user_id']) return true;
	return false;
}

function can_create_event() {
	return user_has_permission('can-create-event');
}

function update_event_details($fields, $eventId) {
	$sqlFields = "";
	foreach($fields as $key => $value) {
		$value = sanitizeText($value);
		$sqlFields .= (empty($sqlFields)) ? "`{$key}`='{$value}'" : ",`{$key}`='{$value}'";
	}
	db()->query("UPDATE `events` SET {$sqlFields} WHERE `event_id`='{$eventId}'");
	fire_hook("event.updated", array($eventId));
}

function event_add_category($val) {
	$expected = array(
		'title' => '',
		'image' => ''
	);

	/**
	 * @var $title
	 * @var $desc
	 * @var $image
	 */
	extract(array_merge($expected, $val));
	$titleSlug = "event_category_".md5(time().serialize($val)).'_title';

	foreach($title as $langId => $t) {
		add_language_phrase($titleSlug, $t, $langId, 'event');
	}


	$time = time();
	$order = db()->query('SELECT id FROM event_categories');
	$order = ($order) ? $order->num_rows : 1;
	$query = db()->query("INSERT INTO `event_categories`(
            `title`,`category_order`, `icon`) VALUES(
            '{$titleSlug}','{$order}','{$image}'
            )
        ");

	return true;
}

function save_event_category($val, $category) {
	$expected = array(
		'title' => '',
		'path' => ''
	);

	/**
	 * @var $title
	 * @var $path
	 */
	extract(array_merge($expected, $val));
	$titleSlug = $category['title'];

	foreach($title as $langId => $t) {
		(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId, 'event') : add_language_phrase($titleSlug, $t, $langId, 'event');
	}
	return true;
}

function get_event_categories() {
	$query = db()->query("SELECT * FROM `event_categories` ORDER BY `category_order` ASC");
	return fetch_all($query);
}

function get_event_category($id) {
	$query = db()->query("SELECT * FROM `event_categories` WHERE `id`='{$id}'");
	return $query->fetch_assoc();
}

function delete_event_category($id, $category) {
	delete_all_language_phrase($category['title']);

	db()->query("DELETE FROM `event_categories` WHERE `id`='{$id}'");

	return true;
}

function update_event_category_order($id, $order) {
	db()->query("UPDATE `event_categories` SET `category_order`='{$order}' WHERE  `id`='{$id}'");
}

function delete_event($event) {
	//delete all rsvp
	$eventId = $event['event_id'];
	db()->query("DELETE FROM event_invites WHERE event_id='{$eventId}'");

	//delete cover images
	if($event['event_cover']) delete_file(path($event['event_cover']));
	if($event['event_cover_resized']) delete_file(path($event['event_cover_resized']));

	//now delete the event itself
	db()->query("DELETE FROM events WHERE event_id='{$eventId}'");

	delete_posts('event', $eventId);

	return true;
}

function time_duration($a, $b) {
	$difference = $b - $a;
	$second = 1;
	$minute = 60 * $second;
	$hour = 60 * $minute;
	$day = 24 * $hour;

	$ans["day"] = floor($difference / $day);
	$ans["hour"] = floor(($difference % $day) / $hour);
	$ans["minute"] = floor((($difference % $day) % $hour) / $minute);
	$ans["second"] = floor(((($difference % $day) % $hour) % $minute) / $second);

	$ds = null;
	$hs = null;
	$ms = null;
	$ss = null;
	if($ans["day"] > 1) {
		$ds = 's';
	} elseif($ans["hour"] > 1) {
		$hs = 's';
	} elseif($ans["minute"] > 1) {
		$ms = 's';
	} elseif($ans["second"] > 1) {
		$ss = 's';
	}

	if($ans["day"]) {
		$days = lang('d-day'.$hs, array('d' => $ans["day"]));
	} else {
		$days = null;
	}

	if($ans["hour"]) {
		$hours = lang('d-hour'.$hs, array('d' => $ans["hour"]));
	} else {
		$hours = null;
	}

	if($ans["minute"]) {
		$minutes = lang('d-minute'.$hs, array('d' => $ans["minute"]));
	} else {
		$minutes = null;
	}

	if($ans["second"]) {
		$seconds = lang('d-second'.$hs, array('d' => $ans["second"]));
	} else {
		$seconds = null;
	}

	$val = $days.','.$hours.','.$minutes;
	$duration = explode(",", $val);
	$duration = array_filter($duration);
	return implode(", ", $duration);
}

function get_event_subscribers($limit = 6) {
	$sql = "SELECT * FROM event_invites WHERE rsvp='1'";
	return paginate($sql, $limit);
}


function get_event_subscribersX() {
	$sql = db()->query("SELECT * FROM event_invites WHERE rsvp='1'");
	$subscribers = array();
	while($fetch = $sql->fetch_assoc()) {
		$subscribers[] = $fetch;
	}
	return $subscribers;
}

function manage_interested($interested) {
	if($interested == '0') {
		$interested = $interested." ".lang('event::no-interest');
	} elseif($interested == '1') {
		$interested = $interested." ".lang('event::interested-person');
	} elseif($interested > 1) {
		$interested = $interested." ".lang('event::interested-people');
	}
	return $interested;
}

function event_suggestion() {
	$user_id = get_userid();
	$user = array(0);
	$friends = array_merge($user, get_friends($user_id));
	$friends = implode(',', $friends);
	$sql = "SELECT event_id FROM event_invites WHERE user_id IN ($friends) ";
	$q = db()->query($sql);
	$record = $q->fetch_assoc();
	$event = "SELECT * FROM events WHERE end_time >= NOW() AND event_id = '{$record['event_id']}'";
	$event_q = db()->query($event);
	$record = $event_q->fetch_assoc();
	return $record;
}

function event_days_duration($start_time, $end_time) {
	$datediff = $end_time - $start_time;
	return round($datediff / (60 * 60 * 24));
}