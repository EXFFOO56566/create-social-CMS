<?php

load_functions('livestreaming::livestream');

register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
		//register_asset("livestreaming::js/livestream.js");
	}
});

register_hook('admin-started', function() {
	delete_empty_livestreams();
	get_menu("admin-menu", "plugins")->addMenu(lang("livestreaming::livestream-manager"), '#', "livestream-manager");
	get_menu("admin-menu", "plugins")->findMenu("livestream-manager")->addMenu(lang("livestreaming::manage"), url('admincp/livestream'), "manage");
	get_menu("admin-menu", "plugins")->findMenu("livestream-manager")->addMenu(lang("livestreaming::servers"), url('admincp/livestream/servers'), "servers");
	get_menu("admin-menu", "plugins")->findMenu("livestream-manager")->addMenu(lang("livestreaming::categories"), url('admincp/livestream/categories'), "categories");
});

register_hook('footer', function() {
	$html = '';
	if(is_loggedIn()) {
		$html = view('livestreaming::iframe');
	}
	echo $html;
});

register_hook('feed.editor.actions', function() {
	$html = '<li><a id="feed-editor-livestream-start" onclick="return loadLivestreamFrame(0)" href="" data-toggle="tooltip" title="'.lang('livestream::live-video').'" class=""><i class="ion-radio-waves"></i></a></li>';
	echo $html;
});

register_hook('feed.editor.modal.actions', function($column) {
	if($column == 1) {
		$html = '<li><a onclick=" return loadLivestreamFrame(0)" id="feed-editor-image-selector" href=""><i class="ion-radio-waves pink"></i><label>'.lang('livestreaming::live-video').'</label></a></li>';
		echo $html;
	}
});

register_filter('livestream-auth', function() {
	$livestreamId = segment(1);
	$livestream = get_livestream($livestreamId);
	app()->livestream = $livestream;
	app()->setTitle($livestream['title']);
	$id = $livestream['id'];
	db()->query("UPDATE livestreams SET view_count = 1 + view_count WHERE id='{$id}'");
	return true;
});


register_filter('livestream-watch-auth', function() {
	if(isset(input('val')['action']) && input('val')['action'] != 'join') return true;
	$livestreamId = input('val')['livestreamId'];
	$livestream = get_livestream($livestreamId);
	app()->livestream = $livestream;
	app()->setTitle($livestream['title']);
	$id = $livestream['id'];
	db()->query("UPDATE livestreams SET view_count = 1 + view_count WHERE id='{$id}'");
	return true;
});

register_pager("admincp/livestream/servers", array(
		'use' => 'livestreaming::admincp@servers_pager',
		'as' => 'admincp-livestream-servers-pager',
		'filter' => 'admin-auth')
);

register_pager("admincp/livestream", array(
		'use' => 'livestreaming::admincp@livestream_pager',
		'as' => 'admincp-livestream-pager',
		'filter' => 'admin-auth')
);

register_pager("admincp/livestream/manage", array(
		'use' => 'livestreaming::admincp@livestreams_manage_pager',
		'as' => 'admincp-livestream-manage-pager',
		'filter' => 'admin-auth')
);

register_pager("admincp/livestream/manage/servers", array(
		'use' => 'livestreaming::admincp@manage_servers_pager',
		'as' => 'admin-livestream-manage-server',
		'filter' => 'admin-auth')
);

register_pager("admincp/livestream/server/add", array(
		'use' => 'livestreaming::admincp@add_server_pager',
		'as' => 'admincp-livestream-add-server-pager',
		'filter' => 'admin-auth')
);

register_pager("admincp/livestream/categories", array(
		'use' => 'livestreaming::admincp@categories_pager',
		'as' => 'admincp-livestream-categories-pager',
		'filter' => 'admin-auth')
);

register_pager("admincp/livestream/manage/categories", array(
		'use' => 'livestreaming::admincp@manage_categories_pager',
		'as' => 'admin-livestream-manage-category',
		'filter' => 'admin-auth')
);

register_pager("admincp/livestream/categories/add", array(
		'use' => 'livestreaming::admincp@add_categories_pager',
		'as' => 'admincp-livestream-add-category-pager',
		'filter' => 'admin-auth')
);

register_pager("admincp/livestream/action/batch", array('use' => "livestreaming::admincp@livestream_action_batch_pager", 'filter' => 'admin-auth', 'as' => 'admin-livestream-batch-action'));


register_pager("livestreams", array(
		'use' => 'livestreaming::livestream@livestreams_pager',
		'as' => 'livestreams',
		'filter' => 'auth')
);

register_pager("livestreams/ongoing", array(
		'use' => 'livestreaming::livestream@render_ongoing_pager',
		'as' => 'ongoing-livestreams',)
);

register_pager("livestream/delete", array(
		'use' => 'livestreaming::livestream@livestream_delete_pager',
		'as' => 'livestream-delete',
		'filter' => 'auth')
);

register_pager("livestream/start", array(
		'use' => 'livestreaming::livestream@start_pager',
		'as' => 'livestream-start',
		'filter' => 'auth')
);

register_pager("livestream/live", array(
		'use' => 'livestreaming::livestream@go_live_pager',
		'as' => 'livestream-live',
		'filter' => 'auth')
);

register_pager('livestream/commentdata', array(
		'use' => 'livestreaming::livestream@load_commentdata_pager',
		'as' => 'livestream-commentdata',
		'filter' => 'auth')
);

register_pager("livestream/comment", array(
		'use' => 'livestreaming::livestream@comment_pager',
		'as' => 'livestream-comment',
		'filter' => 'auth')
);

register_pager("livestream/livestill", array(
		'use' => 'livestreaming::livestream@livestill_pager',
		'as' => 'livestream-livestill',
		'filter' => 'auth')
);

register_pager("livestream/joinlivestream", array(
		'use' => 'livestreaming::livestream@joinlivestream_pager',
		'as' => 'livestream-join',
		'filter' => 'auth')
);

register_pager("livestream/leavelivestream", array(
		'use' => 'livestreaming::livestream@leavelivestream_pager',
		'as' => 'livestream-join',
		'filter' => 'auth')
);

register_pager("livestream/participatingstill", array(
		'use' => 'livestreaming::livestream@participatingstill_pager',
		'as' => 'livestream-participatingstill',
		'filter' => 'auth')
);

register_pager("livestream/end", array(
		'use' => 'livestreaming::livestream@end_pager',
		'as' => 'livestream-end',
		'filter' => 'auth')
);

register_pager("livestream/viewright", array(
		'use' => 'livestreaming::livestream@livestream_view_right_pager',
		'as' => 'livestream-right-view',
		'filter' => 'auth')
);

register_pager("livestream/cred", array(
		'use' => 'livestreaming::livestream@livestream_cred_pager',
		'as' => 'livestream-cred',
		'filter' => 'auth')
);

register_pager("livestream/morecomments", array(
		'use' => 'livestreaming::livestream@more_livestream_comments_pager',
		'as' => 'livestream-comments',
		'filter' => 'auth')
);

register_pager("livestream/submitcred", array(
		'use' => 'livestreaming::livestream@submit_livestream_cred_pager',
		'as' => 'livestream-submit-cred',
		'filter' => 'auth'));

register_pager("livestream/{id}", array(
		'use' => 'livestreaming::livestream@livestream_page_pager',
		'as' => 'livestream-page',
		'filter' => 'livestream-auth')
)->where(array('id' => '[a-zA-Z0-9\-\_]+'));

register_pager('{id}/livestreams', array(
		'use' => 'livestreaming::user-profile@livestreams_pager',
		'as' => 'profile-livestreams',
		'filter' => 'profile')
)->where(array('id' => '[a-zA-Z0-9\_\-\.]+'));

register_hook('can.start.livestream', function($result, $type, $id) {
	if($type == 'user' and $id != get_userid()) $result['result'] = false;
	return $result;
});

register_hook('livestream.started', function($livestream, $livestreamId) {
	if($livestream['privacy'] == 3) {
		send_notification(get_userid(), 'livestream.started', $livestream['id'], $livestream);
		return;
	}
	$friends = get_friends();

	foreach($friends as $userid) {
		if($userid != get_userid()) {
			send_notification($userid, 'livestream.started', $livestream['id'], $livestream);
		}
	}
});

register_hook('livestream.end', function($livestream, $livestreamId) {
	if($livestream['entity_type'] == 'user' and $livestream['status']) {
		add_activity(get_livestream_url($livestream), null, 'livestream', $livestreamId, $livestream['privacy']);
		add_feed(array(
				'entity_id' => $livestream['entity_id'],
				'entity_type' => $livestream['entity_type'],
				'type' => 'feed',
				'type_id' => 'start-livestream',
				'type_data' => $livestream['id'],
				'privacy' => $livestream['privacy'],
				'images' => '',
				'auto_post' => true,
				'can_share' => 1
			)
		);
	}
	$subscribers = get_participants($livestreamId, false);
	foreach($subscribers as $key => $subscriber) {
		if($key != get_userid()) {
			send_notification($key, 'livestream.end', $livestream['id'], $livestream);
		}
	}
});

register_hook('feed.post.plugins.hook', function($type_id, $type_data) {
	if($type_id == 'start-livestream') {
		$livestream = get_livestream($type_data);
		return view('livestreaming::feed_stream', array('livestream' => $livestream, 'from_feed' => true)
		);
	}
});

register_hook('feed-title', function($feed) {
	if($feed['type_id'] == "start-livestream") {
		echo lang('livestreaming::shared-livestream');
	}
});

register_hook('profile.started', function($user) {
	add_menu('user-profile-more', array('title' => lang('livestreaming::livestreams'),
			'as' => 'livestreams', 'link' => profile_url('livestreams', $user))
	);
});

register_hook('feed.arrange', function($feed) {
	if(isset($feed['livestreaming'])) {
		$livestream = get_livestream($feed['livestreaming']);

		if($livestream) {
			if($livestream['status'] == 0 and ($feed['user_id'] != get_userid())) $feed['status'] = 0;
			$feed['livestreamDetails'] = $livestream;
		}
	}
	return $feed;
});

register_hook("activity.title", function($title, $activity, $user) {
	switch($activity['type']) {
		case 'livestream':
			$livestreamId = $activity['type_id'];
			$livestream = get_livestream($livestreamId);
			if(!$livestream) return "invalid";
			$link = get_livestream_url($livestream);
			$owner = get_livestream_owner($livestream);

			return activity_form_link($owner['link'], $owner['name'], true)." ".lang("activity::added-new")." ".activity_form_link($activity['link'], lang('livestreaming::livestream'), true, true);
		break;
	}

	return $title;
});

register_hook("display.notification", function($notification) {
	if($notification['type'] == 'livestream.started') {
		return view("livestreaming::notifications/running", array('notification' => $notification, 'livestream' => get_livestream($notification['type_id']))
		);
	} elseif($notification['type'] == 'livestream.end') {
		return view("livestreaming::notifications/end", array('notification' => $notification, 'livestream' => get_livestream($notification['type_id']))
		);
	} elseif($notification['type'] == 'livestream.like') {
		return view("livestreaming::notifications/like", array('notification' => $notification, 'type' => 'like')
		);
	} elseif($notification['type'] == 'livestream.like.react') {
		return view("livestreaming::notifications/react", array('notification' => $notification)
		);
	} elseif($notification['type'] == 'livestream.dislike') {
		return view("livestreaming::notifications/like", array('notification' => $notification, 'type' => 'dislike')
		);
	} elseif($notification['type'] == 'livestream.like.comment') {
		return view("livestreaming::notifications/like-comment", array('notification' => $notification, 'type' => 'like')
		);
	} elseif($notification['type'] == 'livestream.dislike.comment') {
		return view("livestreaming::notifications/like-comment", array('notification' => $notification, 'type' => 'dislike')
		);
	} elseif($notification['type'] == 'livestream.comment') {
		$livestream = get_livestream($notification['type_id']);
		if($livestream) {

			return view("livestreaming::notifications/comment", array('notification' => $notification, 'livestream' => $livestream)
			);
		} else {
			delete_notification($notification['notification_id']);
		}

	} elseif($notification['type'] == 'livestream.comment.reply') {
		return view("livestreaming::notifications/reply", array('notification' => $notification)
		);
	}
});

register_hook("like.item", function($type, $typeId, $userid) {
	if($type == 'livestream') {
		$livestream = get_livestream($typeId);
		if($livestream['user_id'] and $livestream['user_id'] != get_userid()) {
			send_notification_privacy('notify-site-like', $livestream['user_id'], 'livestream.like', $typeId, $livestream);
		}
	} elseif($type == 'comment') {
		$comment = find_comment($typeId, false);
		if($comment and $comment['user_id'] != get_userid()) {
			if($comment['type'] == 'livestream') {
				$livestream = get_livestream($comment['type_id']);
				send_notification_privacy('notify-site-like', $comment['user_id'], 'livestream.like.comment', $comment['type_id'], $livestream);
			}
		}
	}
});

register_hook("like.react", function($type, $typeId, $code, $userid) {
	if($type == 'livestream') {
		$livestream = get_livestream($typeId);
		if($livestream['user_id'] and $livestream['user_id'] != get_userid()) {
			send_notification_privacy('notify-site-like', $livestream['user_id'], 'livestream.like.react', $typeId, $livestream, $code);
		}
	}
});

register_hook("comment.add", function($type, $typeId, $text) {
	if($type == 'livestream') {
		$livestream = get_livestream($typeId);
		$subscribers = get_subscribers($type, $typeId);
		foreach($subscribers as $userid) {
			if($userid != get_userid()) {
				send_notification_privacy('notify-site-comment', $userid, 'livestream.comment', $typeId, $livestream, null, $text);
			}
		}

	}
});

register_hook("reply.add", function($commentId, $type, $typeId, $text) {
	if($type == 'livestream') {
		$livestream = get_livestream($typeId);
		$subscribers = get_subscribers('comment', $commentId);
		foreach($subscribers as $userid) {
			if($userid != get_userid()) {
				send_notification_privacy('notify-site-reply-comment', $userid, 'livestream.comment.reply', $typeId, $livestream, null, $text);
			}
		}
	}
});

register_hook("display.notification", function($notification) {
	if($notification['type'] == 'livestream.like') {
		if(isset($notification['data']['user_id'])) return view("livestreaming::notifications/like", array('notification' => $notification, 'livestream' => unserialize($notification['data']))
		);
	} elseif($notification['type'] == 'livestream.like.react') {
		if(isset($notification['data']['user_id'])) return view("livestreaming::notifications/react", array('notification' => $notification, 'livestream' => unserialize($notification['data']))
		);
	} elseif($notification['type'] == 'livestream.like.comment') {
		if(isset($notification['data']['user_id'])) return view("livestreaming::notifications/like-comment", array('notification' => $notification, 'livestream' => unserialize($notification['data']))
		);
	} elseif($notification['type'] == 'livestream.comment') {
		if(isset($notification['data']['user_id'])) return view("livestreaming::notifications/comment", array('notification' => $notification, 'livestream' => unserialize($notification['data']))
		);
	} elseif($notification['type'] == 'livestream.comment.reply') {
		if(isset($notification['data']['user_id'])) return view("livestreaming::notifications/reply", array('notification' => $notification, 'livestream' => unserialize($notification['data']))
		);
	}
});


add_menu_location('livestream-menu', 'livestreaming::livestream-menu');

add_available_menu('livestreaming::livestream', 'livestreams', 'ion-radio-waves');

add_available_menu('livestreaming::all-livestreams', 'livestreams', 'ion-radio-waves', 'livestream-menu');

register_hook('user.delete', function($userid) {
	$query = db()->query("SELECT id FROM livestreams WHERE user_id='{$userid}'");
	while($fetch = $query->fetch_assoc()) {
		delete_livestream($fetch['id']);
	}
});

register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => lang('livestreaming::livestream-permissions'),
		'description' => '',
		'roles' => array(
			'can-start-livestream' => array('title' => lang('can-start-livestream'), 'value' => 1),
		)
	);
	return $roles;
});