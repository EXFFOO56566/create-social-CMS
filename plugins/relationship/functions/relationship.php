<?php
function is_following($userid, $loggedinUser = null) {
	$loggedinUser = ($loggedinUser) ? $loggedinUser : get_userid();
	$followings = get_following($loggedinUser);

	if(in_array($userid, $followings)) return true;
	return false;
}

function get_following($userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$cacheName = "user-following-".$userid;
	fire_hook('clear-user-friend-cache', null, array($cacheName));
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {

		$users = array();
		$sql = "SELECT `to_userid` FROM `relationship`";
		$where_clause = " WHERE `type`='1' AND `from_userid`='{$userid}'";
		$where_clause = fire_hook('users.category.filter', $where_clause, array($where_clause, true));
		$order_by = "";
		$query = db()->query("{$sql}"."{$where_clause}");
		if($query) {
			foreach(fetch_all($query) as $result) {
				$users[] = $result['to_userid'];
			}
			return $users;
		}
		set_cacheForever($cacheName, $users);
		return $users;
	}
}

function get_followers($userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$cacheName = "user-followers-".$userid;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$users = array();
		$sql = "SELECT `from_userid` FROM `relationship`";
		$where_clause = " WHERE `type`='1' AND `to_userid`='{$userid}'";
		$where_clause = fire_hook('users.category.filter', $where_clause, array($where_clause, true));
		$order_by = "";
		$query = db()->query("{$sql}"."{$where_clause}");
		if($query) {
			foreach(fetch_all($query) as $result) {
				$users[] = $result['from_userid'];
			}
			return $users;
		}
		set_cacheForever($cacheName, $users);
		return $users;
	}
}

/**
 * process follow
 * @param string $type
 * @param int $userid
 * @return boolean
 */
function process_follow($type, $userid, $notify = true, $fromUserid = null) {
	$fromUserid = ($fromUserid) ? $fromUserid : get_userid();
	if($type == 'unfollow') {
		$sql = "DELETE FROM `relationship` WHERE";
		$unfollow_whereclause = " type='1' AND `from_userid`='{$fromUserid}' AND `to_userid`='{$userid}'";
		$unfollow_whereclause = fire_hook('unfollow.where.clause', $unfollow_whereclause, array($unfollow_whereclause));
		$sql .= $unfollow_whereclause;
		db()->query($sql);
		fire_hook("user.unfollow", null, array($fromUserid, $userid));
	} else {
		$time = time();
		db()->query("INSERT INTO `relationship` (from_userid,to_userid,type,confirm,time)VALUES(
            '{$fromUserid}','{$userid}','1','1','{$time}'
        )");
		$relationshipId = db()->insert_id;
		fire_hook("user.follow", null, array($fromUserid, $userid));
		fire_hook('plugins.users.category.updater', null, array('relationship', ' ', $relationshipId, 'relationship_id'));
		if($notify and plugin_loaded('notification') and user_privacy("receive-follow-notification", true, find_user($userid))) {
			load_functions("notification::notification");
			send_notification_privacy('notify-following-you', $userid, 'relationship.follow', $userid, array(), null, null, $fromUserid);
		}
	}
	forget_cache("user-following-".$fromUserid);
	forget_cache("user-followers-".$userid);
}

function friend_status($userid) {
	if(!is_loggedIn()) return false;
	$loggedUser = get_userid();
	$friends = get_friends($loggedUser);
	if(in_array($userid, $friends)) return 2;
	$inRequestFriends = get_requested_friends($loggedUser);
	if(in_array($userid, $inRequestFriends)) return 1;
	//let check if this user already sent a request to this loggedin user too
	$toRequestFriends = get_requested_friends($userid);
	if(in_array($loggedUser, $toRequestFriends)) return 3;
	return '0';
}

function follow_status($userid) {
	if(!is_loggedIn()) return false;
	$loggedUser = get_userid();
	$followers = get_following($loggedUser);
	if(in_array($userid, $followers)) return 2;

	$toRequestFriends = get_followers($userid);
	if(in_array($loggedUser, $toRequestFriends)) return 2;
	return '0';
}

function get_friends($userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	if(is_array($userid)) $userid = $userid['id'];
	$cacheName = "user-friends-".$userid;
	fire_hook('clear-user-friend-cache', null, array($cacheName));
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	}
	$sql = "SELECT from_userid,to_userid FROM relationship";
	$where_clause = " WHERE `type` = '2' AND confirm='1' AND (from_userid='{$userid}' OR to_userid='{$userid}')";
	$where_clause = fire_hook('users.category.filter', $where_clause, array($where_clause, true));
	$order_by = " ORDER BY relationship_id DESC";
	$query = db()->query("{$sql}"."{$where_clause} "." {$order_by}");
	if($query) {
		$users = array();
		while($fetch = $query->fetch_assoc()) {
			$users[] = ($fetch['from_userid'] == $userid) ? $fetch['to_userid'] : $fetch['from_userid'];
		}
		set_cacheForever($cacheName, $users);
		return $users;
	}
	return array();
}

function get_requested_friends($userid) {
	$cacheName = "user-request-friends-".$userid;
	fire_hook('clear-user-friend-cache', null, array($cacheName));
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	}
	$sql = "SELECT from_userid,to_userid FROM relationship";
	$where_clause = " WHERE type='2' AND confirm='0' AND (from_userid='{$userid}')";
	$where_clause = fire_hook('users.category.filter', $where_clause, array($where_clause, true));
	$query = db()->query("{$sql}"."{$where_clause}");

	if($query) {
		$users = array();
		foreach(fetch_all($query) as $result) {
			$users[] = ($result['from_userid'] == $userid) ? $result['to_userid'] : $result['from_userid'];
		}
		set_cacheForever($cacheName, $users);
		return $users;
	}
	return array();
}

function get_friend_requests($dropdown = false) {
	$userid = get_userid();
	if($dropdown) {
		$sql = "SELECT * FROM `relationship` INNER JOIN `users` ON relationship.from_userid=users.id WHERE `to_userid`='{$userid}' ";
		$sql .= " AND confirm='0' AND (type='2' ";
		//$sql = fire_hook("get.friend.requests", $sql, array());
		$sql .= ")";
		$sql = fire_hook('users.category.filter', $sql, array($sql, true, false, 'relationship', true));

		$query = db()->query($sql." ORDER BY time desc LIMIT 5 ");
		return fetch_all($query);
	} else {
		$sql = "SELECT * FROM `relationship` INNER JOIN `users` ON relationship.from_userid=users.id WHERE `to_userid`='{$userid}' ";
		$sql .= " AND confirm='0' AND (type='2' ";
		$sql = fire_hook("get.friend.requests", $sql, array());
		$sql .= ")";
		$sql = fire_hook('users.category.filter', $sql, array($sql, true, false, 'relationship', true));

		return paginate($sql." ORDER BY time desc ", 10);
	}
}

function preload_friend_requests($ids) {
	$query = db()->query("SELECT * FROM `relationship` INNER JOIN `users` ON relationship.from_userid=users.id WHERE relationship_id IN ({$ids}) ORDER BY time desc ");
	return fetch_all($query);
}

function count_friend_requests() {
	$userid = get_userid();
	$sql = "SELECT `relationship_id` FROM `relationship`";
	$where_clause = "WHERE `to_userid`='{$userid}' AND confirm='0'";
	$where_clause = fire_hook('users.category.filter', $where_clause, array($where_clause, true));
	$query = db()->query("{$sql}"."{$where_clause}");
	if($query) return $query->num_rows;
	return 0;
}

function confirm_friend_request($userid) {
	$loginUserid = get_userid();
	$status = friend_status($userid);
	if($status != 2) {
		db()->query("UPDATE `relationship` SET `confirm`='1' WHERE to_userid='{$loginUserid}' AND from_userid='{$userid}'");
		fire_hook("user.confirm-friend", null, array($loginUserid, $userid));
		fire_hook("creditgift.addcredit.addfriend", null, array($userid));
		forget_cache("user-friends-".$userid);
		forget_cache("user-friends-".$loginUserid);
		forget_cache("user-request-friends-".$loginUserid);
		forget_cache("user-request-friends-".$userid);

		send_notification($userid, 'relationship.confirm');

		$privacy = get_privacy('email-notification', 1, $userid);
		if(config('enable-email-notification', true) and $privacy) {
			$mailer = mailer();
			$user = find_user($userid);
			if(!user_is_online($user)) {
				$mailer->setAddress($user['email_address'], get_user_name($user))->template("friend-acceptance", array(
					'link' => '<a href="'.profile_url('friends').'">'.profile_url('friends').'</a>',
					'fullname' => get_user_name(),
				));
			}
			$mailer->send();
		}
		//we need to follow automatically if the method is 3
		if(config('relationship-mehtod', 3) == 3) {
			if(!is_following($userid)) {
				process_follow('follow', $userid, false);
			}
		}
	}

	return 1;
}

function add_friend($userid, $from_mail = false) {
	if(!is_loggedIn() and friend_status($userid) != 0) return false;
	$time = time();
	$loggedUser = get_userid();

	//This is added for industry member branch
	//it has been tested and proved Harmless by NAFDAC
	//START
	if(!$from_mail) {
		$hook_response = fire_hook('connection.request', true, array());
		if(!$hook_response) {
			return false;
		}
	}
	//END
	$sql = "SELECT * FROM relationship WHERE";
	$whereClause = " type='2'  AND ((`from_userid`='{$userid}' AND `to_userid`='{$loggedUser}') OR (`from_userid`='{$loggedUser}' AND `to_userid`='{$userid}'))";
	$whereClause = fire_hook('users.category.filter', $whereClause, array($whereClause, true));
	$sql .= $whereClause;
	$query = db()->query($sql);
	if($query->num_rows > 0) return true;

	db()->query("INSERT INTO `relationship` (from_userid,to_userid,type,confirm,time)VALUES(
            '{$loggedUser}','{$userid}','2','0','{$time}'
        )");
	$relationshipId = db()->insert_id;
	fire_hook('plugins.users.category.updater', null, array('relationship', ' ', $relationshipId, 'relationship_id'));
	//we need to follow automatically if the method is 3
	if(config('relationship-method', 3) == 3) {
		if(!is_following($userid)) {
			process_follow('follow', $userid, false);
		}
	}
	//send push notification
	pusher()->sendMessage($userid, 'friend-request', array($relationshipId), null, null);

	//let send mail if notification is enabled and the user can receive notification
	$privacy = get_privacy('email-notification', 1, $userid);
	if(config('enable-email-notification', true) and $privacy) {
		$mailer = mailer();
		$user = find_user($userid);
		if(!user_is_online($user)) {
			$mailer->setAddress($user['email_address'], get_user_name($user))->template("friend-request", array(
				'link' => '<a href="'.url('friend/requests').'">'.url('friend/requests').'</a>',
				'fullname' => get_user_name(),
			));
		}
		$mailer->send();
	}


	fire_hook("user.add-friend", null, array($loggedUser, $userid));
	forget_cache("user-friends-".$userid);
	forget_cache("user-friends-".$loggedUser);
	forget_cache("user-request-friends-".$loggedUser);
	return 1;
}

function remove_friend($userid) {
	if(!is_loggedIn() and friend_status($userid) == 0) return false;
	$lUserid = get_userid();
	$sql = "DELETE FROM `relationship` WHERE";
	$remove_friend_whereclause = " (`from_userid`='{$userid}' AND `to_userid`='{$lUserid}') OR (`from_userid`='{$lUserid}' AND `to_userid`='{$userid}') ";
	$remove_friend_whereclause = fire_hook('remove.friend.where.clause', $remove_friend_whereclause, array($remove_friend_whereclause));
	$sql .= $remove_friend_whereclause;
	db()->query($sql);
	fire_hook("user.remove-friend", null, array($lUserid, $userid));
	forget_cache("user-friends-".$userid);
	forget_cache("user-friends-".$lUserid);
	forget_cache("user-request-friends-".$lUserid);
	forget_cache("user-request-friends-".$userid);
	if(is_following($userid)) {
		process_follow('unfollow', $userid, false);
	}

	if(is_following($lUserid, $userid)) {
		process_follow('unfollow', $lUserid, false, $userid);
	}
	return 1;
}

function relationship_valid($userid, $type, $toUserid = null) {
	if(!is_loggedIn()) return false;
	$toUserid = ($toUserid) ? $toUserid : get_userid();
	if($type == 1) {
		$followers = get_followers($userid);
		if(isset($followers[$toUserid]) or in_array($toUserid, $followers)) return true;
	} else {
		$friends = get_friends($userid);
		if(isset($friends[$toUserid]) or in_array($toUserid, $friends)) return true;
	}

	return false;
}

function get_friends_of_friend($userid) {
	$friends = get_friends($userid);
	$users = array();
	foreach($friends as $id) {
		$users = array_merge($users, (array) get_friends($id));
	}
	return $users;
}

function get_following_following($refId) {
	$following = get_following($refId);
	$users = array();
	foreach($following as $id) {
		$users = array_merge($users, get_following($id));
	}
	return $users;
}

function relationship_suggest($limit, $refId = null, $only_people = false) {
	$ignoredUsers = mostIgnoredUsers();
	$refId = ($refId) ? $refId : get_userid();

	$whereClause = "";

	$ignoredUsers = array_merge($ignoredUsers, get_friends($refId));
	$ignoredUsers = array_merge($ignoredUsers, get_requested_friends($refId));
	$friendsFriends = get_friends_of_friend($refId);
	if($friendsFriends) {
		$friendsFriends = implode(',', $friendsFriends);
		$whereClause .= "id IN({$friendsFriends}) ";
	}

	//$followersFollowing = get_following_following($refId);
	$ignoredUsers = array_merge($ignoredUsers, get_following($refId));

	$userCountry = get_user_data('country');
	$userCity = get_user_data('city');
	$userState = get_user_data('state');
	$whereClause .= ($whereClause) ? " OR `country`='{$userCountry}' OR `city`='{$userCity}' OR `state`='{$userState}' OR avatar !=''" : "`country`='{$userCountry}' OR `city`='{$userCity}' OR `state`='{$userState}' OR avatar !=''";
	$whereClause = fire_hook('users.suggestion.sql', $whereClause);
	$after_whereClause = "";
	$after_whereClause = fire_hook('users.category.filter', $after_whereClause, array($after_whereClause, true));
	$ignoredUsers = implode(',', array_merge(array($refId), $ignoredUsers));
	$fields = get_users_fields();

	$query = "SELECT {$fields} FROM `users` WHERE `id`NOT IN({$refId}) AND ({$whereClause}) AND id NOT IN ({$ignoredUsers}) AND activated=1 {$after_whereClause} ORDER BY rand()";
	$query = fire_hook("state.city.suggestions", $query, array($fields, $refId, $whereClause, $ignoredUsers));
	if($only_people) {
		$query = fire_hook('get.suggest.non.doctors', $query, array($fields, $refId, $whereClause, $ignoredUsers));
	}
	return paginate($query, $limit);
}

function list_connections($users, $limit = 10, $term = null) {
	$users[] = 0;
	$users = implode(',', $users);
	$fields = get_users_fields();
	$sql = "SELECT {$fields}, online_time FROM users WHERE id IN ({$users})";
	if(trim($term)) {
		$terms = explode(' ', trim($term));
		$sql .= " AND (0";
		foreach($terms as $term) {
			$sql .= " OR first_name LIKE '%".$term."%' OR last_name LIKE '%".$term."%' OR email_address LIKE '%".$term."%' OR username LIKE '%".$term."%'";
		}
		$sql .= ")";
	}
	$sql .= " ORDER BY online_time";
	return paginate($sql, $limit);
}

function get_mutual_friends($userid) {
	$loggedInFriends = get_friends();
	$thisUserFriends = get_friends($userid);
	$mutual = array();
	if(is_array($thisUserFriends)) {
		foreach($thisUserFriends as $f) {
			if(in_array($f, $loggedInFriends) and $f != get_userid()) $mutual[] = $f;
		}
	}
	return $mutual;
}

function find_relationship($relationshipId) {
	$select = db()->query("SELECT * FROM relationship WHERE relationship_id = ".$relationshipId);
	return $select->fetch_assoc();
}


// Relationship status and family relationship
if(plugin_loaded('relation')) {
	plugin_deactivate('relation');
	plugin_delete('relation');
}

function get_family_members($userID) {
	$db = db();
	$family = $db->query("SELECT * FROM relation WHERE user_id = '".$userID."' OR with_id = '".$userID."'");
	return fetch_all($family);
}

function get_family_member($id) {
	$db = db();
	$family = $db->query("SELECT * FROM relation WHERE id = '".$id."'");
	return $family->fetch_assoc();
}

function add_relationship_member($userId, $relation, $id, $status, $privacy) {
	$date = time();
	db()->query("INSERT INTO relation (user_id, relation_type, with_id, status, privacy, date) VALUES('{$userId}', '{$relation}','{$id}','{$status}','{$privacy}','{$date}')");
	$id = db()->insert_id;
	return $id;
}

function family_accept_request($userId, $requestId, $user = null, $status = null) {
	$user = get_userid();
	$status = 1;
	db()->query("UPDATE relation SET status ={$status} WHERE id ={$requestId} AND user_id ={$userId} AND with_id ={$user}");
}

function family_decline_request($userId, $requestId, $user = null, $status = null) {
	$user = get_userid();
	$status = 2;
	db()->query("UPDATE relation SET status ={$status} WHERE id ={$requestId} AND user_id ={$userId} AND with_id ={$user}");
}

function add_relationship_with($val) {
	db()->query("INSERT INTO relation_type (title, r_type) VALUES('{$val['title']}', '{$val['type']}')");
}

function delete_relation($id, $userId) {

	db()->query("DELETE FROM relation WHERE id ='".$id."' AND  user_id =".$userId);
}

function update_m_status($value, $user = null) {
	$user = get_userid();
	$db = db();
	$query = $db->query("UPDATE users SET m_status ='".$value."' WHERE id =".$user);
	if($query) {
		return true;
	}
}

function check_relation($userId, $withId) {
	$db = db();
	$check = $db->query("SELECT * FROM relation WHERE (user_id = '".$userId."' AND with_id = '".$withId."') OR (user_id = '".$withId."' AND with_id = '".$userId."')");
	$num = $check->num_rows;
	return $num;
}
function get_relationships($gender,$type){
    $where = " status = '1' AND type ='".$type."'";
    if ($gender !='all' && $gender != ''){$where .= " AND gender = '".$gender."'";}
    $select = db()->query("SELECT * FROM relationship_family WHERE ".$where);
    return fetch_all($select);
}
function get_family_relationships() {
    $query = db()->query("SELECT * FROM relationship_family");
    return fetch_all($query);
}

function get_family_relationship($id) {
    $query = db()->query("SELECT * FROM relationship_family WHERE id='".$id ."'");
    return $query->fetch_assoc();
}
function save_relationship_family($val, $fam){
    /**
     * @var $title
     * @var $status
     * @var $type
     * @var $gender
     */
    $expected = array('title' => '', 'status' => '', 'type' => '', 'gender' => '');
    extract(array_merge($expected, $val));
    $titleSlug = $fam['relationship'];
    foreach($title as $langId => $t) {
        (phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId) : add_language_phrase($titleSlug, $t, $langId, 'relationship');
    }
    $id = $fam['id'];
    $db = db();
    $edit = $db->query("UPDATE relationship_family SET relationship = '".$titleSlug."',  status = '".$status."', type = '".$type."', gender = '".$gender."' WHERE id = ".$id);
    if($edit) {
        return true;
    }
}
function add_relationship_family($val) {
    /**
     * @var $title
     * @var $status
     * @var $type
     * @var $gender
     */
    $expected = array('title' => '', 'status' => '', 'type' => '', 'gender' => '');
    extract(array_merge($expected, $val));
    $titleSlug = "relationship_family_".md5(time().serialize($val)).'_title';
    foreach($title as $langId => $t) {
        add_language_phrase($titleSlug, $t, $langId, 'relationship');
    }
    $db = db();
    $db->query("INSERT INTO relationship_family (relationship, status, type, gender) VALUES('".$titleSlug."', '".$status."', ".$type.", ".$gender.")");
    return true;
}
function update_family_relation($id, $value){
    $update = db()->query("UPDATE relation SET relation_type ='{$value}' WHERE id ='{$id}'");
    if ($update) return true;
}
function delete_relationship_fam($id) {
    db()->query("DELETE FROM relationship_family WHERE id = ".$id);
}
function check_relation_get($userId, $withId) {
    $db = db();
    $check = $db->query("SELECT * FROM relation WHERE (user_id = '".$userId."' AND with_id = '".$withId."') OR (user_id = '".$withId."' AND with_id = '".$userId."')");
    $num = $check->fetch_assoc();
    return $num;
}