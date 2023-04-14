<?php
function add_photo_album($val) {
	$expected = array(
		'name' => '',
		'description' => '',
		'privacy' => '',
		'category' => '',
		'entity_id' => get_userid(),
		'entity_type' => 'user',

	);

	/**
	 * @var $name
	 * @var $description
	 * @var $privacy
	 * @var $category
	 * @var $entity_id
	 * @var $entity_type
	 */
	extract(array_merge($expected, $val));
	$time = time();
	$name = sanitizeText($name);
	$description = sanitizeText($description);
	$category = sanitizeText($category);
	$privacy = sanitizeText($privacy);
	db()->query("INSERT INTO `photo_albums`(entity_id,entity_type,title,description,category_id,privacy,time)VALUES(
        '{$entity_id}','{$entity_type}','{$name}','{$description}','{$category}','{$privacy}','{$time}'
    )");
	$insert = db()->insert_id;
	fire_hook("photo.album.create", null, array(db()->insert_id, $val));
	fire_hook('plugins.users.category.updater', null, array('photo_albums', $val, $insert, 'id'));
	return db()->insert_id;
}

function save_photo_album($val, $album) {
	$expected = array(
		'name' => '',
		'description' => '',
		'privacy' => '',
		'category' => '',
	);

	/**
	 * @var $name
	 * @var $description
	 * @var $privacy
	 * @var $category
	 */
	extract(array_merge($expected, $val));
	$time = time();
	$name = sanitizeText($name);
	$description = sanitizeText($description);
	$category = sanitizeText($category);
	$privacy = sanitizeText($privacy);
	$albumId = $album['id'];
	db()->query("UPDATE photo_albums SET title='{$name}',description='{$description}',category_id='{$category}',privacy='{$privacy}' WHERE id='{$albumId}'");
	if($album['privacy'] != $privacy) {
		//we need to change all photos to this privacy too
		db()->query("UPDATE medias SET privacy='{$privacy}' WHERE album_id='{$albumId}'");
	}
	fire_hook('plugins.users.category.updater', null, array('photo_albums', $val, $albumId, 'id'));
	fire_hook("photo.album.updated", null, array($album));

	return true;
}

function delete_photo_album($album) {
	//let the photos of this album
	$albumId = $album['id'];
	$query = db()->query("SELECT path FROM medias WHERE album_id='$albumId'");
	while($fetch = $query->fetch_assoc()) {
		delete_file($fetch['path']);
	}
	db()->query("DELETE  FROM medias WHERE album_id='$albumId'");
	//now time to delete the album itself
	db()->query("DELETE  FROM photo_albums WHERE id='$albumId'");

	fire_hook("photo.album.deleted", null, array($album));
	return true;
}

function get_photo_albums($type = null, $type_id = null, $category = false, $limit = null, $offset = 0) {
	$limit = ($limit) ? $limit : config('photo-album-listing-per-page', 20);
	$type = (empty($type)) ? 'user' : $type;
	$field = "id,entity_id,entity_type,title,description,time,category_id";
	$field = fire_hook('photo.album.more.field', $field, array());
	$type_id = ($type_id) ? $type_id : get_userid();
	if(!$category) {
		if($type == 'user') {
			$sql = "SELECT {$field} FROM photo_albums";
			$onlyMeException = photoOnlyMeException('', $type_id);
			$sql .= " WHERE entity_type='{$type}' AND entity_id='{$type_id}' AND (privacy='1' OR entity_id IN ({$onlyMeException}) ";
			$sql = fire_hook('more.photos.query.query.filter', $sql, array());
			if($type_id != get_userid()) {
				if(config('relationship-method', 3) != 1) {
					$friendStatus = friend_status($type_id);
					if($friendStatus == 2) {
						$sql .= " OR privacy='2'";
					}
				} else {
					if(is_following($type_id)) {
						$sql .= " OR privacy='2'";
					}
				}

			} else {
				if(is_loggedIn()) $sql .= " OR privacy='2' OR privacy='3' ";
			}
			$sql .= ')';
		} else if($type == 'all') {
			$sql = "SELECT {$field} FROM photo_albums ";
			$sql = fire_hook("use.different.photos.query", $sql, array());
			$userid = isset($userid) ? $userid : get_userid();
			$where_clause = " WHERE entity_type='user' ";
			$where_clause = fire_hook("more.photos.query.filter", $where_clause, array());
			$onlyMeException = photoOnlyMeException($userid);
			$privacy_clause = "(privacy = 1 OR entity_id='{$userid}' OR entity_id IN ({$onlyMeException}) ";
			$users = array(get_userid());
			$users = fire_hook('system.relationship.method', $users, array('photo', $userid));
			$users = implode(',', $users);
			$privacy_clause .= " OR (privacy='2'  AND entity_id IN ({$users}))) ";
			$where_clause .= ($where_clause) ? " AND {$privacy_clause} " : " {$privacy_clause} ";
			if($where_clause) $sql .= " {$where_clause} ";
		}
	} else {
		$sql = "SELECT {$field} FROM photo_albums";
		$sql .= " WHERE entity_type='user' AND (SELECT COUNT(album_id) FROM medias WHERE medias.album_id=photo_albums.id) > 0";
		if($category != 'all') {
			$sql .= "  AND category_id='{$category}' ";
		}
		$sql .= " AND (privacy='1'";
		if(is_loggedIn()) {
			$userid = get_userid();
			$sql .= " OR entity_id='{$userid}')";
		}
		$sql .= ")";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
	}

	$sql .= " GROUP BY id ORDER BY time DESC LIMIT {$offset},{$limit}";
	$query = db()->query($sql);
	$albums = array();
	while($fetch = $query->fetch_assoc()) {
		$album = arrange_photo_album($fetch);
		if($album) $albums[] = $album;
	}

	return $albums;
}

function get_photo_album($albumId, $all = true) {
	$field = "id,entity_id,entity_type,title,description,category_id,privacy,time,default_photo";
	$sql = "SELECT ".$field." FROM photo_albums";
	$sql .= " WHERE id='{$albumId}'";
	$sql = fire_hook("more.photos.query.filter", $sql, array());
	$query = db()->query($sql);
	return ($all) ? arrange_photo_album($query->fetch_assoc()) : $query->fetch_assoc();
}

function arrange_photo_album($album) {
	if(!$album) return false;
	$albumId = $album['id'];
	if($album['entity_type'] == 'user') {
		$user = find_user($album['entity_id'], false);
		if($user) {
			$album['publisher'] = $user;
			$album['publisher']['url'] = profile_url(null, $user);
		}
	} else {
		$album['publisher'] = fire_hook('photo.album.get.publisher', null, array($album));
	}
	if(!(isset($album['publisher']) && $album['publisher'])) return false;

	//let get the last uploaded photo to this album
	$album['photo-count'] = count_photos($album['id'], 'album');
	$sql = "SELECT path FROM medias WHERE album_id='{$albumId}'";
	$sql = fire_hook("more.photos.query.filter", $sql, array());
	$sql .= " ORDER BY id DESC LIMIT 1";
	$query = db()->query($sql);
	$fetch = $query->fetch_assoc();
	if($fetch) {
		$album['image'] = url_img($fetch['path'], 600);
	} else {
		$album['image'] = img("photo::images/default.png");
	}
	return $album;
}

function can_manage_photo_album($album) {
	if(!is_loggedIn()) return false;
	if($album['entity_type'] == 'user' and $album['entity_id'] == get_userid()) return true;
	return false;
}

function can_view_photo_album($album) {
	if($album['privacy'] == 1) return true;
	if($album['privacy'] == 3 and can_manage_photo_album($album)) return true;
	if($album['privacy'] == 2 and relationship_valid($album['entity_id'], 2)) {
		return true;
	}
	if(can_manage_photo_album($album)) return true;
	if(fire_hook("private-photo-permission", array(false), array(app()->profileUser))[0]) return true;
	return false;
}

function count_photos($id, $type = 'album') {
	$sql = '';
	if($type == 'album') {
		$sql = "SELECT id,path FROM medias WHERE album_id='{$id}'";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
	} else {
		$sql = "SELECT id,path FROM medias WHERE type_id='{$id}' AND type='{$type}'";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
	}
	$query = db()->query($sql);
	return $query->num_rows;
}

function get_photos($id, $type = 'album', $limit = null, $offset = 0, $filter = null) {
	$limit = ($limit) ? $limit : config("photo-listing-per-page", 20);
	$privacy_sql = " AND (".fire_hook('privacy.sql', ' ').")";
	$feature_sql = $filter == 'featured' ? ' AND featured = 1' : '';
	if($type == 'album') {
		$sql = "SELECT * FROM medias WHERE file_type = 'image'".$feature_sql.$privacy_sql." AND album_id='{$id}'";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
		$sql .= " ORDER BY `id` DESC LIMIT {$offset}, {$limit}";
	} elseif($type == 'user-all') {
		$sql = "SELECT * FROM medias WHERE file_type = 'image'".$feature_sql.$privacy_sql." AND user_id = '".$id."'";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
		$sql .= " ORDER BY `id` DESC LIMIT ".$offset.", ".$limit;
	} elseif($type == 'user-profile') {
		$sql = "SELECT * FROM medias WHERE file_type = 'image'".$feature_sql.$privacy_sql." AND type_id='{$id}' AND type='profile-avatar'";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
		$sql .= " ORDER BY `id` DESC LIMIT {$offset},{$limit}";
	} elseif($type == 'user-cover') {
		$sql = "SELECT * FROM medias WHERE file_type = 'image'".$feature_sql.$privacy_sql." AND type_id='{$id}' AND type='profile-cover'";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
		$sql .= " ORDER BY `id` DESC LIMIT {$offset},{$limit}";
	} elseif($type == 'user-timeline') {
		$sql = "SELECT * FROM medias";
		$userid = isset($userid) ? $userid : get_userid();
		$where_clause = " WHERE file_type = 'image'".$feature_sql.$privacy_sql." AND type_id = '{$id}' AND (type = 'user-posts')";
		$where_clause = fire_hook("more.photos.query.filter", $where_clause, array());
		$privacy_clause = "(privacy = '1' OR user_id='{$userid}' ";
		if(is_loggedIn()) {
			$users = array(get_userid());
			$users = fire_hook('system.relationship.method', $users, array('photo', $userid));
			$users = implode(',', $users);
			if(!empty ($users)) {
				$privacy_clause .= " OR (privacy='2'  AND user_id IN ({$users}))) ";
				$where_clause .= ($where_clause) ? " AND {$privacy_clause} " : " {$privacy_clause} ";
			}
		}
		if($where_clause) $sql .= " {$where_clause} ";
		$sql .= " ORDER BY `id` DESC LIMIT {$offset}, {$limit}";
	} elseif($type == 'all') {
		$sql = "SELECT * FROM medias ";
		$user_id = isset($id) ? $id : get_userid();
		$where_clause = " WHERE file_type ='image'".$feature_sql.$privacy_sql." AND type != 'profile-avatar' AND type != 'profile-cover' ";
		$where_clause = fire_hook("more.photos.query.filter", $where_clause, array());
		$privacy_clause = "(privacy = '1' OR user_id='{$user_id}' ";
		if(is_loggedIn()) {
			$users = array(get_userid());
			$users = fire_hook('system.relationship.method', $users, array('photo', $user_id));
			$users = implode(',', $users);
			if(!empty ($users)) {
				$privacy_clause .= " OR (privacy='2'  AND user_id IN ({$users}))) ";
				$where_clause .= ($where_clause) ? " AND {$privacy_clause} " : " {$privacy_clause} ";
			}
		} else {
			$privacy_clause .= ") ";
			$where_clause .= ($where_clause) ? " AND {$privacy_clause} " : " {$privacy_clause} ";
		}
		if($where_clause) $sql .= " {$where_clause} ";
		$sql .= "order by id desc LIMIT {$offset}, {$limit}";
	} else {
		$sql = "SELECT * FROM medias WHERE file_type = 'image' AND type_id='{$id}' AND type='{$type}'";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
		$sql .= " ORDER BY `id` DESC LIMIT {$offset},{$limit}";
		$sql = fire_hook('photos.get.sql', $sql, array($type, $id, $limit, $offset));
	}
	$query = db()->query($sql);
	return fetch_all($query);
}

function find_photo($id, $all = true) {
	$query = db()->query("SELECT * FROM medias WHERE id='{$id}'");
	return ($all) ? arrange_photo($query->fetch_assoc()) : $query->fetch_assoc();
}

function arrange_photo($fetch) {
	$photo = $fetch;

	if(!$photo) return false;

	//lets get the publisher
    $photoType = array('profile-cover', 'user-posts', 'album', 'profile-avatar');
    $photoType = fire_hook('photo.fetch.type',$photoType);

    if(in_array($fetch['type'], $photoType)) {
		$user = find_user($fetch['type_id'], false);
		if($user) {
			$photo['publisher'] = $user;
			$photo['publisher']['avatar'] = get_avatar(75, $user);
			$photo['publisher']['url'] = profile_url(null, $user);
		}
	} else {
		$photo['publisher'] = fire_hook('photo.get.publisher', null, array($photo));
	}

	if(!isset($photo['publisher'])) return false;

	//album
	if($photo['album_id']) {
		$photo['album'] = get_photo_album($photo['album_id'], false);
	}

	//lets determine the editor of this photo for comments
	$photo['editor'] = array(
		'id' => get_userid(),
		'type' => 'user',
		'avatar' => get_avatar(75)
	);
	//any other can override the editor if they like for example page e.t.c
	$photo['imageBefore'] = get_photo_before($photo);
	$photo['imageAfter'] = get_photo_after($photo);
	$photo = fire_hook("photo.arrange", $photo);
	return $photo;
}

function get_photo_after($photo) {
	$photoId = $photo['id'];
	$type = $photo['type'];
	$type_id = $photo['type_id'];
	$entity_type = $photo['entity_type'];
	$entity_id = $photo['entity_id'];

	if(isset($photo['album'])) {
		$albumId = $photo['album']['id'];
		$sql = "SELECT id, path FROM `medias` WHERE file_type = 'image' AND id = (SELECT min(id) FROM medias WHERE album_id='{$albumId}' AND id > {$photoId})";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
		$sql .= " ORDER BY id ASC LIMIT 1";
		$query = db()->query($sql);
	} else {
		$sql = "SELECT id, path FROM `medias` WHERE file_type = 'image' AND id = (SELECT min(id) FROM medias WHERE type = '{$type}' AND type_id = '{$type_id}' AND entity_type = '{$entity_type}' AND entity_id = '{$entity_id}' AND id > {$photoId})";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
		$sql .= " ORDER BY id ASC LIMIT 1";
		$query = db()->query($sql);
	}
	return ($query and $query->num_rows > 0) ? $query->fetch_assoc() : false;
}

function get_photo_before($photo) {
	$photoId = $photo['id'];
	$type = $photo['type'];
	$type_id = $photo['type_id'];
	$entity_type = $photo['entity_type'];
	$entity_id = $photo['entity_id'];
	if(isset($photo['album'])) {
		$albumId = $photo['album']['id'];
		$sql = "SELECT id, path FROM `medias` WHERE file_type = 'image' AND album_id = '{$albumId}' AND id = (SELECT max(id) FROM medias WHERE album_id='{$albumId}' AND id < {$photoId})";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
		$sql .= " ORDER BY id ASC LIMIT 1";
		$query = db()->query($sql);
	} else {
		$sql = "SELECT id, path FROM `medias` WHERE file_type = 'image' AND type = '{$type}' AND type_id = '{$type_id}' AND id = (SELECT max(id) FROM medias WHERE type='{$type}' AND type_id='{$type_id}' AND entity_type = '{$entity_type}' AND entity_id = '{$entity_id}' AND id < {$photoId})";
		$sql = fire_hook("more.photos.query.filter", $sql, array());
		$sql .= " ORDER BY id ASC LIMIT 1";
		$query = db()->query($sql);
	}

	return ($query and $query->num_rows > 0) ? $query->fetch_assoc() : false;
}

function count_user_photos($userid, $type = 'profile-avatar') {
	$sql = "SELECT id FROM medias WHERE type_id='{$userid}' AND type='{$type}'";
	$sql = fire_hook("more.photos.query.filter", $sql, array());
	$query = db()->query($sql);
	return $query->num_rows;
}

function get_last_user_photo($userid, $type = 'profile-avatar') {
	$sql = "SELECT id,path FROM medias WHERE type_id='{$userid}' AND type='{$type}'";
	$sql = fire_hook("more.photos.query.filter", $sql, array());
	$sql .= " ORDER BY id DESC";
	$query = db()->query($sql);
	return $query->fetch_assoc();
}

function is_photo_owner($photo, $admin = false) {
	if(!is_loggedIn()) return false;
	if(is_admin() and $admin) return true;
	if($photo['user_id'] == get_userid()) return true;
	return false;
}

function delete_photo($id, $photo = null) {
	$photo = $photo ? $photo : find_photo($id);
	if(!is_photo_owner($photo, true)) return false;
	if(plugin_loaded('like')) delete_likes('photo', $id);
	if(plugin_loaded('comment')) delete_comments('photo', $id);
	$db = db();
	if($photo['ref_name'] && $photo['ref_name'] == 'feed') {
		$sql = "SELECT COUNT(id) FROM medias WHERE ref_name = 'feed' AND ref_id = '".$photo['ref_id']."'";
		$query = $db->query($sql);
		if(!$query->fetch_row()[0]) {
			remove_feed($photo['ref_id']);
		}
	}
	$user = get_user();
	if($user['avatar'] == $photo['path']) {
		update_user(array('avatar' => ''));
	}
	$db->query("DELETE FROM medias WHERE id='".$id."'");
	foreach(array(75, 200, 600, 920) as $size) {
		delete_file(path(str_replace('%w', $size, $photo['path'])));
	}
	fire_hook('photo.deleted', $id);
	return true;
}

function delete_photos($entity_type, $entity_id) {
	$db = db();
	$query = $db->query("SELECT * FROM medias WHERE entity_type = '".$entity_type."' AND type_id = '".$entity_id."'");
	while($row = $query->fetch_assoc()) {
		delete_photo($row['id']);
	}
	$db->query("DELETE FROM medias WHERE type = '".$entity_type."' AND type_id = '".$entity_id."'");
}

function make_photo_db($id) {
	$photo = find_photo($id);
	if(!is_photo_owner($photo)) return false;
	if(preg_match('#posts#', $photo['path'])) {
		$path = str_replace('%w', '600', $photo['path']);
	} else {
		$path = $photo['path'];
	}
	update_user(array('avatar' => $path));
}

function is_album_owner($album, $admin = false) {
	if(!is_loggedIn()) return false;
	if(is_admin() and $admin) return true;
	if($album['entity_id'] == get_userid()) return true;
	return false;
}

function photo_num_photos() {
	$db = db();
	$sql = "SELECT COUNT(id) FROM medias";
	$sql .= " WHERE file_type = 'image'";
	$sql = fire_hook("more.photos.query.filter", $sql, array());
	$query = $db->query($sql);
	$result = $query->fetch_row();
	return $result[0];
}

function photo_list($limit = 20) {
	$photo_fields = "medias.*";
	$photo_fields = fire_hook('photo.list.fields', $photo_fields);
	$sql = "SELECT ".$photo_fields." FROM medias ";
	$userid = isset($userid) ? $userid : get_userid();
	$where_clause = " WHERE file_type ='image' AND type != 'profile-avatar' AND type != 'profile-cover' ";
	$where_clause = fire_hook("photo.list.where.clause", $where_clause);
	if(!is_admin()) {
		$where_clause = fire_hook("more.photos.query.filter", $where_clause, array());
		$privacy_clause = "(privacy = '1' OR user_id='{$userid}' ";
		if(is_loggedIn()) {
			$users = array(get_userid());
			$users = fire_hook('system.relationship.method', $users, array('photo', $userid));
			$users = implode(',', $users);
			if(!empty ($users)) {
				$privacy_clause .= " OR (privacy='2'  AND user_id IN ({$users}))) ";
				$where_clause .= ($where_clause) ? " AND {$privacy_clause} " : " {$privacy_clause} ";
			}
		} else {
			$privacy_clause .= ") ";
			$where_clause .= ($where_clause) ? " AND {$privacy_clause} " : " {$privacy_clause} ";
		}
	}
	if($where_clause) $sql .= " {$where_clause} ";
	$photo_order = "ORDER BY id DESC";
	$photo_order = fire_hook('photo.order.sql', $photo_order);
	$sql .= $photo_order;
	return paginate($sql, $limit);
}

function save_photo($val) {
	/**
	 * @var $featured
	 * @var $photo_id
	 */
	$expected = array('featured' => 0, 'photo_id' => 0);
	extract(array_merge($expected, $val));
	$db = db();
	$db->query("UPDATE `medias` SET `featured` = ".$featured." WHERE `id` = ".$photo_id);
	fire_hook('plugins.users.category.updater', null, array('medias', '', $photo_id, 'id'));
	return true;
}

function url_is_image($url) {
	if(!$fp = curl_init($url)) return false;
	$match = preg_replace("#(.+)?\.(\w+)(\?.+)?#", "$2", $url);
	if(($match == 'jpg') || ($match == 'png') || ($match == 'gif') || ($match == 'jpeg')) return true;
	return false;
}

function photoOnlyMeException($userId = null, $typeId = null) {
	$exception = array(0);
	if($userId) {
		$users = array(get_userid());
		$users = fire_hook('system.relationship.method', $users, array('photo', $userId));
		foreach($users as $user) {
			$userDetails = find_user($user);
			$data = unserialize($userDetails['album_private_exception']);
			if(is_array($data)) {
				if(in_array($userId, $data)) {
					$exception[] = $user;
				}
			}
		}
	}
	if($typeId) {
		$user = find_user($typeId);
		$data = $user['album_private_exception'];
		if($data != "") {
			$data = unserialize($data);
			if(is_array($data)) {
				if(in_array(get_userid(), $data)) {
					$exception[] = $typeId;
				}
			}
		}
	}
	$exception = implode(',', $exception);
	return $exception;
}

function admin_get_photo_albums($keyword = null) {
	$limit = input('limit', 40);
	$whereClause = "";
	if($keyword) $whereClause = "WHERE title LIKE '%{$keyword}%' OR description LIKE '%{$keyword}%'";
	$sql = "SELECT * FROM photo_albums {$whereClause}";
	return paginate($sql, $limit);
}