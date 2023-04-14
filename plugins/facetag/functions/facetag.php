<?php
function facetag_people_search($str, $user_id = null) {
	$user_id = ($user_id) ? $user_id : get_userid();
	$db = db();
	$sql = "SELECT `users`.`id` FROM `relationship` LEFT JOIN `users` ON `relationship`.`from_userid` = `users`.`id` OR `relationship`.`to_userid` = `users`.`id` WHERE ((`relationship`.`type` = '1' AND `relationship`.`to_userid` = ".$user_id.") OR (`relationship`.`type` = 2 AND `relationship`.`confirm` = 1 AND (`relationship`.`from_userid` = ".$user_id." OR `relationship`.`to_userid` = ".$user_id."))) AND (`users`.`username` LIKE '%".$str."%' OR `users`.`first_name` LIKE '%".$str."%' OR `users`.`last_name` LIKE '%".$str."%' OR `users`.`email_address` LIKE '%".$str."%') ORDER BY `relationship`.`relationship_id` DESC";
	$query = $db->query($sql);
	$users = array();
	while($row = $query->fetch_assoc()) {
		$users[$row['id']] = find_user($row['id']);
		if(count($users) == 10) {
			break;
		}
	}
	return $users;
}

function facetag_get_tags($photo_id) {
	$cache_name = 'facetag-tags-photo-'.$photo_id;
	if(cache_exists($cache_name)) {
		$tags = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT * FROM `facetags` WHERE `photo_id` = ".$photo_id;
		$query = $db->query($sql);
		$tags = array();
		while($row = $query->fetch_assoc()) {
			$user = find_user($row['tagged_user_id']);
			if($user) {
				$user_name = get_user_name($user);
				$row['name'] = $user_name;
				$row['username'] = $user['username'];
				$tags[$row['coord_id']] = $row;
			}
		}
		set_cacheForever($cache_name, $tags);
	}
	return $tags;
}

function facetag_get_tags_by_user($user_id) {
	$cache_name = 'facetag-tags-user-'.$user_id;
	if(cache_exists($cache_name)) {
		$tags = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT * FROM `facetags` WHERE `user_id` = ".$user_id;
		$query = $db->query($sql);
		$tags = array();
		while($row = $query->fetch_assoc()) {
			$user = find_user($row['tagged_user_id']);
			if($user) {
				$user_name = get_user_name($user);
				$row['name'] = $user_name;
				$tags[$row['coord_id']] = $row;
			}
		}
		set_cacheForever($cache_name, $tags);
	}
	return $tags;
}

function facetag_get_tags_by_tagged_user($user_id) {
	$cache_name = 'facetag-tags-tagged-'.$user_id;
	if(cache_exists($cache_name)) {
		$tags = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT * FROM `facetags` WHERE `tagged_user_id` = ".$user_id;
		$query = $db->query($sql);
		$tags = array();
		while($row = $query->fetch_assoc()) {
			$user = find_user($row['tagged_user_id']);
			if($user) {
				$user_name = get_user_name($user);
				$row['name'] = $user_name;
				$tags[$row['coord_id']] = $row;
			}
		}
		set_cacheForever($cache_name, $tags);
	}
	return $tags;
}

function facetag_get_tag($tag_id) {
	$cache_name = 'facetag-tag-'.$tag_id;
	if(cache_exists($cache_name)) {
		$tag = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT * FROM `facetags` WHERE `id` = ".$tag_id;
		$query = $db->query($sql);
		$tag = $query->fetch_assoc();
		set_cacheForever($cache_name, $tag);
	}
	return $tag;
}

function facetag_check($coord_id) {
	$cache_name = 'facetag-check-'.$coord_id;
	if(cache_exists($cache_name)) {
		$tag_id = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT `id` FROM `facetags` WHERE `coord_id` = '".$coord_id."'";
		$query = $db->query($sql);
		$tag = $query->fetch_assoc();
		$tag_id = isset($tag['id']) ? $tag['id'] : false;
		set_cacheForever($cache_name, $tag_id);
	}
	return $tag_id;
}

function facetag_set_coordinate($coord_id, $photo_id, $tagged_user_id) {
	if(facetag_can_tag_photo($photo_id)) {
		$tag_id = facetag_check($coord_id);
		if($tag_id) {
			facetag_set_tag($tag_id, $tagged_user_id);
		} else {
			facetag_add_tag($photo_id, $coord_id, $tagged_user_id);
		}
		return true;
	} else {
		return false;
	}
}

function facetag_remove_coordinate($coord_id) {
	$tag_id = facetag_check($coord_id);
	if($tag_id) {
		$tag = facetag_get_tag($tag_id);
		$photo_id = $tag['photo_id'];
		if(facetag_can_tag_photo($photo_id)) {
			$tag_id = facetag_check($coord_id);
			if($tag_id) {
				facetag_remove_tag($tag_id);
			}
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function facetag_add_tag($photo_id, $coord_id, $tagged_user_id) {
	$db = db();
	$user_id = get_userid();
	$sql = "INSERT INTO `facetags` (`user_id`, `tagged_user_id`, `photo_id`, `coord_id`) VALUES (".$user_id.", ".$tagged_user_id.", ".$photo_id.", '".$coord_id."')";
	$query = $db->query($sql);
	$tag_id = $db->insert_id;
	if($query) {
		forget_cache('facetag-tags-user-'.$user_id);
		forget_cache('facetag-tags-photo-'.$photo_id);
		forget_cache('facetag-tags-tagged-'.$tagged_user_id);
		fire_hook('facetag.tag.added', $tag_id);
		return true;
	} else {
		return false;
	}
}

function facetag_set_tag($tag_id, $tagged_user_id) {
	$db = db();
	$sql = "UPDATE `facetags` SET `tagged_user_id` = ".$tagged_user_id." WHERE `id` = '".$tag_id."'";
	$query = $db->query($sql);
	if($query) {
		forget_cache('facetag-tags-tagged-'.$tagged_user_id);
		forget_cache('facetag-tag-'.$tag_id);
		$tag = facetag_get_tag($tag_id);
		forget_cache('facetag-tags-user-'.$tag['user_id']);
		forget_cache('facetag-tags-photo-'.$tag['photo_id']);
		fire_hook('facetag.tag.updated', $tag_id);
		return true;
	} else {
		return false;
	}
}

function facetag_remove_tag($tag_id) {
	$tag = facetag_get_tag($tag_id);
	$db = db();
	$sql = "DELETE FROM `facetags` WHERE `id` = '".$tag_id."'";
	$query = $db->query($sql);
	if($query) {
		forget_cache('facetag-tag-'.$tag_id);
		forget_cache('facetag-tags-user-'.$tag['user_id']);
		forget_cache('facetag-tags-photo-'.$tag['photo_id']);
		forget_cache('facetag-tags-photo-'.$tag['tagged_user_id']);
		fire_hook('facetag.tag.removed', $tag_id);
		return true;
	} else {
		return false;
	}
}

function facetag_get_notification_ids($tag_id) {
	$db = db();
	$sql = "SELECT `notification_id` FROM `facetags` WHERE `type` = 'facetag' AND `type_id` = ".$tag_id;
	$query = $db->query($sql);
	$ids = fetch_all($query);
	return $ids;
}

function facetag_delete_notifications($tag_id) {
	$notification_ids = facetag_get_notification_ids($tag_id);
	foreach($notification_ids as $notification_id) {
		delete_notification($notification_id);
	}
	return true;
}

function facetag_can_tag_photo($photo_id) {
	$permission = false;
	$photo = find_photo($photo_id);
	if($photo) {
		if(is_admin()) {
			$permission = true;
		}
		if(is_loggedIn() && is_photo_owner($photo) && user_has_permission('can-tag-photos')) {
			$permission = true;
		}
	}
	return $permission;
}