<?php
function count_notifications($userid = null) {
	$user_id = get_userid();
	$sql = "SELECT notification_id FROM notifications WHERE";
	$where_clause = " `to_userid` = '".$user_id."' AND `seen` = '0'";
	$where_clause = fire_hook('users.category.filter', $where_clause, array($where_clause, false, false));
	$sql .= $where_clause;
	$query = db()->query($sql);
	if($query) return $query->num_rows;
	return 0;
}

function send_notification($user_id, $type, $type_id = null, $data = array(), $title = null, $content = null, $from_user_id = null) {
	$from_user_id = ($from_user_id) ? $from_user_id : get_userid();
	$data = serialize($data);
	$time = time();
	$query = db()->query("INSERT INTO `notifications` (from_userid, to_userid, type, type_id, title, content, data, time) VALUES ('".$from_user_id."', '".$user_id."', '".$type."', '".$type_id."', '".$title."', '".$content."', '".$data."', '".$time."')");
	$insert = db()->insert_id;
	fire_hook("notification.send", null, array($user_id, $from_user_id, $type, $type_id, $data, $title, $content, $from_user_id,));
	fire_hook('plugins.users.category.updater', null, array('notifications', '', $insert, 'notification_id'));
	$seen = fire_hook('notification.send.seen', array('result' => true), array($user_id, $type, $type_id, $data, $title, $content, $from_user_id, $insert));
	pusher()->sendMessage($user_id, 'notification', array($insert), NULL, $seen['result']);
	fire_hook('notification.send.after', null, array($user_id, $type, $type_id, $data, $title, $content, $from_user_id, $insert));
	return true;
}

function send_notification_privacy($p, $user_id, $type, $type_id = null, $data = array(), $title = null, $content = null, $from_user_id = null) {
	$privacy = get_privacy($p, 1, $user_id);
	if($privacy) {
		return send_notification($user_id, $type, $type_id, $data, $title, $content, $from_user_id);
	}
	return false;
}

function get_notifications($limit = null, $unread = false) {
	$limit = $limit ? $limit : config('notification-list-limit', 10);
	$user_id = get_userid();
	$fields = "notification_id,from_userid,to_userid,type,type_id,title,content,data,seen,mark_read,time,id,avatar,first_name,last_name,username,gender";
	$fields = fire_hook('notification.fields', $fields, array($fields));
	$sql = "SELECT ".$fields." FROM notifications INNER JOIN `users` ON notifications.from_userid = users.id WHERE";
	$where_clause = " `to_userid` = '".$user_id."'";
	if($unread) {
		$where_clause .= " AND `seen` = 0";
	}
	$where_clause = fire_hook('users.category.filter', $where_clause, array($where_clause, false, false, 'notifications'));
	$order_by = " ORDER BY `time` DESC";
	$sql .= $where_clause.$order_by;
	return paginate($sql, $limit);
}

function mark_notification_seen($id) {
	db()->query("UPDATE `notifications` SET `seen` = '1' WHERE `notification_id` = '".$id."'");
	fire_hook("notification.seen", null, array($id));
	return true;
}

function mark_notification_read($id, $type) {
	db()->query("UPDATE `notifications` SET `mark_read` = '".$type."' WHERE `notification_id` = '".$id."'");
	fire_hook("notification.seen", null, array($id, $type));
	return true;
}

function delete_notification($id) {
	db()->query("DELETE FROM  `notifications`  WHERE `notification_id` = '".$id."'");
	fire_hook("notification.deleted", null, array($id));
	return true;
}

function preload_notifications($ids) {
	$fields = "notification_id,from_userid,to_userid,type,type_id,title,content,data,seen,mark_read,time,id,avatar,first_name,last_name,username,gender";
	$query = db()->query("SELECT ".$fields." FROM notifications INNER JOIN `users` ON notifications.from_userid = users.id WHERE notification_id IN (".$ids.") ORDER BY `time` DESC");
	return fetch_all($query);
}

function delete_old_notifications() {
	db()->query("DELETE FROM notifications WHERE  time < ".(time() - (60 * 60 * 24 * 5))." AND seen>0");
}

function find_notification($notificationId) {
	$select = db()->query("SELECT * FROM notifications WHERE notification_id = ".$notificationId);
	return $select->fetch_assoc();
}

function notification_fcm_token_update($token, $user_id = null) {
	$db = db();
	$user_id = $user_id ? $user_id : get_userid();
	$query = $db->query("UPDATE `users` SET `fcm_token` = '".$token."' WHERE `id` = ".$user_id);
	return $query ? true : false;
}


function count_unread_notifications() {
	$db = db();
	$user_id = get_userid();
	$sql = "SELECT COUNT(`notification_id`) FROM `notifications` WHERE `to_userid` = '".$user_id."' AND seen = 0";
	$query = $db->query($sql);
	$row = $query->fetch_row();
	$count = $row[0];
	return $count;
}
