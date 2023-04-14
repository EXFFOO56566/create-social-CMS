<?php
function add_report($val) {
	$expected = array(
		'link' => '',
		'type' => '',
		'reason' => ''
	);

	/**
	 * @var $link
	 * @var $type
	 * @var $reason
	 */
	extract(array_merge($expected, $val));
	$time = time();
	$user_id = get_userid();
	$link = sanitizeText($link);
	$type = sanitizeText($type);
	$reason = sanitizeText($reason);
	if(!has_reported($link, $user_id)) {
		db()->query("INSERT INTO reports (user_id, type, link, message, time) VALUES('".$user_id."', '".$type."', '".$link."', '".$reason."', '".$time."')");
		fire_hook('report.added', null, array($val));
		return true;
	} else {
		return false;
	}
}

function count_reports() {
	$db = db();
	$sql = "SELECT COUNT(report_id) FROM reports";
	$query = $db->query($sql);
	$result = $query->fetch_row();
	$count = $result[0];
	return $count;
}

function get_reports($limit = 20) {
	$query = "SELECT * FROM reports ORDER BY report_id DESC ";
	return paginate($query, $limit);
}

function delete_report($id) {
	db()->query("DELETE FROM reports WHERE report_id='{$id}'");
	return true;
}

function has_reported($link, $user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$db = db();
	$sql = "SELECT COUNT(report_id) FROM reports WHERE link = '".$link."' AND user_id = ".$user_id;
	$query = $db->query($sql);
	$result = $query->fetch_row();
	$count = $result[0];
	return $count ? true : false;
}
