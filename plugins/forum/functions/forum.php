<?php
function forum_get_categories() {
	$cache_name = 'forum-categories';
	if(cache_exists($cache_name)) {
		$categories = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT * FROM forum_categories ORDER BY id";
		$query = $db->query($sql);
		$categories = fetch_all($query);
		set_cacheForever($cache_name, $categories);
	}
	return $categories;
}

function forum_is_category_exist($category_id) {
	$cache_name = 'forum-category-exists-'.$category_id;
	if(cache_exists($cache_name)) {
		$result = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT id FROM forum_categories WHERE id = ".$category_id;
		$query = $db->query($sql);
		$num_rows = $query->num_rows;
		$result = $num_rows ? true : false;
		set_cacheForever($cache_name, $result);
	}
	return $result;
}

function forum_get_category($category_id) {
	$cache_name = 'forum-category-'.$category_id;
	if(cache_exists($cache_name)) {
		$category = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT id, title FROM forum_categories WHERE id = ".$category_id;
		$query = $db->query($sql);
		$num_rows = $query->num_rows;
		$category = $query->fetch_assoc();
		if(!$num_rows) {
			$category = false;
		}
		set_cacheForever($cache_name, $category);
	}
	return $category;
}

function forum_get_tags() {
	$cache_name = 'forum-tags';
	if(cache_exists($cache_name)) {
		$tags = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT * FROM forum_tags ORDER BY title";
		$query = $db->query($sql);
		$tags = fetch_all($query);
		set_cacheForever($cache_name, $tags);
	}
	return $tags;
}

function forum_get_tag_names() {
	$tags = forum_get_tags();
	$tag_names = array();
	foreach($tags as $tag) {
		$tag_names[] = $tag['title'];
	}
	return $tag_names;
}

function forum_is_tag_exist($tag_id) {
	$cache_name = 'forum-tag-exists-'.$tag_id;
	if(cache_exists($cache_name)) {
		$result = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT id FROM forum_tags WHERE id = ".$tag_id;
		$query = $db->query($sql);
		$num_rows = $query->num_rows;
		$result = $num_rows ? true : false;
		set_cacheForever($cache_name, $result);
	}
	return $result;
}

function forum_get_tag($tag_id) {
	$cache_name = 'forum-tag-'.$tag_id;
	if(cache_exists($cache_name)) {
		$tag = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT id, title, color FROM forum_tags WHERE id = '".$tag_id."'";
		$query = $db->query($sql);
		$tag = $query->fetch_assoc();
		set_cacheForever($cache_name, $tag);
	}
	return $tag;
}

function forum_get_replies($thread_id, $limit) {
	$sql = "SELECT id, post, replier_id, thread_id, date FROM forum_replies WHERE replied_id = 0 AND thread_id = ".$thread_id;
	$replies = paginate($sql, $limit);
	return $replies;
}

function forum_get_sub_replies($thread_id, $replied_id, $limit, $page) {
	$order = config('forum-sub-replies-order', 'DESC');
	$sql = "SELECT id, post, replier_id, thread_id, date FROM forum_replies WHERE replied_id = ".$replied_id."  AND thread_id = ".$thread_id." ORDER BY date ".$order;
	$sub_replies = paginate($sql, $limit, 7, $page);
	return $sub_replies;
}

function forum_get_num_sub_replies($thread_id, $replied_id) {
	$cache_name = 'forum-num-sub-replies-'.$thread_id.'-'.$replied_id;
	if(cache_exists($cache_name)) {
		$count = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT COUNT(id) FROM forum_replies WHERE replied_id = ".$replied_id."  AND thread_id = ".$thread_id;
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$count = $row[0];
		set_cacheForever($cache_name, $count);
	}
	return $count;
}

function forum_is_reply_exist($reply_id) {
	$cache_name = 'forum-reply-exists-'.$reply_id;
	if(cache_exists($cache_name)) {
		$result = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT id FROM forum_replies WHERE id = ".$reply_id;
		$query = $db->query($sql);
		$num_rows = $query->num_rows;
		$result = $num_rows ? true : false;
		set_cacheForever($cache_name, $result);
	}
	return $result;
}

function forum_get_reply($reply_id) {
	$cache_name = 'forum-reply-'.$reply_id;
	if(cache_exists($cache_name)) {
		$reply = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT id, post, replier_id, thread_id, date FROM forum_replies WHERE id = ".$reply_id;
		$query = $db->query($sql);
		$reply = $query->fetch_assoc();
		set_cacheForever($cache_name, $reply);
	}
	return $reply;
}

function forum_get_threads($category_id, $tag, $search, $order, $page, $limit) {
	$category_id_sql = $category_id ? ' AND forum_threads.category_id = '.$category_id : '';
	$tag_sql = $tag ? " AND forum_threads.tags LIKE '% ".$tag." %'" : '';
	$search_sql = $search ? " AND forum_threads.subject LIKE '%".$search."%'" : '';
	$top_sql = $order == 't' ? ' AND UNIX_TIMESTAMP(forum_viewing_threads.last_viewed) > '.(time() - 86400) : '';
	$featured_sql = $order == 'ft' ? ' AND forum_threads.featured = 1' : '';
	$followed_sql = $order == 'f' && is_loggedIn() ? ' AND forum_followed_threads.follower_id = '.get_userid() : '';
	$where_sql = $category_id_sql.$tag_sql.$top_sql.$featured_sql.$followed_sql.$search_sql;
	$where_sql = fire_hook("more.where.to.forum", $where_sql, array());
	$where_sql .= fire_hook('users.category.filter', $where_sql, array($where_sql));
	switch($order) {
		case 'l':
			$order_sql = 'forum_threads.last_replied DESC';
			$from_sql = 'FROM forum_threads';
			$left_join_sql = '';
		break;

		case 't':
			$order_sql = 'forum_threads.last_viewed DESC';
			$from_sql = 'FROM forum_threads';
			$left_join_sql = '';
		break;

		case 'f':
			$order_sql = 'forum_threads.last_replied DESC';
			$from_sql = 'FROM forum_followed_threads';
			$left_join_sql = 'LEFT JOIN forum_threads ON forum_followed_threads.thread_id = forum_threads.id';
		break;

		default:
			$order_sql = 'forum_threads.id DESC';
			$from_sql = 'FROM forum_threads';
			$left_join_sql = '';
		break;
	}
	$query = "SELECT DISTINCT forum_threads.id, forum_threads.subject, forum_threads.date, forum_threads.last_modified, forum_threads.category_id, forum_threads.tags, forum_threads.op_id, forum_threads.nov, forum_threads.nov, forum_threads.last_replied, forum_threads.pinned, forum_threads.nor, forum_threads.hidden, forum_threads.active, forum_threads.closed, forum_categories.title, op.username AS op_username, op.avatar AS op_avatar, rp.username AS rp_username, rp.avatar AS rp_avatar
		{$from_sql}
		{$left_join_sql}
		LEFT JOIN users op
		ON forum_threads.op_id = op.id
		LEFT JOIN users rp
		ON forum_threads.rp_id = rp.id
		LEFT JOIN forum_categories
		ON forum_threads.category_id = forum_categories.id
		LEFT JOIN forum_viewing_threads
		ON forum_viewing_threads.thread_id = forum_threads.id
		LEFT JOIN forum_replies
		ON forum_replies.thread_id = forum_threads.id
		WHERE forum_threads.hidden = 0 AND forum_threads.active = 1 {$where_sql}
		ORDER BY {$order_sql}";
	$threads = paginate($query, $limit);
	$pinned_threads = array();
	for($i = 0; $i < count($threads->results()); $i++) {
		if($threads->results()[$i]['pinned'] == 1 && $category_id && $page <= 1) {
			$pinned_threads[$i] = $threads->results()[$i];
		}
	}
	foreach($pinned_threads as $key => $value) {
		unset($threads->results()[$key]);
		array_unshift($threads->results(), $value);
	}
	return $threads;
}

function forum_is_thread_exist($thread_id) {
	$cache_name = 'forum-thread-exists-'.$thread_id;
	if(cache_exists($cache_name)) {
		$result = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT id FROM forum_threads WHERE id = ".$thread_id;
		$query = $db->query($sql);
		$num_rows = $query->num_rows;
		$result = $num_rows ? true : false;
		if($result) set_cacheForever($cache_name, $result);
	}
	return $result;
}

function forum_get_thread($thread_id) {
	$cache_name = 'forum-thread-'.$thread_id;
	if(cache_exists($cache_name)) {
		$thread = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT * FROM forum_threads WHERE id = ".$thread_id;
		$query = $db->query($sql);
		$thread = $query->fetch_assoc();
		set_cacheForever($cache_name, $thread);
	}
	return $thread;
}

function forum_get_subject($thread_id) {
	$cache_name = 'forum-subject-'.$thread_id;
	if(cache_exists($cache_name)) {
		$subject = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT subject FROM forum_threads WHERE id = ".$thread_id;
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$subject = $row[0];
		set_cacheForever($cache_name, $subject);
	}
	return $subject;
}

function forum_get_op_id($thread_id) {
	$cache_name = 'forum-op-id-'.$thread_id;
	if(cache_exists($cache_name)) {
		$op_id = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT op_id FROM forum_threads WHERE id = ".$thread_id;
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$op_id = $row[0];
		set_cacheForever($cache_name, $op_id);
	}
	return $op_id;
}

function forum_get_post($post_id) {
	if(is_loggedIn()) {
		$user_id = get_userid();
		$cache_name = 'forum-post-'.$post_id.'-'.$user_id;
		if(cache_exists($cache_name)) {
			$post = get_cache($cache_name);
		} else {
			$db = db();
			$sql = "SELECT post FROM forum_replies WHERE id = ".$post_id." AND replier_id = ".$user_id;
			$query = $db->query($sql);
			$row = $query->fetch_row();
			$post = $row[0];
			set_cacheForever($cache_name, $post);
		}
		return $post;
	} else {
		return false;
	}
}

function forum_is_original_post($post_id) {
	$cache_name = 'forum-is-original-post-'.$post_id;
	if(cache_exists($cache_name)) {
		$result = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT thread_id FROM forum_replies WHERE id = ".$post_id;
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$thread_id = $row[0];
		$sql = "SELECT id FROM forum_replies WHERE thread_id = ".$thread_id." LIMIT 0, 1";
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$original_post_id = $row[0];
		$result = $post_id == $original_post_id ? true : false;
		set_cacheForever($cache_name, $result);
	}
	return $result;
}

function forum_get_num_likes($reply_id, $str = false) {
	$cache_name = 'forum-num-likes-'.$reply_id.'-'.((int) $str);
	if(cache_exists($cache_name)) {
		$num_likes = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT COUNT(id) FROM forum_likes WHERE reply_id = ".$reply_id;
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$num_likes = $row[0];
		if($str) {
			$num_likes_str = $num_likes > 1 ? lang('forum::num-likes', array('num' => $num_likes)) : lang('forum::num-like', array('num' => $num_likes));
			$num_likes_str = $num_likes > 0 ? $num_likes_str : '';
			$num_likes = $num_likes_str;
		}
		set_cacheForever($cache_name, $num_likes);
	}
	return $num_likes;
}

function forum_reply_isliked($reply_id) {
	$cache_name = 'forum-reply-isliked-'.$reply_id;
	if(cache_exists($cache_name)) {
		$result = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT id FROM forum_likes WHERE reply_id = ".$reply_id." AND liker_id = ".get_userid();
		$query = $db->query($sql);
		$num_rows = $query->num_rows;
		$result = $num_rows ? true : false;
		set_cacheForever($cache_name, $result);
	}
	return $result;
}

function forum_like($reply_id) {
	if(forum_reply_isliked($reply_id)) {
		return false;
	} else {
		$db = db();
		$sql = "INSERT INTO forum_likes (reply_id, liker_id) VALUES(".$reply_id.", ".get_userid().")";
		$query = $db->query($sql);
		if($query) {
			forget_cache('forum-num-likes-'.$reply_id.'-0');
			forget_cache('forum-num-likes-'.$reply_id.'-1');
			forget_cache('forum-reply-isliked-'.$reply_id);
			fire_hook('forum.like', null, array('forum.like.post', $reply_id, forum_get_post($reply_id)));
			return true;
		} else {
			return false;
		}
	}
}

function forum_unlike($reply_id) {
	if(forum_reply_isliked($reply_id)) {
		$db = db();
		$sql = "DELETE FROM forum_likes WHERE reply_id = ".$reply_id." AND liker_id = ".get_userid();
		$query = $db->query($sql);
		if($query) {
			forget_cache('forum-num-likes-'.$reply_id.'-0');
			forget_cache('forum-num-likes-'.$reply_id.'-1');
			forget_cache('forum-reply-isliked-'.$reply_id);
			fire_hook('forum.unlike', null, array('forum.unlike.post', $reply_id, forum_get_post($reply_id)));
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function forum_get_thread_followers($thread_id) {
	$cache_name = 'forum-thread-followers-'.$thread_id;
	if(cache_exists($cache_name)) {
		$thread_followers = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT follower_id FROM forum_followed_threads WHERE thread_id = ".$thread_id;
		$query = $db->query($sql);
		$thread_followers = fetch_all($query);
		set_cacheForever($cache_name, $thread_followers);
	}
	return $thread_followers;
}

function forum_get_poster_id($reply_id) {
	$cache_name = 'forum-poster-id-'.$reply_id;
	if(cache_exists($cache_name)) {
		$poster_id = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT replier_id FROM forum_replies WHERE id = ".$reply_id;
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$poster_id = $row[0];
		set_cacheForever($cache_name, $poster_id);
	}
	return $poster_id;
}

function forum_get_thread_id($reply_id) {
	$cache_name = 'forum-thread-id-'.$reply_id;
	if(cache_exists($cache_name)) {
		$thread_id = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT thread_id FROM forum_replies WHERE id = ".$reply_id;
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$thread_id = $row[0];
		set_cacheForever($cache_name, $thread_id);
	}
	return $thread_id;
}

function forum_thread_isfollowed($thread_id) {
	$cache_name = 'forum-thread-isfollowed-'.$thread_id;
	if(cache_exists($cache_name)) {
		$result = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT id FROM forum_followed_threads WHERE thread_id = ".$thread_id." AND follower_id = ".get_userid();
		$query = $db->query($sql);
		$num_rows = $query->num_rows;
		$result = $num_rows ? true : false;
	}
	return $result;
}

function forum_thread_follow($thread_id) {
	if(forum_thread_isfollowed($thread_id)) {
		return false;
	} else {
		$db = db();
		$sql = "SELECT nor FROM forum_threads WHERE id = ".$thread_id;
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$nor = $row[0];
		$sql = "INSERT INTO forum_followed_threads (thread_id, follower_id, last_check_nor) VALUES(".$thread_id.", ".get_userid().", ".$nor.")";
		$query = $db->query($sql);
		if($query) {
			forget_cache('forum-thread-isfollowed-'.$thread_id);
			forget_cache('forum-thread-followers-'.$thread_id);
			return true;
		} else {
			return false;
		}
	}
}

function forum_thread_unfollow($thread_id) {
	if(forum_thread_isfollowed($thread_id)) {
		$db = db();
		$sql = "DELETE FROM forum_followed_threads WHERE thread_id = ".$thread_id." AND follower_id = ".get_userid();
		$query = $db->query($sql);
		if($query) {
			forget_cache('forum-thread-isfollowed-'.$thread_id);
			forget_cache('forum-thread-followers-'.$thread_id);
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function forum_followed_count($str = true) {
	$user_id = get_userid();
	$db = db();
	$sql = "SELECT COUNT(forum_followed_threads.id) FROM forum_followed_threads LEFT JOIN forum_threads ON forum_followed_threads.thread_id = forum_threads.id WHERE forum_followed_threads.follower_id = ".$user_id." AND forum_threads.nor > forum_followed_threads.last_check_nor";
	$query = $db->query($sql);
	$row = $query->fetch_row();
	$followed_threads_count = $row[0];
	$num_followed_threads = $str ? ($followed_threads_count ? ' ('.$followed_threads_count.') ' : null) : $followed_threads_count;
	return $num_followed_threads;
}

function forum_thread_followed_count($thread_id) {
	if(is_loggedIn()) {
		$user_id = get_userid();
		$db = db();
		$followed_thread_count = $db->query("SELECT COUNT(forum_followed_threads.id) FROM forum_followed_threads LEFT JOIN forum_threads ON forum_followed_threads.thread_id = forum_threads.id WHERE forum_followed_threads.follower_id = ".$user_id." AND forum_followed_threads.thread_id = ".$thread_id." AND forum_threads.nor > forum_followed_threads.last_check_nor")->fetch_row()[0];
		$num_followed_thread = ($followed_thread_count == 0) ? NULL : ' ('.$followed_thread_count.') ';
		return $num_followed_thread;
	} else {
		return false;
	}
}

function forum_view_thread($thread_id) {
	$db = db();
	$user_id = get_userid();
	$date = date('Y-m-d H:i:s');
	if(is_loggedIn()) {
		$sql = "SELECT last_viewed FROM forum_viewing_threads WHERE viewer_id = ".$user_id." AND thread_id = ".$thread_id;
		$query = $db->query($sql);
		$viewed = $query->num_rows;
		if($viewed) {
			$sql = "UPDATE forum_viewing_threads SET last_viewed = '".$date."' WHERE viewer_id = ".$user_id." AND thread_id = ".$thread_id;
			$query = $db->query($sql);
			if($query) {
				$sql = "UPDATE forum_threads SET last_viewed = '".$date."' WHERE id = ".$thread_id;
				$query = $db->query($sql);
				forget_cache('forum-thread-'.$thread_id);
			}
		} else {
			$sql = "INSERT INTO forum_viewing_threads (viewer_id, ip, thread_id, last_viewed, bot) VALUES (".$user_id.", '".$_SERVER['REMOTE_ADDR']."', ".$thread_id.", '".$date."', 0)";
			$query = $db->query($sql);
			if($query) {
				$sql = "SELECT COUNT(id) FROM forum_viewing_threads WHERE thread_id = ".$thread_id;
				$query = $db->query($sql);

				$row = $query->fetch_row();
				$nov = $row[0];
				if($query) {
					$sql = "UPDATE forum_threads SET last_viewed = '".$date."', nov = ".$nov." WHERE id = ".$thread_id;
					$query = $db->query($sql);
					forget_cache('forum-thread-'.$thread_id);
				}
			}
		}
		$nor = $db->query("SELECT nor FROM forum_threads WHERE id = ".$thread_id);
		$nor = $nor->fetch_row()[0];
		if(forum_thread_isfollowed($thread_id)) {
			$sql = "UPDATE forum_followed_threads SET last_check_nor = ".$nor." WHERE thread_id = ".$thread_id." AND follower_id = ".$user_id;
			$query = $db->query($sql);
			if($query) {
				forget_cache('forum-thread-isfollowed-'.$thread_id);
				forget_cache('forum-thread-followers-'.$thread_id);
			}
		}
	} else {
		$bot = (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/bot|crawl|slurp|spider/i', $_SERVER['HTTP_USER_AGENT'])) ? 1 : 0;
		$last_viewed = $db->query("SELECT last_viewed FROM forum_viewing_threads WHERE ip = '".$_SERVER['REMOTE_ADDR']."' AND thread_id = ".$thread_id);
		if($last_viewed->num_rows > 0) {
			$sql = "UPDATE forum_viewing_threads SET last_viewed = '".$date."' WHERE ip = '".$_SERVER['REMOTE_ADDR']."' AND thread_id = ".$thread_id;
			$query = $db->query($sql);
			if($query) {
				$sql = "UPDATE forum_threads SET last_viewed = '".$date."' WHERE id = ".$thread_id;
				$query = $db->query($sql);
				forget_cache('forum-thread-'.$thread_id);
			}
		} else {
			$sql = "INSERT INTO forum_viewing_threads (viewer_id, ip, thread_id, last_viewed, bot) VALUES (NULL, '".$_SERVER['REMOTE_ADDR']."', ".$thread_id.", '".$date."', ".$bot.")";
			$query = $db->query($sql);
			if($query) {
				$sql = "SELECT COUNT(id) FROM forum_viewing_threads WHERE thread_id = ".$thread_id;
				$query = $db->query($sql);
				$row = $query->fetch_row();
				$nov = $row[0];
				$sql = "UPDATE forum_threads SET last_viewed = '".$date."', nov = ".$nov." WHERE id = ".$thread_id;
				$query = $db->query($sql);
				forget_cache('forum-thread-'.$thread_id);
			}
		}
	}
	return $query ? true : false;
}

function forum_get_post_page_info($post_id) {
	$cache_name = 'forum-post-page-info-'.$post_id;
	if(cache_exists($cache_name)) {
		$post_page_info = get_cache($cache_name);
	} else {
		$db = db();
		$super_length = config('pagination-length-thread', 20);
		$sub_length = config('pagination-length-sub-replies', 4);
		$reply = $db->query("SELECT id, thread_id, replied_id, date FROM forum_replies WHERE id = ".$post_id)->fetch_assoc();
		if(!$reply) {
			return false;
		}
		if($reply['replied_id'] == 0) {
			$super_date = $reply['date'];
			$sub_date = null;
			$sql = "SELECT COUNT(id) FROM forum_replies WHERE thread_id = ".$reply['thread_id']." AND date <= '".$super_date."' AND replied_id = 0";
			$query = $db->query($sql);
			$row = $query->fetch_row();
			$super_position = $row[0];
			$sub_position = null;
			$sql = "SELECT COUNT(id) FROM forum_replies WHERE thread_id = ".$reply['thread_id']." AND replied_id = 0";
			$query = $db->query($sql);
			$row = $query->fetch_row();
			$super_total_records = $row[0];
			$sub_total_records = null;
			$super_total_pages = ceil($super_total_records / $super_length);
			$sub_total_pages = null;
			$super_page = ceil($super_position / $super_length);
			$sub_page = null;
		} else {
			$sql = "SELECT date FROM forum_replies WHERE id = ".$reply['replied_id'];
			$query = $db->query($sql);
			$row = $query->fetch_row();
			$super_date = $row[0];
			$sub_date = $reply['date'];
			$sql = "SELECT COUNT(id) FROM forum_replies WHERE thread_id = ".$reply['thread_id']." AND date <= '".$super_date."' AND replied_id = 0";
			$query = $db->query($sql);
			$row = $query->fetch_row();
			$super_position = $row[0];
			$sql = "SELECT COUNT(id) FROM forum_replies WHERE replied_id = ".$reply['replied_id']." AND date <= '".$sub_date."'";
			$query = $db->query($sql);
			$row = $query->fetch_row();
			$sub_position = $row[0];
			$sql = "SELECT COUNT(id) FROM forum_replies WHERE thread_id = ".$reply['thread_id']." AND replied_id = 0";
			$query = $db->query($sql);
			$row = $query->fetch_row();
			$super_total_records = $row[0];
			$sql = "SELECT COUNT(id) FROM forum_replies WHERE replied_id = ".$reply['replied_id'];
			$query = $db->query($sql);
			$row = $query->fetch_row();
			$sub_total_records = $row[0];
			$super_total_pages = ceil($super_total_records / $super_length);
			$sub_total_pages = ceil($sub_total_records / $sub_length);
			$super_page = ceil($super_position / $super_length);
			$sub_page = ceil($sub_position / $sub_length);
		}
		$post_page_info = array(
			'thread_id' => $reply['thread_id'],
			'replied_id' => $reply['replied_id'],
			'super_page' => $super_page,
			'sub_page' => $sub_page,
			'super_total_pages' => $super_total_pages,
			'sub_total_pages' => $sub_total_pages
		);
		set_cacheForever($cache_name, $post_page_info);
	}
	return $post_page_info;
}

function forum_execute_form($val) {
	$db = db();
	$type = isset($val['type']) ? $val['type'] : null;
	switch($type) {
		case 'add_category':
			/** @var array $title */
			$expected = array('title' => '');
			extract(array_merge($expected, $val));
			$titleSlug = "forum_category_".md5(time().serialize($val)).'_title';
			foreach($title as $langId => $t) {
				add_language_phrase($titleSlug, $t, $langId, 'forum');
			}
			$sql = "INSERT INTO forum_categories(title) VALUES('".$titleSlug."')";
			$query = $db->query($sql);
			if($query) {
				forget_cache('forum-categories');
				foreach($title as $langId => $t) {
					(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId) : add_language_phrase($titleSlug, $t, $langId, 'forum');
				}
			}
			return $query ? true : false;
		break;

		case 'edit_category':
			/** @var array $title */
			$expected = array('title' => '');
			extract(array_merge($expected, $val));
			$category = forum_get_category(sanitizeText($val['category_id']));
			$titleSlug = $category['title'];
			foreach($title as $langId => $t) {
				(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId) : add_language_phrase($titleSlug, $t, $langId, 'forum');
			}
			return true;
		break;

		case 'delete_category':
			$db = db();
			$category = forum_get_category($val['category_id']);
			delete_all_language_phrase($category['title']);
			$new_category_id = $val['new_category_id'] == 'NULL' ? $val['category_id'] : $val['new_category_id'];
			$sql = "DELETE FROM forum_categories WHERE id = ".$val['category_id'];
			$query = $db->query($sql);
			if($query) {
				forget_cache('forum-categories');
				$sql = "UPDATE forum_threads SET category_id = ".$new_category_id." WHERE category_id = ".$val['category_id'];
				$query = $db->query($sql);
				//forget_cache('forum-thread-'.$thread_id);
			}
			return $query ? true : false;
		break;

		case 'add_tag':
			$sql = "INSERT INTO forum_tags(title, color) VALUES('".$val['title']."', '".$val['color']."')";
			$query = $db->query($sql);
			if($query) {
				forget_cache('forum-categories');
				return true;
			} else {
				return false;
			}
		break;

		case 'edit_tag':
			$sql = "UPDATE forum_tags SET title = '".$val['title']."', color = '".$val['color']."' WHERE id = ".$val['tag_id'];
			$query = $db->query($sql);
			if($query) {
				forget_cache('forum-tag-'.$val['tag_id']);
				return true;
			} else {
				return false;
			}
		break;

		case 'delete_tag':
			$sql = "DELETE FROM forum_tags WHERE id = ".$val['tag_id'];
			$query = $db->query($sql);
			if($query) {
				forget_cache('forum-tags');
				return true;
			} else {
				return false;
			}
		break;

		case 'create_thread':
			$tags = explode(', ', $val['tags']);
			$tag_ids = ' ';
			foreach($tags as $tag) {
				$sql = "SELECT id FROM forum_tags WHERE title = '".trim($tag)."'";
				$query = $db->query($sql);
				if($query->num_rows) {
					$row = $query->fetch_row();
					$tag_ids .= $row[0].' ';
				}
			}
			$date = date('Y-m-d H:i:s');
			$user_id = get_userid();
			$create_thread = $db->query("BEGIN WORK;");
			if($create_thread) {
				$sql = "INSERT INTO forum_threads(subject, date, last_modified, category_id, tags, op_id, rp_id, last_replied) VALUES('".$val['subject']."', '".$date."', '".$date."', ".$val['category_id'].", '".$tag_ids."', ".$user_id.", ".$user_id.", '".$date."')";
				$query = $db->query($sql);
				if($query) {
					forget_cache('forum-num-threads');
					$thread_id = $db->insert_id;
					fire_hook('plugins.users.category.updater', null, array('forum_threads', $val, $thread_id));
					$sql = "INSERT INTO forum_replies(post, date, last_modified, thread_id, replied_id, replier_id) VALUES('".lawedContent($val['postbox'])."', '".$date."', '".$date."', ".$thread_id.", 0, ".$user_id.")";
					$query = $db->query($sql);
					if($query) {
						$reply_id = $db->insert_id;
						forget_cache('forum-num-sub-replies-'.$thread_id.'-'.$reply_id);
						forget_cache('forum-reply-exists-'.$reply_id);
						$sql = "INSERT INTO forum_followed_threads(thread_id, follower_id) VALUES(".$thread_id.", ".$user_id.")";
						$query = $db->query($sql);
						if($query) {
							forget_cache('forum-thread-isfollowed-'.$thread_id);
							forget_cache('forum-thread-followers-'.$thread_id);
							$db->query("COMMIT;");
							fire_hook('forum.create', null, array('forum.create', $thread_id, $val['subject']));
							return $thread_id;
						} else {
							$db->query("ROLLBACK;");
							return false;
						}
					} else {
						$db->query("ROLLBACK;");
						return false;
					}
				} else {
					$db->query("ROLLBACK;");
					return false;
				}
			}
			return false;
		break;

		case 'edit_thread':
			$tags = explode(', ', $val['tags']);
			$tag_ids = ' ';
			foreach($tags as $tag) {
				$sql = "SELECT id FROM forum_tags WHERE title = '".trim($tag)."'";
				$query = $db->query($sql);
				if($query->num_rows) {
					$row = $query->fetch_row();
					$tag_ids .= $row[0].' ';
				}
			}
			$sql = "UPDATE forum_threads SET subject = '".$val['subject']."', category_id = ".$val['category_id'].", tags = '".$tag_ids."', pinned = ".$val['pinned'].", hidden = ".$val['hidden'].", active = ".$val['active'].", closed = ".$val['closed'].", featured = ".$val['featured']." WHERE id = ".$val['thread_id'];
			$query = $db->query($sql);
			if($query) {
				forget_cache('forum-thread-'.$val['thread_id']);
				forget_cache('forum-subject-'.$val['thread_id']);
			}
			fire_hook('plugins.users.category.updater', null, array('forum_threads', $val, $val['thread_id']));
			return $query ? true : false;
		break;

		case 'modify_thread':
			$thread = forum_get_thread($val['id']);
			if(get_userid() != $thread['op_id']) {
				return false;
			}
			$tags = explode(', ', $val['tags']);
			$tag_ids = ' ';
			foreach($tags as $tag) {
				$sql = "SELECT id FROM forum_tags WHERE title = '".trim($tag)."'";
				$query = $db->query($sql);
				if($query->num_rows) {
					$row = $query->fetch_row();
					$tag_ids .= $row[0].' ';
				}
			}
			$sql = "UPDATE forum_threads SET subject = '".$val['subject']."', category_id = ".$val['category_id'].", tags = '".$tag_ids."' WHERE id = ".$val['id'];
			$query = $db->query($sql);
			if($query) {
				forget_cache('forum-thread-'.$val['id']);
				forget_cache('forum-subject-'.$val['id']);
				return true;
			} else {
				return false;
			}
		break;

		case 'delete_thread':
			$result = forum_delete_thread($val['thread_id']);
			return $result;
		break;

		case 'reply_thread':
			$sql = "INSERT INTO forum_replies(post, date, last_modified, thread_id, replied_id, replier_id, hidden) VALUES('".lawedContent($val['postbox'])."', '".date('Y-m-d H:i:s')."', '".date('Y-m-d H:i:s')."', ".$val['thread_id'].", ".$val['id'].", ".get_userid().", 0)";
			$query = $db->query($sql);
			if($query) {
				$reply_id = $db->insert_id;
				forget_cache('forum-num-sub-replies-'.$val['thread_id'].'-'.$reply_id);
				forget_cache('forum-reply-exists-'.$reply_id);
				$reply_id = $db->insert_id;
				$sql = "SELECT COUNT(id) FROM forum_replies WHERE thread_id = ".$val['thread_id'];
				$query = $db->query($sql);
				$row = $query->fetch_row();
				$nor = $row[0];
				$sql = "UPDATE forum_threads SET rp_id = ".get_userid().", nor = ".($nor - 1).", last_replied = '".date('Y-m-d H:i:s')."' WHERE id = ".$val['thread_id'];
				$query = $db->query($sql);
				if($query) {
					forget_cache('forum-thread-'.$val['thread_id']);
					fire_hook('forum.reply', null, array('forum.reply.thread', $reply_id, $val['postbox']));
					if($val['id'] > 0) {
						fire_hook('forum.reply', null, array('forum.reply.post', $reply_id, $val['postbox']));
					}
				}
			}
			return $query ? $reply_id : false;
		break;

		case 'edit_post':
			$sql = "UPDATE forum_replies SET post = '".lawedContent($val['postbox'])."' WHERE id = ".$val['id'];
			$query = $db->query($sql);
			//fire_hook('forum.reply', null, array('forum.reply.edit', $val['id'], $val['postbox']));
			return $query ? $val['id'] : false;
		break;

		case 'delete_post':
			forum_delete_reply($val['id']);
			//fire_hook('forum.reply', null, array('forum.reply.delete', $val['id'], $val['postbox']));
			return $val['id'];
		break;

		default:
			return false;
		break;
	}
}

function forum_assign_get_var($url, $var, $val) {
	$url_details = parse_url($url);
	$scheme = isset($url_details['scheme']) ? $url_details['scheme'] : null;
	$host = isset($url_details['host']) ? $url_details['host'] : null;
	$path = isset($url_details['path']) && $url_details['path'] != '/' ? $url_details['path'] : null;
	$query = isset($url_details['query']) ? $url_details['query'] : null;
	$fragment = isset($url_details['fragment']) ? $url_details['fragment'] : null;
	$variables = array();
	if(!is_null($query)) {
		parse_str($query, $variables);
	}
	$variables[$var] = $val;
	$s = empty($scheme) ? '' : '://';
	$q = empty($variables) ? '' : '?';
	$h = empty($fragment) ? '' : '#';
	return $scheme.$s.$host.$path.$q.http_build_query($variables).$h.$fragment;
}

function forum_remove_get_var($url, $var) {
	$url_details = parse_url($url);
	$scheme = isset($url_details['scheme']) ? $url_details['scheme'] : null;
	$host = isset($url_details['host']) ? $url_details['host'] : null;
	$path = isset($url_details['path']) && $url_details['path'] != '/' ? $url_details['path'] : null;
	$query = isset($url_details['query']) ? $url_details['query'] : null;
	$fragment = isset($url_details['fragment']) ? $url_details['fragment'] : null;
	$variables = array();
	if(!is_null($query)) {
		parse_str($query, $variables);
	}
	if(isset($variables[$var])) {
		unset($variables[$var]);
	}
	$s = empty($scheme) ? '' : '://';
	$q = empty($variables) ? '' : '?';
	$h = empty($fragment) ? '' : '#';
	return $scheme.$s.$host.$path.$q.http_build_query($variables).$h.$fragment;
}

function forum_timelength($s, $t = false) {
	$s = abs($s);
	if($s < 60) {
		$st = $s < 2 ? lang('a-second') : lang('d-seconds', array('d' => $s));
		return $st;
	} elseif($s < 3600) {
		$m = floor($s / 60);
		$s = $s % 60;
		$mt = $m < 2 ? lang('a-minute') : lang('d-minutes', array('d' => $m));
		$st = $s < 2 ? lang('a-second') : lang('d-seconds', array('d' => $s));
		return $t ? $mt : $mt.', '.$st;
	} elseif($s < 86400) {
		$h = floor($s / 60 / 60);
		$m = $s / 60 % 60;
		$s = $s % 60;
		$ht = $h < 2 ? lang('an-hour') : lang('d-hours', array('d' => $h));
		$mt = $m < 2 ? lang('a-minute') : lang('d-minutes', array('d' => $m));
		$st = $s < 2 ? lang('a-second') : lang('d-seconds', array('d' => $s));
		return $t ? $ht : $ht.', '.$mt.', '.$st;
	} elseif($s < 604800) {
		$d = floor($s / 60 / 60 / 24);
		$h = $s / 60 / 60 % 24;
		$m = $s / 60 % 60;
		$s = $s % 60;
		$dt = $d < 2 ? lang('a-day') : lang('d-days', array('d' => $d));
		$ht = $h < 2 ? lang('an-hour') : lang('d-hours', array('d' => $h));
		$mt = $m < 2 ? lang('a-minute') : lang('d-minutes', array('d' => $m));
		$st = $s < 2 ? lang('a-second') : lang('d-seconds', array('d' => $s));
		return $t ? $dt : $dt.', '.$ht.', '.$mt.', '.$st;
	} elseif($s < 31449600) {
		$w = floor($s / 60 / 60 / 24 / 7);
		$d = $s / 60 / 60 / 24 % 7;
		$h = $s / 60 / 60 % 24;
		$m = $s / 60 % 60;
		$s = $s % 60;
		$wt = $w < 2 ? lang('a-week') : lang('d-weeks', array('d' => $w));
		$dt = $d < 2 ? lang('a-day') : lang('d-days', array('d' => $d));
		$ht = $h < 2 ? lang('an-hour') : lang('d-hours', array('d' => $h));
		$mt = $m < 2 ? lang('a-minute') : lang('d-minutes', array('d' => $m));
		$st = $s < 2 ? lang('a-second') : lang('d-seconds', array('d' => $s));
		return $t ? $wt : $wt.', '.$dt.', '.$ht.', '.$mt.', '.$st;
	} else {
		$y = floor($s / 60 / 60 / 24 / 7 / 52);
		$w = $s / 60 / 60 / 24 / 7 % 52;
		$d = $s / 60 / 60 / 24 % 7;
		$h = $s / 60 / 60 % 24;
		$m = $s / 60 % 60;
		$s = $s % 60;
		$yt = $y < 2 ? lang('a-year') : lang('d-years', array('d' => $y));
		$wt = $w < 2 ? lang('a-week') : lang('d-weeks', array('d' => $w));
		$dt = $d < 2 ? lang('a-day') : lang('d-days', array('d' => $d));
		$ht = $h < 2 ? lang('an-hour') : lang('d-hours', array('d' => $h));
		$mt = $m < 2 ? lang('a-minute') : lang('d-minutes', array('d' => $m));
		$st = $s < 2 ? lang('a-second') : lang('d-seconds', array('d' => $s));
		return $t ? $yt : $yt.', '.$wt.', '.$dt.', '.$ht.', '.$mt.', '.$st;
	}
}

function forum_get_user_column($user_id, $column) {
	$db = db();
	$sql = "SELECT ".$column." FROM users WHERE id = ".$user_id;
	$query = $db->query($sql);
	$row = $query->fetch_row();
	$column = $row[0];
	return $column;
}

function forum_get_last_replier($thread_id) {
	$db = db();
	$sql = "SELECT replier_id FROM forum_replies WHERE thread_id = ".$thread_id." ORDER BY date DESC LIMIT 1";
	$query = $db->query($sql);
	$repliers = fetch_all($query);
	return $repliers;
}

function forum_delete_thread($thread_id) {
	$db = db();
	$sql = "SELECT op_id FROM forum_threads WHERE id = ".$thread_id;
	$query = $db->query($sql);
	$thread = $query->fetch_assoc();
	$op_id = $thread['op_id'];
	$user_id = get_userid();
	$query = null;
	if($user_id == $op_id || is_admin()) {
		$sql = "DELETE FROM forum_threads WHERE id = ".$thread_id;
		$query = $db->query($sql);
		if($query) {
			forget_cache('forum-num-threads');
			$sql = "SELECT id FROM forum_replies WHERE thread_id = ".$thread_id;
			$query = $db->query($sql);
			$replies = fetch_all($query);
			$sql = "DELETE FROM forum_replies WHERE thread_id = ".$thread_id;
			$query = $db->query($sql);
			if($query) {
				foreach($replies as $reply) {
					$reply_id = $reply['id'];
					forget_cache('forum-num-sub-replies-'.$thread_id.'-'.$reply_id);
					forget_cache('forum-reply-exists-'.$reply_id);
				}
				$sql = "DELETE FROM forum_followed_threads WHERE thread_id = ".$thread_id;
				$query = $db->query($sql);
				if($query) {
					forget_cache('forum-thread-isfollowed-'.$thread_id);
					forget_cache('forum-thread-followers-'.$thread_id);
					$sql = "DELETE FROM forum_viewing_threads WHERE thread_id = ".$thread_id;
					$query = $db->query($sql);
					if($query) {
						$sql = "SELECT id FROM forum_replies WHERE thread_id = ".$thread_id;
						$query = $db->query($sql);
						while($row = $query->fetch_assoc($sql)) {
							forum_delete_reply($row['id']);
						}
					}
				}
			}
		}
	}
	return $query ? true : false;
}

function forum_delete_reply($reply_id) {
	$db = db();
	$sql = "SELECT thread_id, replier_id FROM forum_replies WHERE id = ".$reply_id;
	$query = $db->query($sql);
	$reply = $query->fetch_assoc();
	$thread_id = $reply['thread_id'];
	$replier_id = $reply['replier_id'];
	$user_id = get_userid();
	$query = null;
	if($user_id == $replier_id || is_admin()) {
		$sql = "DELETE FROM forum_replies WHERE id = ".$reply_id;
		$query = $db->query($sql);
		if($query) {
			$sql = "DELETE FROM forum_replies WHERE replied_id = ".$reply_id." AND thread_id = ".$thread_id;
			$query = $db->query($sql);
			if($query) {
				forget_cache('forum-num-sub-replies-'.$thread_id.'-'.$reply_id);
				forget_cache('forum-reply-exists-'.$reply_id);
				$sql = "DELETE FROM forum_likes WHERE reply_id = ".$reply_id;
				$query = $db->query($sql);
			}
		}
	}
	return $query ? true : false;
}

function forum_num_threads() {
	$cache_name = 'forum-num-threads';
	if(cache_exists($cache_name)) {
		$count = get_cache($cache_name);
	} else {
		$db = db();
		$sql = "SELECT COUNT(id) FROM forum_threads";
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$count = $row[0];
		set_cacheForever($cache_name, $count);
	}
	return $count;
}

function forum_get_avatar($user_id, $size, $username = null) {
	$db = db();
	if($username) {
		$sql = "SELECT id FROM users WHERE username = '".$username."'";
		$query = $db->query($sql);
		$row = $query->fetch_row();
		$user_id = $row[0];
	}
	$sql = "SELECT gender, avatar FROM users WHERE id = ".$user_id;
	$query = $db->query($sql);
	$user = $query->fetch_assoc();
	if(!$user) {
		return false;
	}
	$avatar = $user['avatar'];
	if($avatar) {
		return url(str_replace('%w', $size, $avatar));
	} else {
		$gender = isset($user['gender']) && $user['gender'] ? $user['gender'] : null;
		return $gender ? img('images/avatar/'.$gender.'.png') : img('images/avatar.png');
	}
}

function forum_output_text($content) {
	if(is_rtl($content)) {
		$content = '<span style="direction: rtl;text-align: right;display: block">'.$content.'</span>';
	}
	return nl2br($content);
}

function forum_slugger($str) {
	$slug = preg_replace('/[^A-Za-z0-9]+/', '-', $str);
	$slug = trim(strtolower($slug), '-');
	return $slug;
}

function forum_get_thread_slug_link($thread_id, $page = null) {
	$link = url('forum/thread/'.$thread_id.'/'.forum_slugger(forum_get_subject($thread_id)).'/'.$page);
	return $link;
}

function forum_get_forum_slug_link($url) {
	$url_details = parse_url($url);
	$scheme = isset($url_details['scheme']) ? $url_details['scheme'] : null;
	$host = isset($url_details['host']) ? $url_details['host'] : null;
	$path = isset($url_details['path']) && $url_details['path'] != '/' ? $url_details['path'] : null;
	$query = isset($url_details['query']) ? $url_details['query'] : null;
	$fragment = isset($url_details['fragment']) ? $url_details['fragment'] : null;
	$variables = array();
	if(!is_null($query)) {
		parse_str($query, $variables);
	}
	$category = null;
	$tag = null;
	$order = null;
	$search = null;
	$page = null;
	if(isset($variables['c'])) {
		$category = '/category/'.$variables['c'].'/'.forum_slugger(lang(forum_get_category($variables['c'])['title']));
		unset($variables['c']);
	}
	if(isset($variables['t'])) {
		$tag = '/tag/'.$variables['t'].'/'.forum_slugger(forum_get_tag($variables['t'])['title']);
		unset($variables['t']);
	}
	if(isset($variables['o'])) {
		switch($variables['o']) {
			case 'l':
				$order = '/latest';
			break;
			case 't':
				$order = '/top';
			break;
			case 'ft':
				$order = '/featured';
			break;
			case 'f':
				$order = '/followed';
			break;
			default:
				$order = '/new';
			break;
		}
		unset($variables['o']);
	}
	if(isset($variables['s'])) {
		$search = '/search/'.$variables['s'];
		unset($variables['s']);
	}

	$s = empty($scheme) ? '' : '://';
	$q = empty($variables) ? '' : '?';
	$h = empty($fragment) ? '' : '#';
	return $scheme.$s.$host.rtrim($path, '/').$category.$tag.$order.$search.$q.http_build_query($variables).$h.$fragment;
}