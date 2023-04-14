<?php
function memory_get_relationship($user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$db = db();
	$query = $db->query("SELECT * FROM relationship WHERE MONTH(FROM_UNIXTIME(time)) = MONTH(NOW()) AND DAY(FROM_UNIXTIME(time)) = DAY(NOW()) AND YEAR(FROM_UNIXTIME(time)) != YEAR(NOW()) AND (from_userid = ".$user_id." OR to_userid = ".$user_id.")");
	return fetch_all($query);
}

function memory_get_registration($user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$db = db();
	$query = $db->query("SELECT * FROM users WHERE MONTH(join_date) = MONTH(NOW()) AND DAY(join_date) = DAY(NOW()) AND YEAR(join_date) != YEAR(NOW()) AND id = ".$user_id);
	return (array) $query->fetch_assoc();
}

function memory_get_post($user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$db = db();
	$query = $db->query("SELECT * FROM feeds WHERE type_id != 'memory' AND MONTH(timestamp) = MONTH(NOW()) AND DAY(timestamp) = DAY(NOW()) AND YEAR(timestamp) != YEAR(NOW()) AND user_id = ".$user_id);
	return fetch_all($query);
}

function memory_get_birthday_reminder($user_id = null) {
	$db = db();
	$user_id = isset($user_id) ? $user_id : get_userid();
	$sql = "SELECT * FROM users WHERE MONTH(STR_TO_DATE(birth_month, '%M')) = MONTH(NOW()) AND birth_day = DAY(NOW()) AND birth_year < YEAR(NOW()) AND id = ".$user_id;
	$query = $db->query($sql);
	return fetch_all($query);
}

function memory_add($user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$cache_name = 'memory-'.$user_id.'-'.date('Y-m-d');
	$registration_memory = memory_get_registration();
	$db = db();
	$add_memory = $db->query("BEGIN WORK;");
	if($add_memory) {
		$query = true;
		if($registration_memory) {
			$sql = "INSERT INTO memories(type, user_id, data, date, cache) VALUES('registration', ".$user_id.", '".perfectSerialize($registration_memory)."', NOW(), '".$cache_name."')";
			$query = $db->query($sql);
		}
		if($query) {
			$relationship_memories = memory_get_relationship();
			foreach($relationship_memories as $relationship_memory) {
				if($query) {
					$sql = "INSERT INTO memories(type, user_id, data, date, cache) VALUES('relationship', ".$user_id.", '".perfectSerialize($relationship_memory)."', NOW(), '".$cache_name."')";
					$query = $db->query($sql);
				} else {
					$add_memory = $db->query("ROLLBACK;");
					return false;
				}
			}
			if($query) {
				$post_memories = memory_get_post();
				foreach($post_memories as $post_memory) {
					if($query) {
						$sql = "INSERT INTO memories(type, user_id, data, date, cache) VALUES('post', ".$user_id.", '".perfectSerialize($post_memory)."', NOW(), '".$cache_name."')";
						$query = $db->query($sql);
					} else {
						$add_memory = $db->query("ROLLBACK;");
						return false;
					}
				}
				if($query) {
					$birthday_memories = memory_get_birthday_reminder();
					foreach($birthday_memories as $birthday_reminder_memory) {
						if($query) {
							$sql = "INSERT INTO memories(type, user_id, data, date, cache) VALUES('birthday_reminder', ".$user_id.", '".perfectSerialize($birthday_reminder_memory)."', NOW(), '".$cache_name."')";
							$query = $db->query($sql);
						} else {
							$add_memory = $db->query("ROLLBACK;");
							return false;
						}
					}
				}
				if($query) {
					$add_memory = $db->query("COMMIT;");
					set_cacheForever($cache_name, true);
					return memory_get_memories();
				} else {
					$add_memory = $db->query("ROLLBACK;");
					return false;
				}
			} else {
				$add_memory = $db->query("ROLLBACK;");
				return false;
			}
		} else {
			$add_memory = $db->query("ROLLBACK;");
			return false;
		}
	} else {
		return false;
	}
}

function memory_added($user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$cache_name = 'memory-'.$user_id.'-'.date('Y-m-d');
	if(cache_exists($cache_name)) {
		return get_cache($cache_name);
	} else {
		$db = db();
		$memory = $db->query("SELECT COUNT(id) FROM memories WHERE cache = '".$cache_name."'");
		$memory = $memory->fetch_row()[0];
		set_cacheForever($cache_name, $memory);
		return $memory;
	}
}

function memory_get_memories($user_id = null, $limit = null, $rand = false, $exclude_shared = true, $exclude_unshared = false) {
	if($user_id) {
		$user_ids = array($user_id);
	} else {
		$user_id = get_userid();
		$user_ids = array_merge(array($user_id), get_friends($user_id));
	}
	$cache_name = 'memory-'.$user_id.'-'.date('Y-m-d');
	$db = db();
	$sql = "SELECT memories.*, feeds.privacy FROM memories LEFT JOIN feeds ON type_data = memories.id WHERE memories.cache = '".$cache_name."' AND memories.user_id IN (".implode(', ', $user_ids).") AND feeds.type_id = 'memory'";
	if($exclude_shared) {
		$sql .= " AND feeds.privacy > 2";
	}
	if($exclude_unshared) {
		$sql .= " AND feeds.privacy < 3";
	}
	$sql .= ($rand) ? " ORDER BY RAND()" : "";
	$sql .= ($limit) ? " LIMIT 0, ".$limit : "";
	$query = $db->query($sql);
	$memories = fetch_all($query);
	return $memories;
}

function memory_get_feed($id) {
	$db = db();
	$sql = "SELECT feed_id FROM feeds WHERE type_id = 'memory' AND type_data = ".$id;
	$query = $db->query($sql);
	$row = $query->fetch_assoc();
	$feed_id = $row['feed_id'];
	$feed = find_feed($feed_id);
	return $feed;
}

function memory_get($id) {
	$db = db();
	$sql = "SELECT * FROM memories WHERE id = '".$id."'";
	$query = $db->query($sql);
	$memory = $query->fetch_assoc();
	return $memory;
}

function memory_share_feed($id) {
	$db = db();
	$query = $db->query("UPDATE `feeds` SET `privacy` = 2 WHERE `feed_id` = '".$id."'");
	return $query ? true : false;
}
