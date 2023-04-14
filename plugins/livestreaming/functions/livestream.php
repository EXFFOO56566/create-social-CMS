<?php

function add_livestream($val) {
	$expected = array(
		'title' => '',
		'description' => '',
		'privacy' => '',
		'media_type' => '',
		'broadcast_token' => '',
		'status' => 1,
		'entity_type' => 'user',
		'entity_id' => get_userid(),
		'category_id' => '',
		'preview_path' => '',
		'auto_posted' => 0
	);

	/**
	 * @var $title
	 * @var $description
	 * @var $privacy
	 * @var $code
	 * @var $source
	 * @var $file_path
	 * @var $entity_id
	 * @var $entity_type
	 * @var $status
	 * @var $category_id
	 * @var $preview_path
	 * @var $auto_posted
	 */
	extract(array_merge($expected, $val));

	$result = array('result' => true);
	$result = fire_hook('can.start.livestream', $result, array($entity_type, $entity_id));
	if(!$result['result']) return false;
	$livestream_preview = input_file('livestream_preview');

	if($livestream_preview) {
		$uploader = new Uploader($livestream_preview);
		$uploader->setPath(get_userid().'/'.date('Y').'/livestreams/preview/')->disableCDN();
		$uploader->uploadFile();
		$preview_path = $uploader->result();
	}
	$status = 1; //0 mean cancelled, 1 means streaming, 2 means finished and 3 means on timeline 
	$time = time();
	$userid = get_userid();
	$slug = toAscii($title);
	if(empty($slug)) $slug = md5(time());
	if(livestream_exists($slug)) {
		$slug = md5($slug.time());
	}

	db()->query("INSERT INTO livestreams (auto_posted,title,slug,description,user_id,entity_type,entity_id,preview_path,category_id,media_type,status,privacy,broadcast_token, start_time,end_time,updated_last) VALUES(
        '{$auto_posted}','{$title}','{$slug}', '{$description}','{$userid}','{$entity_type}','{$entity_id}','{$preview_path}','{$category_id}','{$media_type}','{$status}','{$privacy}','{$broadcast_token}','{$time}','{$time}',{$time}
    )");

	$livestreamId = db()->insert_id;

	$livestream = get_livestream($livestreamId);
	fire_hook('livestream.started', null, array($livestream, $livestreamId));
	fire_hook('creditgift.addcredit.addlivestream', null, array($userid));
	return $livestream;
}

function is_livestream_owner($livestream) {
	if(!is_loggedIn()) return false;
	if($livestream['user_id'] == get_userid()) return true;
	return false;
}

function save_livestream($val, $livestream) {
	$expected = array(
		'title' => '',
		'description' => '',
		'featured' => 0,
		'privacy' => $livestream['privacy'],
		'category' => $livestream['category_id'],
		'file_path' => ''
	);
	/**
	 * @var $title
	 * @var $description
	 * @var $featured
	 * @var $privacy
	 * @var $category
	 */
	extract(array_merge($expected, $val));
	$livestreamId = $livestream['id'];
	db()->query("UPDATE livestreams SET title='{$title}',description='{$description}',featured='{$featured}',category_id='{$category}',privacy='{$privacy}, file_path='{$file_path}' WHERE id='{$livestreamId}'");
	fire_hook('livestream.admin.edited', null, array($livestreamId));

	return true;
}

function delete_livestream($id) {
	$livestream = get_livestream($id);
	//delete the row
	db()->query("DELETE FROM livestreams WHERE id='{$id}'");
	delete_file(path($livestream['preview_path']));
	delete_file(path($livestream['file_path']));

	$query = db()->query("SELECT feed_id FROM feeds WHERE type_id='livestream-started' AND type_data = {$id} AND feed_content = '' AND photos = '' AND link_details = ''");
	if($query->num_rows > 0) {
		$feeds = fetch_all($query);
		foreach($feeds as $feed) {
			remove_feed($feed['feed_id']);
		}
	}
	return true;
}

function livestream_exists($id) {
	$query = db()->query("SELECT slug FROM livestreams WHERE id='{$id}' LIMIT 1");
	if($query and $query->num_rows > 0) return true;
	return false;
}

function get_livestream($id) {
	$db = db()->query("SELECT * FROM livestreams WHERE slug = '{$id}'");
	if($db->num_rows == 0) {
		$db = db()->query("SELECT * FROM livestreams WHERE id='{$id}'");
	}
	return $db->fetch_assoc();
}

function get_livestream_preview($id) {
	return url(get_livestream($id)['preview_path']);
}

function get_all_livestreams($cat, $term, $limit = 10) {

	$sql = "SELECT * FROM livestreams ";

	$where = "file_path IS NOT NULL";
	if($cat and $cat != 'all') {
		$where = " category_id='{$cat}' ";
	}
	if($term) {
		$where .= ($where) ? " AND (title LIKE '%{$term}%' OR description LIKE '%{$term}%' ) " : " (title LIKE '%{$term}%' OR description LIKE '%{$term}%' )";
	}
	if($where) $sql .= "WHERE {$where} ";
	$sql .= "ORDER BY end_time desc";
	return paginate($sql, $limit);
}

function delete_empty_livestreams() {
	$two_minutes_ago = time() - 2 * 60;
	$sql = "SELECT * FROM livestreams file_path IS NULL and updated_last < {$two_minutes_ago}";
	$query = db()->query($sql);
	$results = fetch_all($query);
	foreach($results as $result) delete_livestream($result['id']);
}

function get_livestreams($type, $category = 'all', $term = null, $userid = null, $limit = 10, $filter = 'all', $withTitle = false, $entity_type = null, $entity_id = null) {
	$sql = "SELECT * FROM livestreams ";
	$where_clause = $type == 'ongoing' ? "" : "file_path IS NOT NULL";
	$userid = ($userid) ? $userid : get_userid();
	if($type == 'mine') {
		$where_clause .= ($where_clause) ? " AND user_id='{$userid}' " : " user_id='{$userid}'";
	}

	if($type == 'ongoing') {
		$twenty_five_secs_ago = time() - 25;
		$where_clause .= ($where_clause) ? " AND status='1' AND broadcast_token IS NOT NULL AND updated_last > {$twenty_five_secs_ago}" : " status='1' AND broadcast_token IS NOT NULL AND updated_last > {$twenty_five_secs_ago}";
	}

	if($type == 'user-profile') {
		$privacy_sql = fire_hook('privacy.sql', ' ');
		$user_profile_sql = " entity_type = 'user' AND user_id = '".$userid."' AND (".$privacy_sql.") ";
		$where_clause .= $where_clause ? " AND (".$user_profile_sql.") " : " (".$user_profile_sql.") ";
	}

	if($category and $category != 'all') {
		$subCategories = get_livestream_parent_categories($category);
		if(!empty($subCategories)) {
			$subIds = array();
			foreach($subCategories as $cat) {
				$subIds[] = $cat['id'];
			}
			$subIds = implode(',', $subIds);
			$where_clause .= ($where_clause) ? " AND (category_id='{$category}' OR category_id IN ({$subIds}))" : " (category_id='{$category}' OR category_id IN ({$subIds})) ";
		} else {
			$where_clause .= ($where_clause) ? " AND category_id='{$category}' " : "category_id='{$category}' ";
		}

	}

	if($filter and $filter == 'featured') {
		$where_clause .= ($where_clause) ? " AND featured='1' " : " featured='1' ";
	}
	if($term) {
		$where_clause .= ($where_clause) ? " AND (title LIKE '%{$term}%' OR description LIKE '%{$term}%' ) " : " (title LIKE '%{$term}%' OR description LIKE '%{$term}%' )";
	}

	if($type == 'browse') {
		$privacy_sql = fire_hook('privacy.sql', ' ');
		$where_clause .= $where_clause ? " AND (".$privacy_sql.") " : " (".$privacy_clause.") ";
	}

	if($entity_type && $entity_id) {
		$entity_sql = "entity_type = '".$entity_type."' AND entity_id = ".$entity_id;
		$where_clause .= $where_clause ? " AND (".$entity_sql.") " : " (".$entity_sql.") ";
	}

	if($where_clause) $sql .= " WHERE {$where_clause} ";
	//if ($type != 'mine') $sql .= " AND status='1'";
	if($withTitle) $sql .= " AND title != '' ";
	if($filter and $filter == 'top') {
		$sql .= " ORDER BY view_count desc";
	} else {
		$sql .= " ORDER BY start_time desc";
	}

	$limit = ($limit) ? $limit : config('livestream-list-limit', 10);
	return paginate($sql, $limit);
}

function get_related_livestreams($livestream, $limit = 6) {
	$title = $livestream['title'];
	$explode = explode(" ", $title);
	$livestreamId = $livestream['id'];
	$sql = "SELECT * FROM livestreams WHERE file_path IS NOT NULL id != '{$livestreamId}'  AND (";
	$where = "";
	foreach($explode as $t) {
		$where .= ($where) ? " OR title LIKE '%{$t}%' " : "title LIKE '%{$t}%'";
	}
	$sql .= $where.') ORDER BY start_time desc';
	return paginate($sql, $limit);

}

function get_livestream_owner($livestream) {
	$result = array('name' => '', 'image' => '', 'link' => '', 'id' => '');
	$entity = fire_hook('entity.info', $livestream);
	$result['name'] = $entity['name'];
	$result['image'] = $entity['avatar'];
	$result['link'] = url($entity['id']);
	$result['id'] = $entity['id'];
	return fire_hook('get.livestream.owner', $result, array($livestream));
}

function get_livestream_url($livestream) {
	return url('livestream/'.$livestream['id']);
}

function get_livestream_categories() {
	$cacheName = "livestream-categories";
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$db = db()->query("SELECT * FROM livestream_categories WHERE parent_id='0' ORDER BY category_order ASC");
		$result = fetch_all($db);
		set_cacheForever($cacheName, $result);
		return $result;
	}
}

function get_livestream_category($id) {
	$query = db()->query("SELECT * FROM livestream_categories WHERE id='{$id}'");
	if($query) return $query->fetch_assoc();
	return false;
}

function get_livestream_parent_categories($id) {
	$cacheName = 'livestream-parent-categories-'.$id;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$db = db()->query("SELECT * FROM livestream_categories WHERE parent_id='{$id}' ORDER BY category_order ASC");
		$result = fetch_all($db);
		set_cacheForever($cacheName, $result);
		return $result;
	}
}

function update_livestream_category_order($catId, $no, $parentId = null) {
	db()->query("UPDATE livestream_categories SET category_order='{$no}' WHERE id='{$catId}'");
	if($parentId) {
		forget_cache('livestream-parent-categories-'.$parentId);
	} else {
		forget_cache("livestream-categories");
	}
	return true;
}

function delete_livestream_category($category) {
	$id = $category['id'];
	db()->query("DELETE FROM livestream_categories WHERE id='{$id}'");
	forget_cache('livestream-categories');
	if($category['parent_id']) forget_cache('livestream-parent-categories-'.$category['parent_id']);
	return true;
}

function save_livestream_category($val, $cat) {
	/**
	 * @var $title
	 * @var $category
	 */
	extract($val);
	$englishValue = $title['english'];
	$slug = $cat['title'];
	foreach($title as $langId => $t) {
		if(!$t) $t = $englishValue;
		(phrase_exists($langId, $slug)) ? update_language_phrase($slug, $t, $langId, 'livestream-category') : add_language_phrase($slug, $t, $langId, 'livestream-category');
	}

	$categoryId = $cat['id'];
	db()->query("UPDATE livestream_categories SET parent_id='{$category}' WHERE id='{$categoryId}'");
	fire_hook('livestream.category.edit', null, array($cat));
	forget_cache('livestream-categories');
	if($category) forget_cache('livestream-parent-categories-'.$category);
	return true;
}

function livestream_add_category($val) {
	/**
	 * @var $title
	 * @var $category
	 */
	extract($val);
	$titleSlug = 'livestream_category_'.md5(time().serialize($val)).'_title';
	$englishValue = $title['english'];
	foreach($title as $langId => $t) {
		if(!$t) $t = $englishValue;
		add_language_phrase($titleSlug, $t, $langId, 'livestream-category');
	}
	$slug = toAscii($englishValue);
	if(empty($slug)) $slug = md5(time());
	if(livestream_category_exists($slug)) {
		$slug = md5($slug.time());
	}

	db()->query("INSERT INTO livestream_categories (title,parent_id,slug) VALUES(
    '{$titleSlug}','{$category}','{$slug}'
    )");

	$insertedId = db()->insert_id;
	fire_hook('livestream.category.add', null, array($insertedId));
	forget_cache('livestream-categories');
	if($category) forget_cache('livestream-parent-categories-'.$category);
	return true;
}

function livestream_category_exists($slug) {
	$db = db()->query("SELECT id FROM livestream_categories WHERE slug='{$slug}' LIMIT 1");
	if($db and $db->num_rows > 0) return true;
	return false;
}

function get_livestream_servers() {
	$cacheName = "livestream-servers";
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$db = db()->query("SELECT * FROM livestream_servers ORDER BY title ASC");
		$result = fetch_all($db);
		set_cacheForever($cacheName, $result);
		return $result;
	}
}

function get_livestream_servers_for_settings() {
	$cacheName = "livestream-servers-for-settings";
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$db = db()->query("SELECT * FROM livestream_servers ORDER BY title ASC");
		$results = fetch_all($db);
		$return = array();
		foreach($results as $result) {
			$return[$result['id']] = $result['title'];
		}
		set_cacheForever($cacheName, $return);
		return $return;
	}
}

function get_livestream_server($id) {
	$query = db()->query("SELECT * FROM livestream_servers WHERE id='{$id}'");
	if($query) return $query->fetch_assoc();
	return false;
}

function delete_livestream_server($server) {
	$id = $server['id'];
	db()->query("DELETE FROM livestream_servers WHERE id='{$id}'");
	forget_cache('livestream-servers');
	forget_cache('livestream-servers-for-settings');
	return true;
}

function save_livestream_server($val, $server) {
	/**
	 * @var $title
	 * @var $address
	 * @var $type
	 * @var $port
	 */
	$expected = array('title' => '', 'address' => '', 'port' => 0, 'type' => '');
	extract(array_merge($expected, $val));

	$server_id = $server['id'];
	$db = db();
	$sql = "UPDATE livestream_servers SET port = '".$port."', address = '".$address."',  title = '".$title."',  `type` = '".$type."' WHERE id = '".$server_id."'";
	$db->query($sql);
	fire_hook('livestream.server.edit', null, array($server));
	forget_cache('livestream-servers');
	forget_cache('livestream-servers-for-settings');
	return true;
}

function livestream_add_server($val) {
	/**
	 * @var $title
	 * @var $address
	 * @var $type
	 * @var $port
	 */

	$expected = array('title' => '', 'address' => '', 'port' => 0, 'type' => '');
	extract(array_merge($expected, $val));
	$db = db();
	$sql = "INSERT INTO livestream_servers (title, address, port, `type`) VALUES('".$title."','".$address."','".$port."', '".$type."')";
	$db->query($sql);
	$server_id = $db->insert_id;
	fire_hook('livestream.server.add', null, array($server_id));
	forget_cache('livestream-servers');
	forget_cache('livestream-servers-for-settings');

	return true;
}

function livestream_server_exists($title) {
	$db = db()->query("SELECT id FROM livestream_servers WHERE title='{$title}' LIMIT 1");
	if($db and $db->num_rows > 0) return true;
	return false;
}

function get_livestream_attributes($livestream, $ffmpeg) {
	if(!file_exists($ffmpeg)) {
		exit('FFMPEG does not exist in specified path');
	}
	$livestream_attributes = array();
	$command = $ffmpeg.' -i '.$livestream.' -vstats 2>&1';
	$output = shell_exec($command);
	for($i = 2; $i <= 3; $i++) {
		$regex = '([^,]*),';
		for($j = 1; $j < $i; $j++) {
			$regex .= '([^,]*),';
		}
		$k = false;
		$regex_sizes = "/livestream: ".$regex." ([0-9]{1,4})x([0-9]{1,4})/";
		if(preg_match($regex_sizes, $output, $regs)) {
			$livestream_attributes['codec'] = $regs [1] ? $regs [1] : null;
			$livestream_attributes['width'] = $regs [(count($regs) - 2)] ? $regs [(count($regs) - 2)] : null;
			$livestream_attributes['height'] = $regs [(count($regs) - 1)] ? $regs [(count($regs) - 1)] : null;
			$k = true;
		}
		if($k) break;
	}
	$regex_duration = "/Duration: ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2}).([0-9]{1,2})/";
	if(preg_match($regex_duration, $output, $regs)) {
		$livestream_attributes['hours'] = $regs [1] ? $regs [1] : null;
		$livestream_attributes['mins'] = $regs [2] ? $regs [2] : null;
		$livestream_attributes['secs'] = $regs [3] ? $regs [3] : null;
		$livestream_attributes['ms'] = $regs [4] ? $regs [4] : null;
	}
	$livestream_attributes['length'] = (float) strtotime('1970-01-01 '.$livestream_attributes['hours'].':'.$livestream_attributes['mins'].':'.$livestream_attributes['secs'].' UTC').'.'.$livestream_attributes['ms'];
	return $livestream_attributes;
}

function combineVideoAudio($video_file, $audio_file) {
	$OSList = array
	(
		'Windows 3.11' => 'Win16',
		'Windows 95' => '(Windows 95)|(Win95)|(Windows_95)',
		'Windows 98' => '(Windows 98)|(Win98)',
		'Windows 2000' => '(Windows NT 5.0)|(Windows 2000)',
		'Windows XP' => '(Windows NT 5.1)|(Windows XP)',
		'Windows Server 2003' => '(Windows NT 5.2)',
		'Windows Vista' => '(Windows NT 6.0)',
		'Windows 7' => '(Windows NT 7.0)',
		'Windows 8' => '(Windows NT 8.1)',
		'Windows 10' => '(Windows NT 10.0)',
		'Windows NT 4.0' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)',
		'Windows ME' => 'Windows ME',
		'Open BSD' => 'OpenBSD',
		'Sun OS' => 'SunOS',
		'Linux' => '(Linux)|(X11)',
		'Mac OS' => '(Mac_PowerPC)|(Macintosh)',
		'QNX' => 'QNX',
		'BeOS' => 'BeOS',
		'OS/2' => 'OS/2',
		'Search Bot' => '(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp)|(MSNBot)|(Ask Jeeves/Teoma)|(ia_archiver)'
	);
	foreach($OSList as $CurrOS => $Match) if(preg_match("/".$Match."/i", $_SERVER['HTTP_USER_AGENT'])) break;
	$merge_dir = path('storage/uploads/'.get_userid().'/'.date('Y').'/livestreams/merged');
	@mkdir($merge_dir, 0777);
	$merged_file = $merge_dir.'/'.time().rand(0, 999).'.webm';
	//exec(config('video-ffmpeg-path').' -i '.$audio_file.' -vn -acodec copy '.$audio_file.'.oga 2>&1', $out, $ret);
	if(!strrpos($CurrOS, "Windows")) $cmd = '-i '.$audio_file.' -i '.$video_file.' -map 0:0 -map 1:0 '.$merged_file;
	else $cmd = ' -i '.$audio_file.'.oga -i '.$video_file.' -c:v mpeg4 -c:a vorbis -b:v 64k -b:a 12k -strict experimental '.$merged_file;

	exec(config('video-ffmpeg-path').' '.$cmd.' 2>&1', $out, $ret);
	if($ret) {
		return ['status' => 0, 'message' => $out];
	} else {
		unlink(path($audio_file));
		unlink(path($video_file));
		return ['status' => 1, 'message' => "Ffmpeg successfully merged audio/video files into single WebM container!\n", 'file_path' => $merged_file];
	}
}

/**
 * @todo
 */
function get_participants($livestreamId, $active = true) {
	$twenty_five_seconds_ago = time() - 35;
	$sql = $active ? "SELECT livestream_histories.user_id, users.username, users.avatar FROM `livestream_histories` INNER JOIN users ON users.id = livestream_histories.user_id WHERE livestream_histories.livestream_id='{$livestreamId}' AND livestream_histories.active_last_time > {$twenty_five_seconds_ago}" : "SELECT  livestream_histories.user_id, users.username, users.avatar FROM `livestream_histories` INNER JOIN users ON users.id = livestream_histories.user_id WHERE livestream_histories.livestream_id='{$livestreamId}'";
	$query = db()->query($sql);
	if(db()->error) {
		exit(db()->error);
	}
	$result = array();
	if($query) {
		while($fetch = $query->fetch_assoc()) {
			$result[$fetch['user_id']]['username'] = $fetch['username'];
			$result[$fetch['user_id']]['avatar'] = url($fetch['avatar']);
		}
	}
	return $result;
}

function update_active($livestreamId) {
	db()->query("UPDATE `livestream_histories` SET active_last_time='".(time() + 3)."' WHERE livestream_id='{$livestreamId}' AND user_id='".get_userid()."'");
	if(db()->error) {
		exit(db()->error);
	}
}

function livestream_ended($livestream_id) {
	$db = db();
	$thirty_sec_ago = time() - 30;
	$sql = "SELECT * FROM livestreams WHERE file_path IS NULL AND updated_last > {$thirty_sec_ago} AND id='{$livestream_id}'";
	$query = $db->query($sql);
	return $query->num_rows > 0 ? false : true;
}

function livestill($id) {
	$now = time() + 2;
	db()->query("UPDATE livestreams SET updated_last='{$now}' WHERE id='{$id}'");
}

function participatingstill($id) {
	db()->query("UPDATE `livestream_histories` SET active_last_time='".time()."' WHERE livestream_id='{$id}' AND user_id='".get_userid()."'");
}

function joinlivestream($id) {
	db()->query("UPDATE livestreams SET view_count = 1 + view_count WHERE id='{$id}'");
	db()->query("INSERT INTO `livestream_histories` (livestream_id, user_id, join_time) VALUES ('".$id."','".get_userid()."','".time()."')");
}

function leavelivestream($id) {
	db()->query("UPDATE `livestream_histories` SET leave_time='".time()."' WHERE livestream_id='{$id}' AND user_id='".get_userid()."'");
}

function count_comment_data($comment_id) {
	$comment_ids = explode("_", $comment_id);
	$return = array();
	foreach($comment_ids as $cm_id) {
		if($cm_id == "") continue;
		$replies = 0;
		if(config('enable-comment-replies', true)) {
			$query = db()->query("SELECT `comment_id` FROM `comments` WHERE `type`='comment' AND `type_id`='{$cm_id}'");
			$replies = $query->num_rows;
		}
		$query = db()->query("SELECT `like_id` FROM `likes` WHERE `type`='comment' AND `type_id`='{$cm_id}'");
		$likes = $query->num_rows;
		$return["a".$cm_id] = array('comment_id' => $cm_id, 'likes' => $likes, 'replies' => $replies);
	}
	return $return;
}

function get_join($livestreamId, $last_get_time) {
	$sql = "SELECT livestream_histories.user_id, users.first_name, users.last_name FROM `livestream_histories` INNER JOIN users ON users.id = livestream_histories.user_id WHERE livestream_histories.livestream_id='{$livestreamId}' AND livestream_histories.join_time > {$last_get_time}";
	$query = db()->query($sql);
	if(db()->error) {
		echo $sql;
		exit(db()->error);
	}
	$result = array();
	if($query) {
		while($fetch = $query->fetch_assoc()) {
			$result[] = array("icon" => get_avatar(75, find_user($fetch['user_id'])), "title" => $fetch['first_name'].' '.$fetch['last_name'], "message" => $fetch['first_name'].' '.$fetch['last_name'].' '.lang('livestreaming::has-joined'));
		}
	}

	return $result;
}

function get_left($livestreamId, $last_get_time) {
	$sql = "SELECT livestream_histories.user_id, users.first_name, users.last_name FROM `livestream_histories` INNER JOIN users ON users.id = livestream_histories.user_id WHERE livestream_histories.livestream_id='{$livestreamId}' AND livestream_histories.leave_time > {$last_get_time}";
	$query = db()->query($sql);
	if(db()->error) {
		exit(db()->error);
	}
	$result = array();
	if($query) {
		while($fetch = $query->fetch_assoc()) {
			$result[] = array("icon" => get_avatar(75, find_user($fetch['user_id'])), "title" => $fetch['first_name'].' '.$fetch['last_name'], "message" => $fetch['first_name'].' '.$fetch['last_name'].' '.lang('livestreaming::has-joined'));
		}
	}
	return $result;
}

function livestream_comment_seen($comment_id) {
	$user_id = get_userid();
	$time = time();
	db()->query("INSERT INTO comment_seen_by (comment_id, user_id, time) VALUES(
    '{$comment_id}','{$user_id}','{$time}'
    )");

	$insertedId = db()->insert_id;
	fire_hook('livestream.server.add', null, array($insertedId));
	forget_cache('livestream-servers');
	forget_cache('livestream-servers-for-settings');
	return true;
}

function livestream_livestream_have_seen($comment_id) {
	$user_id = get_userid();
	$db = db()->query("SELECT id FROM comment_seen_by WHERE user_id='{$user_id}' AND {$comment_id} LIMIT 1");
	if($db and $db->num_rows > 0) return true;
	return false;
}

function get_unseen_comments($type, $typeId, $owner = null) {
	$fields = get_comment_fields();
	$user_id = get_userid();

	$query = db()->query("SELECT {$fields} FROM comments  WHERE `type`='{$type}' AND `type_id`='{$typeId}' AND `user_id`!= {$user_id} AND comment_id NOT IN (SELECT comment_id FROM comment_seen_by WHERE user_id = {$user_id}) ORDER BY `time` DESC");
	if($query) {
		$comments = array();
		while($fetch = $query->fetch_assoc()) {
			$comment = arrange_comment($fetch, $owner);
			if($comment) $comments[] = $comment;
		}
		$comments = array_reverse($comments);
		$commentContent = '';
		foreach($comments as $comment) {
			livestream_comment_seen($comment['comment_id']);
			$commentContent .= view('livestreaming::viewer/display', array('comment' => $comment));
		}
		return $commentContent;
	}
	return '';
}

