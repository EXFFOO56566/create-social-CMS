<?php
function invitationsystem_validate_invitatation_code($invitation_code) {
	$invitation_code_info = invitationsystem_get_invitation_code_info($invitation_code);
	if($invitation_code_info) {
		$referrer_invitation_code_info = invitationsystem_get_invitation_code($invitation_code_info['user_id']);
		$validation = $referrer_invitation_code_info['code'] == $invitation_code_info['code'] ? true : false;
	} else {
		$validation = false;
	}
	return $validation;
}

function invitationsystem_signup($user_id, $invitation_code) {
	$db = db();
	$sql = "SELECT `id`, `user_id` FROM `invitationsystem_invitation_codes` WHERE `code` = '".$invitation_code."'";
	$query = $db->query($sql);
	$result = $query->fetch_assoc();
	$invitation_code_id = $result['id'];
	$inviter_id = $result['user_id'];
	$invited_id = $user_id;
	$sql = "INSERT INTO `invitationsystem_invitations` (`inviter_id`, `invited_id`, `invitation_code_id`, `time`) VALUES(".$inviter_id.", ".$invited_id.", ".$invitation_code_id.", NOW())";
	$query = $db->query($sql);
	$invitation = array(
		'id' => $db->insert_id,
		'inviter_id' => $inviter_id,
		'invited_id' => $invited_id
	);
	return $query ? $invitation : false;
}

function invitationsystem_generate_invitation_code($user_id) {
	return strtoupper(substr($user_id.md5(time()), 0, 16));
}

function invitationsystem_set_invitation_code($user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$db = db();
	$invitation_code = invitationsystem_generate_invitation_code($user_id);
	$invitation_code_lifetime = config('invitationsystem-invitation-code-lifetime', 2);
	$invitation_code_expiry_time = time() + ($invitation_code_lifetime * 86400);
	$sql = "INSERT INTO `invitationsystem_invitation_codes` (`code`, `expiry_time`, `user_id`, `time`) VALUES('".$invitation_code."', '".date('Y-m-d H:i:s', $invitation_code_expiry_time)."', ".$user_id.", NOW())";
	$query = $db->query($sql);
	return isset($query) && $query ? true : false;
}

function invitationsystem_get_invitation_code($user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$db = db();
	$sql = "SELECT * FROM `invitationsystem_invitation_codes` WHERE `user_id` = ".$user_id." ORDER BY time DESC LIMIT 1";
	$query = $db->query($sql);
	$result = $query->fetch_assoc();
	return $result ? $result : false;
}

function invitationsystem_get_invitation_code_info($invitation_code) {
	$db = db();
	$sql = "SELECT * FROM `invitationsystem_invitation_codes` WHERE `code` = '".$invitation_code."' ORDER BY time DESC LIMIT 1";
	$query = $db->query($sql);
	$result = $query->fetch_assoc();
	return $result ? $result : false;
}

function invitationsystem_get_invitation($id) {
	$db = db();
	$sql = "SELECT `invitationsystem_invitations`.`inviter_id`, `invitationsystem_invitations`.`invited_id`, `invitationsystem_invitations`.`invitation_code_id`, `invitationsystem_invitations`.`time`, `inviters`.`first_name` as inviter_username, CONCAT(`inviters`.`first_name`, ' ', `inviters`.`last_name`) as inviter_name, `inviteds`.`first_name` as invited_username, CONCAT(`inviteds`.`first_name`, ' ', `inviteds`.`last_name`) as invited_name FROM `invitationsystem_invitations` LEFT JOIN `users` as `inviters` ON `invitationsystem_invitations`.`inviter_id` = `inviters`.`id` LEFT JOIN `users` as `inviteds` ON `invitationsystem_invitations`.`invited_id` = `inviteds`.`id` WHERE `invitationsystem_invitations`.`id` = ".$id;
	$query = $db->query($sql);
	$result = $query->fetch_row();
	return $result;
}

function invitationsystem_get_invitations($search = null, $user_id = null, $limit = null) {
	$where_sql = $user_id ? " WHERE `invitationsystem_invitations`.`inviter_id`  = ".$user_id : null;
	$having_sql = $search ? " HAVING (`inviter_name` LIKE '%".$search."%' OR `invited_username` LIKE '%".$search."%' OR `invited_name` LIKE '%".$search."%' OR `invited_username` LIKE '%".$search."%')" : null;
	$limit = isset($limit) ? $limit : config('invitationsystem-invitations-list-limit', 20);
	$sql = "SELECT `invitationsystem_invitations`.`inviter_id`, `invitationsystem_invitations`.`invited_id`, `invitationsystem_invitations`.`invitation_code_id`, `invitationsystem_invitations`.`time`, `inviters`.`first_name` as inviter_username, CONCAT(`inviters`.`first_name`, ' ', `inviters`.`last_name`) as inviter_name, `inviteds`.`first_name` as invited_username, CONCAT(`inviteds`.`first_name`, ' ', `inviteds`.`last_name`) as invited_name FROM `invitationsystem_invitations` LEFT JOIN `users` as `inviters` ON `invitationsystem_invitations`.`inviter_id` = `inviters`.`id` LEFT JOIN `users` as `inviteds` ON `invitationsystem_invitations`.`invited_id` = `inviteds`.`id` ".$having_sql." ".$where_sql;
	return paginate($sql, $limit);
}

function invitationsystem_get_inviter_id($user_id) {
	$db = db();
	$sql = "SELECT `inviter_id` FROM `invitationsystem_invitations` WHERE `invited_id` = ".$user_id;
	$query = $db->query($sql);
	$result = $query->fetch_row();
	return isset($result[0]) ? $result[0] : false;
}

function invitationsystem_count_invites($user_id) {
	$db = db();
	$sql = "SELECT COUNT(`id`) FROM `invitationsystem_invitations` WHERE `inviter_id` = ".$user_id;
	$query = $db->query($sql);
	$fetch = $query->fetch_row();
	$num = $fetch[0];
	return $num;
}

function invitation_user_delete_hook($user_id) {
	$db = db();
	$sql = "DELETE FROM `invitationsystem_invitations` WHERE `invitationsystem_invitations`.`inviter_id`  = ".$user_id;
	$query = $db->query($sql);
	return $query;
}