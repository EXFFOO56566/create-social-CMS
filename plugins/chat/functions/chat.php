<?php
function get_conversation_id($users, $create = true) {
	$id = get_single_conversation_id($users);
	if(count($users) > 1 or !$id and $create) {
		$type = (count($users) > 1) ? 'multiple' : 'single';
		$time = time();
		$user1 = null;
		$user2 = null;
		if(count($users) == 1) {
			$user1 = get_userid();
			$user2 = $users[0];
		}
		db()->query("INSERT INTO conversations (type, time, last_update_time, user1, user2)
        VALUES('".$type."', '".$time."', '".$time."', '".$user1."', '".$user2."')");
		$id = db()->insert_id;
		$users[] = get_userid();

		foreach($users as $user) {
			add_conversation_member($id, $user);
		}

	}

	return $id;
}

function get_single_conversation_id($users) {
	if(count($users) > 1) return false;
	$userid = $users[0];
	$iUser = get_userid();
	$query = db()->query("SELECT cid FROM conversations WHERE type = 'single' AND ((user1 = '".$userid."' AND user2 = '".$iUser."') OR (user1 = '".$iUser."' AND user2 = '".$userid."'))");
	$result = $query->fetch_assoc();
	if($result) return $result['cid'];
	return false;
}

function add_conversation_member($id, $user) {
	$members = get_conversation_members($id);
	if(in_array($user, $members) or $user == 0) return false;

	$time = time();
	$db = db()->query("INSERT INTO conversation_members (member_cid, user_id, time) VALUES('".$id."', '".$user."', '".$time."')");

	fire_hook('conversation.member.added', null, array(db()->insert_id));
	forget_cache("chat-conversation-members-".$id);
	return db()->insert_id;
}

function get_conversation_members($id) {
	$cacheName = "chat-conversation-members-".$id;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$users = array();
		$q = db()->query("SELECT user_id FROM conversation_members WHERE member_cid = '".$id."'");
		while($fetch = $q->fetch_assoc()) {
			$users[] = $fetch['user_id'];
		}

		set_cacheForever($cacheName, $users);
		return $users;
	}
}

function is_conversation_member($cid, $userid = null) {
	$members = get_conversation_members($cid);
	$userid = ($userid) ? $userid : get_userid();
	if(in_array($userid, $members)) return true;
	return false;
}

function send_chat_message($cid, $message, $image = null, $file = null, $voice = null, $gifPath = null) {
	$time = time();
	$sender = get_userid();
	$message = sanitizeText($message);
	//update the cid last update time
	db()->query("UPDATE conversations SET last_update_time = '".$time."' WHERE cid = '".$cid."'");
	$db = db()->query("INSERT INTO conversation_messages (cid, sender, message, time, files, image, voice, gif) VALUES('".$cid."', '".$sender."', '".$message."', '".$time."', '".$file."', '".$image."', '".$voice."', '".$gifPath."')");
	$insertId = db()->insert_id;
	//lets send the push to each members of this conversations
	$members = get_conversation_members($cid);
	foreach($members as $userid) {
		if($userid != get_userid()) {
			$messages = get_chat_messages_by_ids($insertId);
			$html = view("chat::display/list", array('messages' => $messages, 'mark' => false, 'user_id' => $userid));
			pusher()->sendMessage($userid, 'chat', array('user' => get_userid(), 'id' => $insertId, 'html' => $html), $cid);
		} else {
			//the essence of this is to prevent seen pushes update to prevent sound alert
			//pusher()->sendMessage($userid, 'chat', array('user' => get_userid(), 'id' => $insertId), $cid, false);
		}
	}

	return $insertId;
}

function get_conversation_message_fields() {
	return "conversation_messages.message_id, conversation_messages.cid, conversation_messages.sender, conversation_messages.message, conversation_messages.files, conversation_messages.voice, conversation_messages.gif, conversation_messages.image, conversation_messages.time";
}

function get_chat_message($id) {
	$fields = get_conversation_message_fields();
	$fields .= ", users.first_name, users.username, users.last_name, users.avatar, users.gender";
	$query = db()->query("SELECT ".$fields." FROM conversation_messages LEFT JOIN users ON conversation_messages.sender = users.id WHERE message_id = '".$id."'");
	if($query) return $query->fetch_assoc();
	return false;
}

function get_chat_messages_by_ids($ids) {
	$fields = get_conversation_message_fields();
	$fields .= ", users.first_name, users.username, users.last_name, users.avatar, users.gender";
	$query = db()->query("SELECT ".$fields." FROM conversation_messages LEFT JOIN users ON conversation_messages.sender = users.id WHERE message_id IN (".$ids.")");
	if($query) return fetch_all($query);
	return false;
}

function get_chat_messages($cid, $limit = null, $offset = null, $user_id = null) {
	$limit = isset($limit) ? $limit : 10;
	$offset = isset($offset) ? $offset : 0;
	$user = isset($user_id) ? find_user($user_id) : null;
	$fields = get_conversation_message_fields();
	$fields .= ", users.first_name, users.username, users.last_name, users.avatar, users.gender";
	$ids = get_privacy('delete-messages', array(0), $user);
	$ids = implode(', ', $ids);

	$query = db()->query("SELECT ".$fields." FROM conversation_messages LEFT JOIN users ON users.id = conversation_messages.sender WHERE conversation_messages.cid = '".$cid."' AND conversation_messages.message_id NOT IN (".$ids.") ORDER BY conversation_messages.time desc LIMIT ".$offset.", ".$limit."");
	if($query) return array_reverse(fetch_all($query));
}

function count_unread_messages($cids = null, $user_id = null) {
	$ids = get_read_messages();
	$ids[] = 0;
	//lets add deleted messages as well
	$user_id = isset($user_id) ? $user_id : get_userid();
	$user = find_user($user_id);
	$ids = array_merge($ids, get_privacy('delete-messages', array(0), $user));
	$ids = implode(', ', $ids);
	if(!$cids) $cids = get_user_conversation_id($user_id);
	$cids[] = 0;
	$cids = implode(', ', $cids);
	$q = db()->query("SELECT COUNT(message_id) as count FROM conversation_messages WHERE message_id NOT IN (".$ids.") AND sender != '".$user_id."' AND cid IN (".$cids.")");
	$fetch = $q->fetch_assoc();
	return $fetch['count'];
}

function get_read_messages($userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$cacheName = "messages_read_".$userid;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$result = array();
		$q = db()->query("SELECT message_id FROM conversation_messages_read WHERE user_id = '".$userid."'");
		while($fetch = $q->fetch_assoc()) {
			$result[] = $fetch['message_id'];
		}
		set_cacheForever($cacheName, $result);
		return $result;
	}
}

function get_unread_messages($userid = null, $cids = null) {
	$result = array();
	$userid = ($userid) ? $userid : get_userid();
	$ids = get_read_messages();
	$ids[] = 0;
	$ids = array_merge($ids, get_privacy('delete-messages', array(0)));
	$ids = implode(', ', $ids);
	if(!$cids) $cids = get_user_conversation_id();
	$cids[] = 0;
	$cids = implode(', ', $cids);
	$q = db()->query("SELECT * FROM conversation_messages WHERE message_id NOT IN (".$ids.") AND sender != '".$userid."' AND cid IN (".$cids.")");
	while($fetch = $q->fetch_assoc()) {
		$result[] = $fetch['message_id'];
	}
	return $result;
}

function has_read_message($messageId, $userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$cacheName = "messages_read_".$userid;
	$readMessages = get_read_messages($userid);
	if(in_array($messageId, $readMessages)) return true;
	return false;
}

function message_is_read($message_id) {
	$cache_name = 'message_read_'.$message_id;
	if(cache_exists($cache_name)) {
		return get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT COUNT(message_id) FROM conversation_messages_read WHERE message_id = ".$message_id;
		$query = $db->query($sql);
		$result = $query->fetch_row();
		set_cacheForever($cache_name, $result[0]);
		return $result[0];
	}
}

function mark_message_read($messageId, $userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$cacheName = "messages_read_".$userid;
	$readMessages = get_read_messages($userid);
	if(in_array($messageId, $readMessages)) return true;

	db()->query("INSERT INTO conversation_messages_read (message_id, user_id) VALUES('".$messageId."', '".$userid."')");
	forget_cache($cacheName);
	get_read_messages($userid);
	return true;
}

function get_conversation_title($cid, $c = null) {
	$noC = false;
	if(!$c) {
		$noC = true;
		$q = db()->query("SELECT cid, type, title, user1, user2 FROM conversations WHERE cid = '".$cid."' ");
		$c = $q->fetch_assoc();
	}

	if(!$c) return false;
	if($c['type'] == 'single') {
		$userid = ($c['user1'] == get_userid()) ? $c['user2'] : $c['user1'];
		$q = db()->query("SELECT first_name, last_name FROM users WHERE id = '".$userid."'");
		if($q and $fetch = $q->fetch_assoc()) return get_user_name($fetch);
	} else {
		if($c['title']) return $c['title'];
		//lets get the members
		if($noC) {
			$userid = get_userid();
			$q = db()->query("SELECT id, first_name, last_name, avatar, username FROM conversation_members LEFT JOIN users ON conversation_members.user_id = users.id WHERE member_cid = '".$cid."' AND user_id! = '".$userid."' LIMIT 2");
			$title = "";
			while($fetch = $q->fetch_assoc()) {
				$title .= ($title) ? ', '.$fetch['first_name'] : $fetch['first_name'];
			}
			return $title;
		}
	}
}

function get_user_conversation_id($userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$query = db()->query("SELECT member_cid FROM conversation_members WHERE user_id = '".$userid."'");
	$ids = array();
	while($fetch = $query->fetch_assoc()) {
		$ids[] = $fetch['member_cid'];
	}
	return $ids;
}

function get_user_conversations($limit = null, $multiple = false) {
	$ids = get_user_conversation_id();
	$ids = implode(', ', $ids);
	if(empty($ids)) return array();
	$deleteConversations = get_deleted_conversations();
	$dIds = implode(', ', $deleteConversations);
	$sql = "SELECT cid, type, title, last_update_time, user1, user2 FROM conversations WHERE cid IN (".$ids.") AND cid NOT IN (".$dIds.")";
	if($multiple) $sql .= " AND type = 'multiple' ";
	$sql .= " ORDER BY last_update_time DESC ";
	if($limit) $sql .= "  LIMIT ".$limit."";
	$query = db()->query($sql);
	$conversations = array();
	while($fetch = $query->fetch_assoc()) {
		$c = rearrange_conversation($fetch);
		if($c) $conversations[] = $c;
	}

	return $conversations;
}

function get_last_conversation() {
	$conversations = get_user_conversations(1);
	if($conversations) return $conversations[0];
}

function get_deleted_conversations() {
	$a = array(0);
	$userid = get_userid();
	$q = db()->query("SELECT member_cid FROM conversation_members WHERE user_id = '".$userid."' AND active = 0");
	while($fetch = $q->fetch_assoc()) {
		$a[] = $fetch['member_cid'];
	}
	return $a;
}

function rearrange_conversation($fetch, $all = true) {
	if(!$fetch) return false;
	$c = $fetch;
	//if (empty($c['title'])) $c['title'] = get_conversation_title($c['cid'], $c);
	//get last massage
	$cid = $c['cid'];
	$c['last_message'] = null;
	if($all) {
		$q = db()->query("SELECT message, sender FROM conversation_messages WHERE cid = '".$cid."' ORDER BY time DESC LIMIT 1");
		if($q and $fetch = $q->fetch_assoc()) {
			$c['last_message'] = $fetch['message'];
			$c['last_sender'] = $fetch['sender'];
		}

		//we need to count unread messages as well
		$c['unread'] = count_unread_messages(array($c['cid']));

		$userid = get_userid();
		$q = db()->query("SELECT id, first_name, last_name, avatar, username FROM conversation_members LEFT JOIN users ON conversation_members.user_id = users.id WHERE member_cid = '".$cid."' AND user_id != '".$userid."' LIMIT 2");
		if($c['type'] == 'single') {
			$other_user = get_userid() == $c['user1'] ? find_user($c['user2']) : find_user($c['user1']);
			$c['title'] = get_user_name($other_user);
			$c['avatar'] = get_avatar(75, $other_user);
			if(!isset($c['avatar'])) return false;
		} else {
			$title = "";
			$avatars = array();
			while($fetch = $q->fetch_assoc()) {
				$title .= ($title) ? ', '.$fetch['first_name'] : $fetch['first_name'];
				$avatars[] = get_avatar(75, $fetch);
			}
			$c['title'] = $title;
			$c['avatars'] = $avatars;
			if(count($avatars) < 1) return false;
		}
	}

	return $c;
}


function get_conversation($cid, $all = true) {
	$query = db()->query("SELECT cid, type, title, last_update_time, user1, user2 FROM conversations WHERE cid = '".$cid."'");
	return rearrange_conversation($query->fetch_assoc(), $all);
}

function chat_get_onlines($user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$friends = get_friends($user_id);
	$friends[] = 0;
	$time = time() - 50;
	$friends = implode(', ', $friends);
	$mustAvoidIds = implode(', ', mostIgnoredUsers($user_id));
	$sql = "SELECT id, first_name, last_name, avatar, online_status, username, online_time FROM users WHERE id IN (".$friends.") AND id NOT IN (".$mustAvoidIds.") and online_time > ".$time." and online_status != 2 ";

	if($term = input('term') and $term) {
		$sql .= "AND ( first_name LIKE '%".$term."%' OR last_name LIKE '%".$term."%' ) ";
	}
	$sql .= " ORDER BY online_time DESC";
	$q = db()->query($sql);
	return fetch_all($q);
}

function get_few_offlines($user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$friends = get_friends($user_id);
	$friends[] = 0;
	$time = time() - 50;
	$friends = implode(', ', $friends);
	$mustAvoidIds = implode(', ', mostIgnoredUsers($user_id));
	$q = db()->query("SELECT id, first_name, last_name, avatar, online_status, username, online_time FROM users WHERE id IN (".$friends.") AND id NOT IN (".$mustAvoidIds.") and online_time < ".$time." ORDER BY online_time DESC LIMIT 10");
	return fetch_all($q);
}

function delete_all_messages($cid) {
	$q = db()->query("SELECT message_id FROM conversation_messages WHERE cid = '".$cid."'");
	$ids = array();
	while($fetch = $q->fetch_assoc()) {
		$ids[] = $fetch['message_id'];
	}
	$deletedMessages = get_privacy('delete-messages', array(0));
	$deletedMessages = array_merge($deletedMessages, $ids);
	save_privacy_settings(array('delete-messages' => $deletedMessages));

	return true;
}

function revive_conversation($cid) {

	db()->query("UPDATE conversation_members SET active = 1  WHERE member_cid = '".$cid."' ");
	return true;
}

function remove_typing_status($con) {
	if($con and $con['type'] == 'single') {
		$userid = ($con['user1'] == get_userid()) ? $con['user2'] : $con['user1'];
		$cacheName = "typing-".$userid."";
		$result = array();
		if(cache_exists($cacheName)) $result = get_cache($cacheName);
		$newResult = array();
		foreach($result as $cid => $time) {
			if($con['cid'] != $cid) {
				$newResult[$cid] = $time;
			} else {
				$newResult[$cid] = time() - 100;
			}
		}
		set_cacheForever($cacheName, $newResult);
	}
}

function register_waiting_message($messageId, $con) {
	$userid = get_userid();
	$cacheName = "message-waiting-".$userid."";
	$result = array();
	if(cache_exists($cacheName)) {
		$result = get_cache($cacheName);
	}
	$theUserid = ($con['user1'] == get_userid()) ? $con['user2'] : $con['user1'];
	$result[$con['cid']] = array($messageId, $theUserid);
	set_cacheForever($cacheName, $result);
}

