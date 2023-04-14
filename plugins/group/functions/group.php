<?php
function group_add($val) {
	/**
	 * @var $title
	 * @var $name
	 * @var $description
	 * @var $privacy
	 * @var $category_id
	 */
	extract($val);
	$add = ($privacy == 2) ? 2 : 1;
	$userid = get_userid();
	$title = sanitizeText($title);
	//$name = unique_slugger($title, 'groups', 'group_id', 'group_title', 'group_name');
	$description = sanitizeText($description);
	$privacy = sanitizeText($privacy);
	db()->query("INSERT INTO groups (group_title, group_name, group_description, user_id, who_can_add_member, privacy, category_id)VALUES('".$title."', '".$name."', '".$description."', '".$userid."', '".$add."', '".$privacy."', '".$category_id."')");
	$groupId = db()->insert_id;
	//auto add creator as member of the group
	group_add_member($groupId);
	fire_hook('group.added', null, array($groupId, $val));
	fire_hook('plugins.users.category.updater', null, array('groups', $val, $groupId, 'group_id'));
	return $groupId;
}

function save_group_settings($val, $groupId) {
	update_group_details($val, $groupId);
}

function get_group_fields() {
	$fields = "group_id,featured,recommend,group_title,group_name,group_description,user_id,who_can_add_member,privacy,who_can_post,moderators,group_created_time,group_logo,group_cover,group_cover_resized,category_id";
	$fields = fire_hook('more.group.fields', $fields, array($fields));
	return $fields;
}

function find_group($id, $few = false) {
	$fields = get_group_fields();
	if($few) $fields = "group_id,group_title,group_name";
	$q = db()->query("SELECT {$fields} FROM groups WHERE ".(is_numeric($id) ? "group_id = ".$id : "group_name = '".$id."'"));
	return $q->fetch_assoc();
}

function group_url($slug = null, $group = null) {
	$group = $group ? $group : false;
	if(!$group && isset(app()->profileGroup)) {
		$group = app()->profileGroup;
	}
	return $group ? url_to_pager("group-profile", array('slug' => $group['group_name'])).'/'.$slug : false;
}

function get_group_cover($group = null, $original = true) {
	$default = img("images/cover.jpg");
	if(!$original and !empty($group['group_cover_resized'])) return url_img($group['group_cover_resized']);
	if(!empty($group['group_cover'])) return url_img($group['group_cover']);
	return ($original) ? '' : $default;
}

function get_group_logo($size, $group = null) {
	$avatar = $group['group_logo'];
	if($avatar) {
		return url(str_replace('%w', $size, $avatar));
	} else {

		return $image = img("images/page-avatar.png");
	}
}

function get_group_details($index, $group = null) {
	$group = $group ? $group : app()->profileGroup;
	if(isset($group[$index])) return $group[$index];
	return false;
}

function delete_group($group) {
	$groupId = $group['group_id'];

	if($group['group_cover']) delete_file(path($group['group_cover']));
	if($group['group_logo']) delete_file(path($group['group_logo']));
	if($group['group_cover_resized']) delete_file(path($group['group_cover_resized']));

	db()->query("DELETE FROM group_members WHERE member_group_id='{$groupId}'");
	//delete its posts too
	delete_posts('group', $groupId);
	db()->query("DELETE FROM groups WHERE group_id='{$groupId}'");
	fire_hook("group.deleted", $groupId);
	return true;

}

function update_group_details($fields, $groupId) {
	$sqlFields = "";
	foreach($fields as $key => $value) {
		$value = sanitizeText($value);
		$sqlFields .= (empty($sqlFields)) ? "`{$key}`='{$value}'" : ",`{$key}`='{$value}'";
	}
	db()->query("UPDATE `groups` SET {$sqlFields} WHERE `group_id`='{$groupId}'");
	fire_hook("group.updated", array($groupId));
	fire_hook('plugins.users.category.updater', null, array('groups', $fields, $groupId, 'group_id'));
}

function make_group_moderator($group, $uid) {
	if(is_group_moderator($group, $uid)) return true;
	$moderators = ($group['moderators']) ? perfectUnserialize($group['moderators']) : array();
	$moderators[] = $uid;
	$moderators = perfectSerialize($moderators);
	$groupId = $group['group_id'];
	db()->query("UPDATE groups SET moderators='{$moderators}' WHERE group_id='{$groupId}'");
	send_notification($uid, 'group.role', $groupId);
	return true;
}

function remove_group_moderator($group, $uid) {
	if(!is_group_moderator($group, $uid)) return true;
	$moderators = ($group['moderators']) ? perfectUnserialize($group['moderators']) : array();
	$newModerators = array();
	foreach($moderators as $u) {
		if($u != $uid) $newModerators[] = $u;
	}
	$moderators = perfectSerialize($newModerators);
	$groupId = $group['group_id'];
	db()->query("UPDATE groups SET moderators='{$moderators}' WHERE group_id='{$groupId}'");
	return true;
}

function is_group_admin($group, $userid = null, $admin = true) {
	$userid = ($userid) ? $userid : get_userid();
	if($admin and is_admin()) return true;
	if($userid == $group['user_id']) return true;
	return false;
}

function is_group_moderator($group, $userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$moderators = ($group['moderators']) ? perfectUnserialize($group['moderators']) : array();
	if(in_array($userid, $moderators)) return true;
	return false;
}

function can_join_group($group) {
	return true;
}

function group_can_post($group = null) {
	if(!is_loggedIn()) return false;
	$group = ($group) ? $group : app()->profileGroup;
	$add = $group['who_can_post'];
	if($add == 1 and is_group_member($group['group_id'])) return true;
	if(is_group_admin($group) or ($add == 2 and is_group_moderator($group))) return true;
	if($add == 3 and is_group_admin($group)) return true;
	return false;
}

function group_can_add_member($group) {
	if(!is_loggedIn()) return false;
	$add = $group['who_can_add_member'];

	if($add == 1) return true;
	if($add == 2 and is_group_moderator($group)) return true;
	if(is_group_admin($group)) return true;
	return false;
}

function group_add_member($groupId, $userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	if(is_group_member($groupId, $userid)) return true;
	db()->query("INSERT INTO group_members (member_id,member_group_id)VALUES('{$userid}','{$groupId}')");
	fire_hook('member.added.to.group', $userid, array($groupId));
	forget_cache("group-members-".$groupId);
	forget_cache('group-joined-'.$userid);
	return true;
}

function group_remove_member($groupId, $userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	if(!is_group_member($groupId, $userid)) return true;
	db()->query("DELETE FROM group_members WHERE member_group_id='{$groupId}' AND member_id='{$userid}'");
	forget_cache("group-members-".$groupId);
	forget_cache('group-joined-'.$userid);
	return true;
}

function is_group_member($groupId, $userid = null, $verified = false) {
	if(!is_loggedIn()) return false;
	$userid = ($userid) ? $userid : get_userid();
	fire_hook('group.members.verified', $groupId, array($userid, $verified));
	$members = get_group_members_id($groupId);
	if(in_array($userid, $members)) return true;
	return false;
}

function get_group_members_id($groupId, $only_verified = false) {
	$cacheName = "group-members-".$groupId;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$q = db()->query("SELECT member_id FROM group_members WHERE member_group_id='{$groupId}'");
		$r = array();
		while($fetch = $q->fetch_assoc()) {
			$r[] = $fetch['member_id'];
		}
		set_cacheForever($cacheName, $r);
		return $r;
	}
}

function get_group_members($groupId, $limit = 10) {
	$membersId = get_group_members_id($groupId);
	$membersId[] = 0;
	$membersId = implode(',', $membersId);
	//$q = db()->query();
	return paginate("SELECT id,first_name,last_name,avatar,username FROM users WHERE id IN ({$membersId})", $limit);
}

function get_groups($type, $term = null, $limit = 10, $filter = 'all', $category = null) {
	$fields = get_group_fields();
	$sql = "SELECT ".$fields." FROM groups ";
	$sql = fire_hook("use.different.group.query", $sql, array($fields));
	$user_id = get_userid();
	if($type == 'yours') {
		$sql .= " WHERE user_id = '".$user_id."' ";
		if($term) {
			$sql .= " AND (group_title LIKE '%".$term."%' OR group_description LIKE '%".$term."%') ";
		}
		if($filter and $filter == 'featured') {
			$sql .= " AND featured = '1' ";
		}
		$sql .= " GROUP BY group_id ORDER BY group_id DESC";
	} elseif($type == 'saved') {
		$saved = get_user_saved('group');
		$saved[] = 0;
		$saved = implode(',', $saved);
		$sql .= " WHERE group_id IN (".$saved.") ";
		if($term) {
			$sql .= " AND (group_title LIKE '%".$term."%' OR group_description LIKE '%".$term."%') ";
		}
		if($filter and $filter == 'featured') {
			$sql .= " AND featured = '1' ";
		}
		$sql = fire_hook("more.group.query.filter", $sql, array());
		$sql = fire_hook('users.category.filter', $sql, array($sql));
		$sql .= " ORDER BY group_id DESC";
	} elseif($type == 'member') {
		$group_ids = get_joined_groups();
		$group_ids[] = 0;
		$group_ids = implode(',', $group_ids);
		$sql .= " WHERE group_id IN (".$group_ids.") ";
		if($term) {
			$sql .= " AND (group_title LIKE '%".$term."%' OR group_description LIKE '%".$term."%') ";
		}
		if($filter and $filter == 'featured') {
			$sql .= " AND featured = '1' ";
		}
		if($category && $category != 'all') $sql .= " AND category_id ='".$category."'";
		$sql = fire_hook("more.group.query.filter", $sql, array());
		$sql .= " ORDER BY group_id DESC";
	} elseif($type == 'profile') {
		$group_ids = get_joined_groups($term);
		$group_ids[] = 0;
		$group_ids = implode(',', $group_ids);
		$sql .= " WHERE privacy = '1' AND (user_id = '".$term."' OR group_id IN (".$group_ids.")) ";
	} elseif($type == "category") {
		$sql .= " Where category_id ='".$category."'";
	} elseif($type == 'recommend') {
		$sql = "SELECT ".$fields.",(SELECT SUM(member_id) FROM group_members WHERE member_group_id = group_id) as members FROM groups ";

		$friendsGroups = array(0);
		$group_ids = get_joined_groups();
		$group_ids[] = 0;
		$friendsGroups = fire_hook('system.relationship.method', $friendsGroups, array('group'));
		$friendsGroups = implode(',', $friendsGroups);
		$group_ids = implode(',', $group_ids);
		$sql .= " WHERE (privacy = '1' OR recommend = 1 OR (group_id IN (".$group_ids."))";
		if($term) {
			$sql .= " AND (group_title LIKE '%".$term."%' OR group_description LIKE '%".$term."%') ";
		}
		$sql .= ")";

		if($filter and $filter == 'featured') {
			$sql .= " AND featured = '1' ";
		}
		if($category && $category != 'all') $sql .= " AND category_id ='".$category."'";
		$sql = fire_hook("more.group.query.filter", $sql, array());
		$sql = fire_hook('users.category.filter', $sql, array($sql));

		if($filter == 'top') {
			$sql .= " ORDER BY members DESC";
		} else {
			$sql .= " ORDER BY group_id DESC";
		}

	} elseif($type == 'search') {
		$group_ids = get_joined_groups();
		$group_ids[] = 0;
		$group_ids = implode(',', $group_ids);
		$sql .= " WHERE (privacy = '1' AND (group_title LIKE '%".$term."%' OR group_description LIKE '%".$term."%' OR group_name LIKE '%".$term."%')) OR (group_id IN (".$group_ids.") AND (group_title LIKE '%".$term."%' OR group_description LIKE '%".$term."%' OR group_name LIKE '%".$term."%')) ";
		$sql = fire_hook("more.group.query.filter", $sql, array());
		$sql = fire_hook('users.category.filter', $sql, array($sql));
	}

	return paginate($sql, $limit);
}

function get_joined_groups($userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$cacheName = 'group-joined-'.$userid;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$q = db()->query("SELECT member_group_id FROM group_members WHERE member_id='{$userid}'");
		$a = array();
		while($fetch = $q->fetch_assoc()) {
			$a[] = $fetch['member_group_id'];
		}
		set_cacheForever($cacheName, $a);
		return $a;
	}
}

function count_total_groups() {
	$q = db()->query("SELECT * FROM groups ");
	return $q->num_rows;
}

function count_groups_in_month($n, $year) {
	$q = db()->query("SELECT * FROM groups WHERE YEAR(timestamp)={$year} AND MONTH(timestamp)={$n}");
	return $q->num_rows;
}


function get_all_groups() {
	$sql = "SELECT * FROM groups ";

	$term = input('term', false);
	if($term) {
		$sql .= " WHERE group_title LIKE '%{$term}%' OR group_description LIKE '%{$term}%' OR group_name LIKE '%{$term}%'";
	}
	$sql .= "ORDER BY group_id DESC";
	return paginate($sql);
}

function get_group_firends($group_id, $userId = null) {
	$userId = $userId ? $userId : get_userid();
	$friends = get_friends($userId);
	$members = get_group_members_id($group_id);
	return array_intersect($friends, $members);
}

function get_group_categories() {
	$db = db()->query("SELECT * FROM group_categories ORDER BY `id` ASC");
	$result = fetch_all($db);
	return $result;
}

function get_group_category($id) {
	$query = db()->query("SELECT * FROM group_categories WHERE id='{$id}'");
	if($query) return $query->fetch_assoc();
	return false;
}

function delete_group_category($category) {
	$id = $category['id'];
	db()->query("DELETE FROM group_categories WHERE id='{$id}'");
	return true;
}

function save_group_category($val, $cat) {
	/**
	 * @var $title
	 * @var $category
	 * @var $image
	 */
	extract($val);
	$englishValue = $title['english'];
	$slug = $cat['title'];
	foreach($title as $langId => $t) {
		if(!$t) $t = $englishValue;
		(phrase_exists($langId, $slug)) ? update_language_phrase($slug, $t, $langId, 'group-category') : add_language_phrase($slug, $t, $langId, 'group-category');
	}
	$categoryId = $cat['id'];
	if(isset($image)) {
		db()->query("UPDATE group_categories SET image ='{$image}' WHERE id='{$categoryId}'");
	}
	return true;
}

function group_add_category($val) {
	/**
	 * @var $title
	 * @var $category
	 * @var $image
	 */
	$expected = array('title' => '', 'category' => '', 'image' => '');
	extract(array_merge($expected, $val));
	$titleSlug = 'group_category_'.md5(time().serialize($val)).'_title';
	$englishValue = $title['english'];
	foreach($title as $langId => $t) {
		if(!$t) $t = $englishValue;
		add_language_phrase($titleSlug, $t, $langId, 'group-category');
	}
	$slug = toAscii($englishValue);
	if(empty($slug)) $slug = md5(time());
	if(group_category_exists($slug)) $slug = md5($slug.time());
	db()->query("INSERT INTO group_categories (title, slug,image) VALUES('{$titleSlug}', '{$slug}','{$image}')");
	$insertedId = db()->insert_id;
	return true;
}

function group_category_exists($slug) {
	$db = db()->query("SELECT id FROM group_categories WHERE slug='{$slug}' LIMIT 1");
	if($db and $db->num_rows > 0) return true;
	return false;
}

function find_group_category($slug) {
	$db = db()->query("SELECT * FROM group_categories WHERE slug='{$slug}' LIMIT 1");
	if($db and $db->num_rows > 0) {
		return $db->fetch_assoc();
	}
}