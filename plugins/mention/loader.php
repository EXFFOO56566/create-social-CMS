<?php
load_functions("like::like");

register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
		//register assets
		register_asset("mention::css/mention.css");
		register_asset("mention::js/mention.js");
	}
});

register_hook('feed.added', function($id, $val) {
	load_functions("mention::mention");
	if(isset($val['content'])) {
		$content = $val['content'];
		$mentions = mention_parse($content);
		if($mentions) {
			foreach($mentions as $mention) {
				$username = str_replace('@', '', $mention);
				$user = find_user($username, false);
				if($user and $user['id'] != get_userid()) {
					send_notification_privacy('notify-site-mention-you', $user['id'], 'feed.mention', $id);
				}
			}
		}
	}
});

register_hook('comment.add', function($type, $type_id, $text) {
	load_functions("mention::mention");
	if($type == 'feed') {
		$mentions = mention_parse($text);
		if($mentions) {
			foreach($mentions as $mention) {
				$username = str_replace('@', '', $mention);
				$user = find_user($username, false);
				if($user and $user['id'] != get_userid()) {
					send_notification_privacy('notify-site-mention-you', $user['id'], 'feed.mention', $type_id);
				}
			}
		}
	} elseif($type == 'comment') {
		$comment = find_comment($type_id);
		if($comment and $comment['type'] == 'feed') {
			$mentions = mention_parse($text);
			if($mentions) {
				foreach($mentions as $mention) {
					$username = str_replace('@', '', $mention);
					$user = find_user($username, false);
					if($user and $user['id'] != get_userid()) {
						send_notification_privacy('notify-site-mention-you', $user['id'], 'feed.mention', $comment['type_id']);
					}
				}
			}
		}
	}
});

register_hook('filter.content', function($content) {
	load_functions("mention::mention");
	$mentions = mention_parse($content);
	if($mentions) {
		$done = array();
		foreach($mentions as $mention) {
			$username = str_replace('@', '', $mention);
			if(!in_array($username, $done)) {
				$done[] = $username;
				$field = "id,first_name,last_name,username,avatar";
				$field = fire_hook('more.users.fields', $field, array($field));
				$sql = "SELECT {$field} FROM users WHERE";
				$where_clause = " `username`='{$username}'";
				$where_clause = fire_hook('users.category.filter', $where_clause, array($where_clause, false, false));
				$sql .= $where_clause;
				$query = db()->query($sql);
				$user = $query->fetch_assoc();
				if($query and $user) {
					$color = config('mention-color', '#3498db');
					$title = (config('mention-title', 2) == '1') ? $user['username'] : get_user_name($user);
					$link = " <a ajax='true' style='color:{$color} !important' href='".profile_url(null, $user)."'>".$title."</a> ";
					$content = str_replace($mention, $link, $content);
				}
			}
		}
	}

	return $content;
});

register_hook("display.notification", function($notification) {
	if($notification['type'] == 'feed.mention') {
		return view("mention::notifications/feed", array('notification' => $notification));
	}
});


register_get_pager("mention/find", array('use' => 'mention::mention@find_user_pager'));