<?php
load_functions("feed::feed");
load_functions("ads::ads");
register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
		//register assets
		register_asset("feed::css/feed.css");
		register_asset("feed::js/feed.js");
		register_asset("feed::js/isInViewport.js");

	}
});

register_hook("before-render-js", function($html) {
	$enable = config('feed-realtime-update', 1);
	$interval = config('feed-realtime-update-interval', 20000);
	$max_photos_upload = config('max-photos-upload', 10);
	$enable_editor_enter_submit = config('enable-editor-enter-submit', 0);
	$html .= <<<EOT

		<script>
			var feedUpdate = $enable;
			var feedUpdateInterval = $interval;
			var enableEditorEnterSubmit = $enable_editor_enter_submit;
			var maxPhotosUpload = $max_photos_upload;
		</script>
EOT;
	return $html;
});

/*register_hook('before-render-css', function($html) {
    $html .= <<<EOT
    <style>
		input, select, textarea {
			font-size: 16px !important;
		}
    </style>
EOT;
    return $html;
});*/

register_hook("admin-started", function() {
	//get_menu("admin-menu", "settings")->addMenu(lang("feed::feed"), url("admin/plugin/settings/feed"));
});

register_get_pager('feed/{id}', array('use' => 'feed::feed@feed_page_pager', 'as' => 'view-post'))->where(array('id' => '[0-9]+'));

register_block_page('feed-page', lang('feed::feed-page'));

register_pager("feed", array("use" => "feed::feed@feed_pager", "as" => "feed", "filter" => "auth", "block" => lang("feed")));
register_pager("feed/search/media", array("use" => "feed::feed@search_media_pager", "filter" => "auth"));
register_pager("feed/submit/poll", array("use" => "feed::feed@submit_poll_pager", "filter" => "auth"));
register_pager('feed/load/voters', array('use' => 'feed::feed@poll_voters_pager', 'as' => 'poll-voters'));
    register_post_pager("feed/add", array("use" => "feed::feed@add_feed_pager", "filter" => "auth"));
register_pager("feed/pin/{id}", array("use" => "feed::feed@pin_feed_pager", "filter" => "auth"))->where(array('id' => '[0-9]+'));
register_post_pager("feed/save", array("use" => "feed::feed@save_feed_pager", "filter" => "auth"));
register_get_pager('feed/delete', array('use' => 'feed::feed@remove_feed_pager', 'filter' => 'auth'));
register_get_pager('feed/editor/privacy', array('use' => 'feed::feed@update_editor_privacy_pager', 'filter' => 'auth'));
register_get_pager('feed/more', array('use' => 'feed::feed@feed_more_pager'));
register_get_pager('feed/download', array('use' => 'feed::feed@feed_download_pager', 'as' => 'feed-download'));
register_get_pager('feed/share', array('use' => 'feed::feed@share_feed_pager', 'filter' => 'auth'));
register_get_pager('feed/hide', array('use' => 'feed::feed@hide_feed_pager', 'filter' => 'auth'));
register_get_pager('feed/unhide', array('use' => 'feed::feed@unhide_feed_pager', 'filter' => 'auth'));
register_pager('feed/link/get', array('use' => 'feed::feed@get_link_pager', 'filter' => 'auth'));
register_pager('feed/update/privacy', array('use' => 'feed::feed@update_privacy_pager', 'filter' => 'auth'));
register_get_pager('feed/notification', array('use' => 'feed::feed@feed_notification_pager', 'filter' => 'auth'));

register_pager('check/new/feeds', array('use' => 'feed::feed@check_new_pager', 'filter' => 'auth'));

//add menu for feed editor menu
add_menu("feed-editor-menu", array('id' => 'image', 'title' => lang('feed::image'), 'icon' => 'mdi-image-photo-camera', 'link' => 'javascript:void(0)'));
add_menu("feed-editor-menu", array('id' => 'video', 'title' => lang('feed::video'), 'icon' => 'mdi-av-videocam', 'link' => 'javascript:void(0)'));

register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => 'Feed Permissions',
		'description' => '',
		'roles' => array(
			'can-tag-users-feed' => array('title' => lang('feed::can-tag-users'), 'value' => 1),
			'can-share-file-feed' => array('title' => lang('feed::can-upload-file'), 'value' => 1),
			'can-upload-photo-feed' => array('title' => lang('feed::can-upload-photos'), 'value' => 1),
			'can-upload-video-feed' => array('title' => lang('feed::can-upload-video'), 'value' => 1),
			'can-create-poll' => array('title' => lang('feed::can-create-poll'), 'value' => 1),
			'can-use-feeling' => array('title' => lang('feed::can-use-feeling'), 'value' => 1),
			'can-post-feed' => array('title' => lang('feed::can-post-feed'), 'value' => 1),
		)
	);
	return $roles;
});

register_hook("like.item", function($type, $typeId, $userid) {
	if($type == 'feed') {
		$feed = find_feed($typeId, false);
		if($feed['user_id'] and $feed['user_id'] != get_userid()) {
			send_notification_privacy('notify-site-like', $feed['user_id'], 'feed.like', $typeId);
		}
	} elseif($type == 'comment') {
		$comment = find_comment($typeId, false);
		if($comment and $comment['user_id'] != get_userid()) {
			if($comment['type'] == 'feed') {
				send_notification_privacy('notify-site-like', $comment['user_id'], 'feed.like.comment', $comment['type_id']);
			}
		}
	}
});

register_hook("like.react", function($type, $typeId, $code, $userid) {
	if($type == 'feed') {
		$feed = find_feed($typeId, false);
		if($feed['user_id'] and $feed['user_id'] != get_userid()) {
			send_notification_privacy('notify-site-like', $feed['user_id'], 'feed.like.react', $typeId, array(), $code);
		}
	}
});

register_hook("comment.add", function($type, $typeId, $text) {
	if($type == 'feed') {
		$subscribers = get_subscribers($type, $typeId);
		foreach($subscribers as $userid) {
			if($userid != get_userid()) {
				send_notification_privacy('notify-site-comment', $userid, 'feed.comment', $typeId, array(), null, $text);
			}
		}

		$feed = find_feed($typeId, false);

		//let send mail if notification is enabled and the user can receive notification
		if($feed and $feed['user_id'] != get_userid()) {
			$userid = $feed['user_id'];
			$privacy = get_privacy('email-notification', 1, $userid);
			if(config('enable-email-notification', true) and $privacy) {
				$mailer = mailer();
				$user = find_user($userid);
				if(!user_is_online($user)) {
					$mailer->setAddress($user['email_address'], get_user_name($user))->template("comment-post", array(
						'link' => url('feed/'.$typeId),
						'fullname' => get_user_name(),
					));
					$mailer->send();
				}
			}
		}

	}
});

register_hook("reply.add", function($commentId, $type, $typeId, $text) {
	if($type == 'feed') {
		$subscribers = get_subscribers('comment', $commentId);
		foreach($subscribers as $userid) {
			if($userid != get_userid()) {
				send_notification_privacy('notify-site-reply-comment', $userid, 'feed.comment.reply', $typeId, array(), null, $text);
			}
		}
	}
});

if(config('dislike-notification', false)) {
	register_hook("dislike.item", function($type, $typeId, $userid) {
		if($type == 'feed') {
			$feed = find_feed($typeId, false);
			if($feed['user_id'] and $feed['user_id'] != get_userid()) {
				send_notification_privacy('notify-site-dislike-item', $feed['user_id'], 'feed.dislike', $typeId);
			}
		}
	});
}

register_hook("share.feed", function($feed) {
	if($feed['user_id'] and $feed['user_id'] != get_userid()) {
		send_notification_privacy('notify-site-share-item', $feed['user_id'], 'feed.shared', $feed['feed_id']);
	}
});

register_hook("feed.added", function($id, $val) {
	/**
	 * @var $tags
	 */
	extract(array_merge(array('tags' => array()), $val));
	if($tags) {
		foreach($tags as $tag) {
			send_notification_privacy('notify-site-tag-you', $tag, 'feed.tag', $id);
		}
	}
});

register_hook("user.avatar", function($userid, $avatar, $id) {
	if(!$id) return false;
	$images = array($id => $avatar);
	add_feed(array(
		'entity_id' => $userid,
		'type' => 'feed',
		'type_id' => 'change-avatar',
		'auto_post' => true,
		'privacy' => 1,
		'images' => perfectSerialize($images),
	));
});

register_hook("user.cover", function($cover, $id) {
	if(!$id) return false;
	$images = array($id => $cover);
	add_feed(array(
		'entity_id' => get_userid(),
		'type' => 'feed',
		'type_id' => 'change-cover',
		'privacy' => 1,
		'auto_post' => true,
		'images' => perfectSerialize($images),
	));
});


register_hook("display.notification", function($notification) {
	if($notification['type'] == 'feed.like') {
		return view("feed::notifications/like", array('notification' => $notification, 'type' => 'like'));
	} elseif($notification['type'] == 'feed.like.react') {
		return view("feed::notifications/react", array('notification' => $notification));
	} elseif($notification['type'] == 'feed.dislike') {
		return view("feed::notifications/like", array('notification' => $notification, 'type' => 'dislike'));
	} elseif($notification['type'] == 'feed.like.comment') {
		return view("feed::notifications/like-comment", array('notification' => $notification, 'type' => 'like'));
	} elseif($notification['type'] == 'feed.dislike.comment') {
		return view("feed::notifications/like-comment", array('notification' => $notification, 'type' => 'dislike'));
	} elseif($notification['type'] == 'feed.comment') {
		$feed = get_feed_publisher($notification['type_id']);
		if($feed) {

			return view("feed::notifications/comment", array('notification' => $notification, 'feed' => $feed));
		} else {
			delete_notification($notification['notification_id']);
		}

	} elseif($notification['type'] == 'feed.comment.reply') {
		return view("feed::notifications/reply", array('notification' => $notification));
	} elseif($notification['type'] == 'feed.shared') {
		return view("feed::notifications/shared", array('notification' => $notification));
	} elseif($notification['type'] == 'feed.tag') {
		return view("feed::notifications/tag", array('notification' => $notification));
	} elseif($notification['type'] == 'post-on-timeline') {
		return view("feed::notifications/timeline", array('notification' => $notification));
	}
});

register_hook("feed-title", function($feed) {
	if($feed['type'] == 'feed' and $feed['type_id'] == 'change-avatar') {
		echo lang('changed-profile-picture');
	} elseif($feed['type'] == 'feed' and $feed['type_id'] == 'change-cover') {
		echo lang('changed-profile-cover');
	}
});


register_hook('admin.statistics', function($stats) {
	$stats['feeds'] = array(
		'count' => count_total_feeds(),
		'title' => lang('feed::posts'),
		'icon' => 'ion-chatboxes',
		'link' => '#',
	);
	return $stats;
});
register_hook('admin.charts', function($result, $months, $year) {
	$c = array(
		'name' => lang('feed::posts'),
		'points' => array()
	);


	foreach($months as $name => $n) {
		$c['points'][$name] = count_posts_in_month($n, $year);

	}

	$result['charts']['members'][] = $c;


	return $result;
});

register_hook('user.delete', function($userid) {

	$d = db()->query("SELECT * FROM feeds WHERE user_id='{$userid}'");

	while($feed = $d->fetch_assoc()) {
		remove_feed($feed['feed_id'], $feed);
	}
});

register_hook('feed.edit.privacy.check', function($result, $feed) {
	if($feed['type'] == 'feed') {
		if(!is_loggedIn() || (is_loggedIn() && $feed['user_id'] != get_userid())) {
			$result['edit'] = false;
		}
	}
	return $result;
});

register_hook('app.view.result', function($result, $view, $param) {
	$entity_type = isset($param['entity_type']) ? $param['entity_type'] : 'user';
	$entity_id = isset($param['entity_id']) ? $param['entity_id'] : get_userid();
	$to_user_id = isset($param['to_user_id']) ? $param['to_user_id'] : null;
	if($view == 'feed::editor' && !can_post_to_feed($entity_type, $entity_id, $to_user_id)) {
		$result = '';
	}
	return $result;
});

register_hook('can.post.feed', function($result, $type, $typeId, $to_user_id) {
	if(!(is_loggedIn() && user_has_permission('can-post-feed'))) {
		$result['result'] = false;
	}
	if($type == 'user') {
		if(!is_loggedIn() || (is_loggedIn() && $typeId != get_userid())) {
			$result['result'] = false;
		}
		if(isset($to_user_id) && !empty($to_user_id) && !can_post_on_timeline($to_user_id)) {
			$result['result'] = false;
		}
	}
	return $result;
});

if(isRTL()) {
	register_hook("after-render-css", function($html) {
		$html .= "
		<style>
			.feed-wrapper .feed-header > .left {
				float: right !important;
				margin-left: 12px;
			}
		</style>\n";
		return $html;
	});
}

register_hook('before.feed.content', function() {
	if(config('enable-feed-sort', true)) {
		echo view('feed::sort');
	}
});

register_hook('feeds.query.fields', function($fields) {
	$fields .= ", ((SELECT COUNT(like_id) FROM likes WHERE type = 'feed' AND type_id = feeds.feed_id) * (1 / (UNIX_TIMESTAMP() - feeds.time))) as popularity";
	return $fields;
});

register_hook('feed.get.sql.where.extend', function($sql) {
	$filter = input('sortby');
	if($filter == 'photo') {
		$sql .= " AND photos != '' ";
	} elseif($filter == 'videos') {
		$sql .= " AND video != '' OR feed_content LIKE '%https://www.youtube.com/watch%' OR feed_content LIKE '%vimeo.com/%' OR feed_content LIKE '%dailymotion.com/%' ";
	} elseif($filter == 'musics') {
		$sql .= " AND music != '' OR feed_content LIKE '%https://soundcloud.com%' ";
	} elseif($filter == 'files') {
		$sql .= " AND files !='' ";
	} elseif($filter == 'text') {
		$sql .= " AND feed_content != '' AND feed_content NOT LIKE '%https://www.youtube.com/watch%' AND feed_content NOT LIKE '%vimeo.com/%' AND feed_content NOT LIKE '%dailymotion.com/%' AND photos = '' AND video = '' AND music IS NULL AND (is_poll = 0 OR type_id  != 'poll')";
	} elseif($filter == 'polls') {
		$sql .= " AND (is_poll = 1 OR type_id  = 'poll') ";
	}
	return $sql;
});

register_hook('feed.get.sql.order', function($sort_sql) {
	$sort = input('orderby');
	if($sort == 'top') {
		$sort_sql = " ORDER BY popularity DESC";
	}
	return $sort_sql;
});

register_hook('comment.can-edit', function($result, $comment) {
	if($comment['type'] === 'feed') {
		$feed = find_feed($comment['type_id']);
		if($feed['user_id'] == get_userid()) {
			$result['status'] = true;
		}
	}

	return $result;
});

register_hook('before.feed.content', function (){
    if (!isset($_COOKIE['day_greetings'])){
        echo view("feed::greetings");
    }
});