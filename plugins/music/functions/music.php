<?php

function add_music($val) {
	/**
	 * @var $title
	 * @var $artist
	 * @var $album
	 * @var $privacy
	 * @var $code
	 * @var $source
	 * @var $file_path
	 * @var $entity_id
	 * @var $entity_type
	 * @var $status
	 * @var $category_id
	 * @var $cover_art
	 * @var $auto_posted
	 * @var $entity
	 */
	$expected = array('title' => '', 'artist' => '', 'album' => '', 'privacy' => 1, 'code' => '', 'source' => '', 'status' => 1, 'file_path' => '', 'entity_type' => 'user', 'entity_id' => get_userid(), 'category_id' => '', 'cover_art' => '', 'entity' => '', 'auto_posted' => 0);
	extract(array_merge($expected, $val));
	$entity = explode('-', $entity);
	if(count($entity) == 2) {
		$entity_type = $entity[0];
		$entity_id = $entity[1];
	}
	if(!isset($entity_type) || !isset($entity_type)) {
		return false;
	}
	$result = array('result' => true);
	$result = fire_hook('can.post.music', $result, array($entity_type, $entity_id));
	if(!$result['result']) return false;
	$music_file = input_file('music_file');
	if($music_file) {
		$uploader = new Uploader($music_file, 'audio');
		$uploader->setPath(get_userid().'/'.date('Y').'/musics/');
		$uploader->uploadFile();
		$file_path = $uploader->result();
		$status = 1;
	}
	$time = time();
	$userid = get_userid();
	$slug = toAscii($title);
	if(empty($slug)) $slug = md5(time());
	if(music_exists($slug) || in_array($slug, array('playlist'))) {
		$slug = md5($slug.time());
	}
	db()->query("INSERT INTO musics (auto_posted, title, slug, artist, album, user_id, entity_type, entity_id, cover_art, category_id, source, code, status, file_path, privacy, time) VALUES('{$auto_posted}', '{$title}', '{$slug}', '{$artist}', '{$album}', '{$userid}', '{$entity_type}', '{$entity_id}', '{$cover_art}', '{$category_id}', '{$source}', '{$code}', '{$status}', '{$file_path}', '{$privacy}', '{$time}' )");
	$music_id = db()->insert_id;
	$music = get_music($music_id);
	fire_hook('music.added', null, array($music, $music_id));
	fire_hook('plugins.users.category.updater', null, array('musics', $val, $music_id));
	return $music;
}

function is_music_owner($music) {
	if(!is_loggedIn()) return false;
	if($music['user_id'] == get_userid()) return true;
	return false;
}

function save_music($val, $music) {
	$expected = array('title' => '', 'artist' => '', 'album' => '', 'featured' => $music['featured'], 'privacy' => $music['privacy'], 'category' => '');
	/**
	 * @var $title
	 * @var $artist
	 * @var $album
	 * @var $cover_art
	 * @var $featured
	 * @var $privacy
	 * @var $category
	 */
	extract(array_merge($expected, $val));
	$music_id = $music['id'];
	db()->query("UPDATE musics SET title = '{$title}', artist = '{$artist}', album = '{$album}', cover_art = '{$cover_art}', featured = '{$featured}', category_id = '{$category}', privacy = '{$privacy}' WHERE id = '{$music_id}'");
	fire_hook('music.admin.edited', null, array($music_id));
	fire_hook('plugins.users.category.updater', null, array('musics', $val, $music_id));
	return true;
}

function delete_music($id) {
	$music = get_music($id);
	//delete the row
	db()->query("DELETE FROM musics WHERE id='{$id}'");
	if($music['source'] == 'upload') {
		delete_file(path($music['cover_art']));
		delete_file(path($music['file_path']));
	}
	$query = db()->query("SELECT feed_id FROM feeds WHERE type_id='upload-music' AND type_data = {$id} AND feed_content = '' AND photos = '' AND link_details = ''");
	if($query->num_rows > 0) {
		$feeds = fetch_all($query);
		foreach($feeds as $feed) {
			remove_feed($feed['feed_id']);
		}
	}
	return true;
}

function music_exists($slug) {
	$query = db()->query("SELECT slug FROM musics WHERE slug='{$slug}' LIMIT 1");
	if($query and $query->num_rows > 0) return true;
	return false;
}

function get_music($id) {
	if(is_numeric($id)) {
		$sql = "SELECT * FROM musics WHERE id='{$id}'";
	} else {
		$sql = "SELECT * FROM musics WHERE slug='{$id}'";
	}
	$sql .= " LIMIT 1";
	$query = db()->query($sql);
	if($query) return $query->fetch_assoc();
	return false;
}

function get_all_musics($cat, $term, $limit = 10) {
	$sql = "SELECT * FROM musics ";
	$where = "";
	if($cat and $cat != 'all') $where = " category_id='{$cat}' ";
	if($term) $where .= ($where) ? " AND (title LIKE '%{$term}%' OR artist LIKE '%{$term}%' OR album LIKE '%{$term}%') " : " (title LIKE '%{$term}%' OR artist LIKE '%{$term}%' OR album LIKE '%{$term}%')";
	if($where) $sql .= "WHERE {$where} ";
	$sql .= "ORDER BY time desc";
	return paginate($sql, $limit);
}

function get_musics($type, $category = 'all', $term = null, $user_id = null, $limit = 10, $filter = 'all', $with_title = false, $entity_type = null, $entity_id = null) {
	$sql = "SELECT * FROM musics ";
	$where_clause = "";
	$user_id = $user_id ? $user_id : get_userid();
	if($type == 'mine') {
		$where_clause .= $where_clause ? " AND user_id = '".$entity_id."' " : " user_id = '".$user_id."'";
	}
	if($type == 'user-profile') {
		$privacy_sql = fire_hook('privacy.sql', ' ');
		$user_profile_sql = " entity_type = 'user' AND user_id = '".$user_id."' AND (".$privacy_sql.") ";
		$where_clause .= $where_clause ? " AND (".$user_profile_sql.") " : " (".$user_profile_sql.") ";
	} else {
		$where_clause = trim(fire_hook('music.type.more', ($where_clause ? $where_clause : ' '), array($type, $user_id)));
	}
	if($category && $category != 'all') {
        $subCategories = get_music_parent_categories($category);
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
		$where_clause .= $where_clause ? " AND featured = '1' " : " featured = '1' ";
	}
	if($term) {
		$where_clause .= $where_clause ? " AND (title LIKE '%".$term."%' OR artist LIKE '%".$term."%' OR album LIKE '%".$term."%' ) " : " (title LIKE '%".$term."%' OR artist LIKE '%".$term."%' OR album LIKE '%".$term."%')";
	}
	if($type == 'browse') {
		$privacy_sql = fire_hook('privacy.sql', ' ');
		$where_clause .= $where_clause ? " AND (".$privacy_sql.") " : " (".$privacy_sql.") ";
	}
	if($entity_type && $entity_id) {
		$entity_sql = "entity_type = '".$entity_type."' AND entity_id = ".$entity_id;
		$where_clause .= $where_clause ? " AND (".$entity_sql.") " : " (".$entity_sql.") ";
	}
	$where_clause = fire_hook('users.category.filter', $where_clause, array($where_clause));
	if($where_clause) {
		$sql .= " WHERE {$where_clause} ";
	}
	if($type != 'mine') {
		$sql .= " AND status = 1";
	}
	if($with_title) {
		$sql .= " AND title != '' ";
	}
	$sql = fire_hook('music.after.query', $sql, array($sql));
	if($filter and $filter == 'top') {
		$sql .= " ORDER BY play_count desc";
	} else {
		$sql .= " ORDER BY time desc";
	}
	$limit = $limit ? $limit : config('music-list-limit', 10);
	return paginate($sql, $limit);
}

function get_related_musics($music, $limit = 6) {
	$title = $music['title'];
	$explode = explode(" ", $title);
	$music_id = $music['id'];
	$sql = "SELECT * FROM musics WHERE id != '{$music_id}'  AND (";
	$where = "";
	foreach($explode as $t) {
		$where .= ($where) ? " OR title LIKE '%{$t}%' " : "title LIKE '%{$t}%'";
	}
	$privacy_sql = fire_hook('privacy.sql', ' ');
	$sql .= $where.') AND (".$privacy_sql.") ORDER BY time desc';
	return paginate($sql, $limit);

}

function get_music_owner($music) {
	$result = array('name' => '', 'image' => '', 'link' => '', 'id' => '');
	$entity = fire_hook('entity.info', $music);
	$result['name'] = $entity['name'];
	$result['image'] = $entity['avatar'];
	$result['link'] = url($entity['id']);
	$result['id'] = $entity['id'];
	return fire_hook('get.music.owner', $result, array($music));
}

function get_music_url($music) {
	return url_to_pager('music-page', array('id' => $music['slug']));
}

function get_music_categories() {
//    $cacheName = "music-categories";
//    if(cache_exists($cacheName)) {
//        return get_cache($cacheName);
//    } else {
	$db = db()->query("SELECT * FROM music_categories WHERE parent_id='0' ORDER BY `order` ASC");
	$result = fetch_all($db);
//        set_cacheForever($cacheName, $result);
	return $result;
//    }
}

function get_music_category($id) {
	$query = db()->query("SELECT * FROM music_categories WHERE id='{$id}'");
	if($query) return $query->fetch_assoc();
	return false;
}

function get_music_parent_categories($id) {
//    $cacheName = 'music-parent-categories-'.$id;
//    if(cache_exists($cacheName)) {
//        return get_cache($cacheName);
//    } else {
	$db = db()->query("SELECT * FROM music_categories WHERE parent_id='{$id}' ORDER BY `order` ASC");
	$result = fetch_all($db);
//      set_cacheForever($cacheName, $result);
	return $result;
//    }
}

function update_music_order($catId, $no, $parentId = null) {
	db()->query("UPDATE music_categories SET `order`='{$no}' WHERE id='{$catId}'");
	if($parentId) {
		forget_cache('music-parent-categories-'.$parentId);
	} else {
		forget_cache("music-categories");
	}
	return true;
}

function delete_music_category($category) {
	$id = $category['id'];
	db()->query("DELETE FROM music_categories WHERE id='{$id}'");
	forget_cache('music-categories');
	if($category['parent_id']) forget_cache('music-parent-categories-'.$category['parent_id']);
	return true;
}

function save_music_category($val, $cat) {
	/**
	 * @var $title
	 * @var $category
	 */
	extract($val);
	$englishValue = $title['english'];
	$slug = $cat['title'];
	foreach($title as $langId => $t) {
		if(!$t) $t = $englishValue;
		(phrase_exists($langId, $slug)) ? update_language_phrase($slug, $t, $langId, 'music-category') : add_language_phrase($slug, $t, $langId, 'music-category');
	}
	$categoryId = $cat['id'];
	db()->query("UPDATE music_categories SET parent_id='{$category}' WHERE id='{$categoryId}'");
	fire_hook('music.category.edit', null, array($cat));
	forget_cache('music-categories');
	if($category) forget_cache('music-parent-categories-'.$category);
	return true;
}

function music_add_category($val) {
	/**
	 * @var $title
	 * @var $category
	 */
	extract($val);
	$titleSlug = 'music_category_'.md5(time().serialize($val)).'_title';
	$englishValue = $title['english'];
	foreach($title as $langId => $t) {
		if(!$t) $t = $englishValue;
		add_language_phrase($titleSlug, $t, $langId, 'music-category');
	}
	$slug = toAscii($englishValue);
	if(empty($slug)) $slug = md5(time());
	if(music_category_exists($slug)) $slug = md5($slug.time());
	db()->query("INSERT INTO music_categories (title, parent_id, slug) VALUES('{$titleSlug}', '{$category}', '{$slug}')");
	$insertedId = db()->insert_id;
	fire_hook('music.category.add', null, array($insertedId));
	forget_cache('music-categories');
	if($category) forget_cache('music-parent-categories-'.$category);
	return true;
}

function music_category_exists($slug) {
	$db = db()->query("SELECT id FROM music_categories WHERE slug='{$slug}' LIMIT 1");
	if($db and $db->num_rows > 0) return true;
	return false;
}

function get_playlists($type, $term = null, $user_id = null, $limit = 10, $filter = 'all', $with_title = false, $entity_type = null, $entity_id = null) {
	$sql = "SELECT * FROM music_playlists ";
	$where_clause = "";
	$user_id = ($user_id) ? $user_id : get_userid();
	if($type == 'mine') $where_clause .= $where_clause ? " AND user_id = '".$user_id."' " : " user_id = '".$user_id."'";
	if($type == 'user-profile') {
		$privacy_sql = fire_hook('privacy.sql', ' ');
		$user_profile_sql = " entity_type = 'user' AND user_id = '".$user_id."' AND (".$privacy_sql.") ";
		$where_clause .= $where_clause ? " AND (".$user_profile_sql.") " : " (".$user_profile_sql.") ";
	}
	if($filter and $filter == 'featured') $where_clause .= $where_clause ? " AND featured = 1 " : " featured = 1 ";
	if($term) $where_clause .= $where_clause ? " AND (title LIKE '%{$term}%' OR description LIKE '%{$term}%') " : " (title LIKE '%{$term}%' OR description LIKE '%{$term}%')";
	if($type == 'browse') {
		$privacy_sql = fire_hook('privacy.sql', ' ');
		$where_clause .= ($where_clause) ? " AND (".$privacy_sql.") " : " (".$privacy_sql.") ";
	}
	if($entity_type && $entity_id) {
		$entity_sql = "entity_type = '".$entity_type."' AND entity_id = ".$entity_id;
		$where_clause .= $where_clause ? " AND (".$entity_sql.") " : " (".$entity_sql.") ";
	}
	if($where_clause) $sql .= " WHERE {$where_clause} ";
	if($type != 'mine') $sql .= " AND status = '1'";
	if($with_title) $sql .= " AND title != '' ";
	if($filter and $filter == 'top') {
		$sql .= " ORDER BY play_count desc";
	} else {
		$sql .= " ORDER BY time desc";
	}
	$limit = ($limit) ? $limit : config('music-list-limit', 10);
	return paginate($sql, $limit);
}

function get_playlist_musics($id) {
	$playlist_musics = array();
	$playlist = get_playlist($id);
	if(!$playlist) return false;
	$musics = unserialize($playlist['musics']) ? unserialize($playlist['musics']) : array();
	foreach($musics as $slug) {
		$music = get_music($slug);
		if($music) {
			$music['file_path'] = fire_hook('filter.url', url($music['file_path'], false));
			$music['cover_art'] = fire_hook('filter.url', url($music['cover_art'], false));
			if(music_exists($slug)) {
				$playlist_musics[$music['slug']] = $music;
			}
		}
	}
	return $playlist_musics;
}

function get_playlist($id) {
	$db = db();
	$query = $db->query("SELECT * FROM music_playlists WHERE id = '{$id}' OR slug = '{$id}' LIMIT 1");
	if($query) return $query->fetch_assoc();
	return false;
}

function is_playlist_owner($playlist) {
	if(!is_loggedIn()) return false;
	if($playlist['user_id'] == get_userid()) return true;
	return false;
}

function save_playlist($val, $playlist) {
	$expected = array('title' => '', 'description' => '', 'featured' => $playlist['featured'], 'privacy' => $playlist['privacy']);
	/**
	 * @var $title
	 * @var $description
	 * @var $musics
	 * @var $cover_art
	 * @var $featured
	 * @var $privacy
	 */
	extract(array_merge($expected, $val));
	$musics = serialize($musics);
	$playlistId = $playlist['id'];
	db()->query("UPDATE music_playlists SET title = '{$title}', description = '{$description}',  musics = '{$musics}', featured = '{$featured}', privacy = '{$privacy}' WHERE id = '{$playlistId}'");
	fire_hook('playlist.admin.edited', null, array($playlistId));
	return true;
}

function get_playlist_url($playlist) {
	return url_to_pager('music-playlist-page', array('id' => $playlist['slug']));
}

function delete_playlist($id) {
	db()->query("DELETE FROM music_playlists WHERE id = '{$id}'");
	return true;
}

function add_playlist($val) {
	/**
	 * @var $title
	 * @var $description
	 * @var $musics
	 * @var $entity_id
	 * @var $entity_type
	 * @var $privacy
	 * @var $status
	 */
	$expected = array('title' => '', 'description' => '', 'privacy' => 1, 'status' => 1, 'entity_type' => 'user', 'entity_id' => get_userid());
	extract(array_merge($expected, $val));
	$result = array('result' => true);
	$result = fire_hook('can.post.playlist', $result, array($entity_type, $entity_id));
	if(!$result['result']) return false;
	$time = time();
	$userid = get_userid();
	$slug = toAscii($title);
	if(empty($slug)) $slug = md5(time());
	if(playlist_exists($slug)) {
		$slug = md5($slug.time());
	}
	$musics = serialize($musics);
	$db = db();
	$db->query("INSERT INTO music_playlists (title, slug, description, user_id, entity_type, entity_id, musics, status, privacy, time) VALUES('{$title}', '{$slug}', '{$description}', '{$userid}', '{$entity_type}', '{$entity_id}', '{$musics}', '{$status}', '{$privacy}', '{$time}')");
	$playlistId = db()->insert_id;
	$playlist = get_playlist($playlistId);
	fire_hook('playlist.added', null, array($playlist, $playlistId));
	return $playlist;
}

function playlist_exists($slug) {
	$query = db()->query("SELECT slug FROM music_playlists WHERE slug = '{$slug}' LIMIT 1");
	if($query and $query->num_rows > 0) return true;
	return false;
}


function get_playlist_owner($playlist) {
	$result = array('name' => '', 'image' => '', 'link' => '', 'id' => '');
	$entity = fire_hook('entity.info', $playlist);
	$result['name'] = $entity['name'];
	$result['image'] = $entity['avatar'];
	$result['link'] = url($entity['id']);
	$result['id'] = $entity['id'];
	return fire_hook('get.playlist.owner', $result, array($playlist));
}


function get_related_playlist($playlist, $limit = 6) {
	$title = $playlist['title'];
	$explode = explode(" ", $title);
	$playlistId = $playlist['id'];
	$sql = "SELECT * FROM music_playlists WHERE id != '{$playlistId}'  AND (";
	$where = "";
	foreach($explode as $t) {
		$where .= ($where) ? " OR title LIKE '%{$t}%' " : "title LIKE '%{$t}%'";
	}
	$sql .= $where.') ORDER BY time desc';
	return paginate($sql, $limit);

}


function get_related_playlists($playlist, $limit = 6) {
	$title = $playlist['title'];
	$explode = explode(" ", $title);
	$playlistId = $playlist['id'];
	$sql = "SELECT * FROM music_playlists WHERE id != '{$playlistId}'  AND (";
	$where = "";
	foreach($explode as $t) {
		$where .= ($where) ? " OR title LIKE '%{$t}%' " : "title LIKE '%{$t}%'";
	}
	$sql .= $where.') ORDER BY time desc';
	return paginate($sql, $limit);

}

function count_musics() {
	$db = db();
	$num_musics = $db->query("SELECT COUNT(id) FROM musics");
	if($db->error) {
		return 0;
	} else {
		return $num_musics->fetch_row()[0];
	}
}

function count_playlists() {
	$db = db();
	$num_playlists = $db->query("SELECT COUNT(id) FROM music_playlists");
	if($db->error) {
		return 0;
	} else {
		return $num_playlists->fetch_row()[0];
	}
}

function count_playlist($id) {
	$playlist_musics = get_playlist_musics($id);
	return $playlist_musics ? count(get_playlist_musics($id)) : 0;
}


function get_all_playlists($term, $limit = 10) {
	$sql = "SELECT * FROM music_playlists ";
	$where = "";
	if($term) $where .= ($where) ? " AND (title LIKE '%{$term}%' OR description LIKE '%{$term}%') " : " (title LIKE '%{$term}%' OR description LIKE '%{$term}%')";
	if($where) $sql .= "WHERE {$where} ";
	$sql .= "ORDER BY time desc";
	return paginate($sql, $limit);
}