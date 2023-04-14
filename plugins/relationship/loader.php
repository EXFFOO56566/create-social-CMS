<?php
load_functions("relationship::relationship");
register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
		//register assets
		register_asset("relationship::css/relationship.css");
		register_asset("relationship::js/relationship.js");
	}
});

register_pager("admincp/relationship/list", array("use" => "relationship::admincp@relationship_list_pager", 'filter' => 'admin-auth', 'as' => 'admin-relationship-list'));
register_pager("admincp/relationship/add", array("use" => "relationship::admincp@add_relationship_fam_pager", 'filter' => 'admin-auth', 'as' => 'admin-relationship-add'));
register_pager("admincp/relationship/edit", array("use" => "relationship::admincp@edit_relationship_fam_pager", 'filter' => 'admin-auth', 'as' => 'admin-relationship-edit'));
register_pager("admincp/relationship/delete", array("use" => "relationship::admincp@delete_relationship_fam_pager", 'filter' => 'admin-auth', 'as' => 'admin-relationship-delete'));
register_hook('admin-started', function() {
    get_menu('admin-menu', 'plugins')->addMenu(lang('Relationship'), '#', 'relationship');
    get_menu('admin-menu', 'plugins')->findMenu('relationship')->addMenu(lang('Relationships'), url_to_pager('admin-relationship-list'), 'relationships');
    get_menu('admin-menu', 'plugins')->findMenu('relationship')->addMenu(lang('Add Relationship'), url_to_pager('admin-relationship-add'), 'add-relationship');
});

register_get_pager("relationship/follow", array("use" => "relationship::relationship@process_follow_pager", 'filter' => 'auth'));
register_get_pager("relationship/add/friend", array("use" => "relationship::relationship@add_friend_pager", 'filter' => 'auth'));
register_get_pager("relationship/remove/friend", array("use" => "relationship::relationship@remove_friend_pager", 'filter' => 'auth'));
register_get_pager("relationship/load/requests", array("use" => "relationship::relationship@load_friend_requests_pager", 'filter' => 'auth'));
register_get_pager("friend/requests/preload", array("use" => "relationship::relationship@preload_friend_requests_pager", 'filter' => 'auth'));
register_get_pager("friend/request/confirm", array("use" => "relationship::relationship@confirm_friend_request_pager", 'filter' => 'auth'));
register_get_pager("friend/requests", array('use' => "relationship::relationship@friend_requests_pager", 'filter' => 'auth', 'as' => 'friend-requests'));
register_get_pager("suggestions", array('use' => "relationship::relationship@suggestions_pager", 'filter' => 'auth', 'as' => 'suggestions'));
/**Notification display hook**/
register_hook("display.notification", function($notification) {
	if($notification['type'] == 'relationship.follow') {
		return view("relationship::notifications/follow", array('notification' => $notification));
	} elseif($notification['type'] == 'relationship.confirm') {
		return view("relationship::notifications/friend", array('notification' => $notification));
	}
});

register_hook("plugin.check", function($plugin) {
	if($plugin == 'relationship') {

	}
});


register_block("relationship::suggestion-block", lang('relationship::people-suggestion'), null, array(
	'limit' => array(
		'title' => lang('relationship::people-suggestion-limit'),
		'description' => lang('relationship::people-suggestion-limit-desc'),
		'type' => 'text',
		'value' => 5
	),
));

register_block("relationship::block/friends", lang('relationship::user-friends'), null, array(
	'limit' => array(
		'title' => lang('relationship::user-list-limit'),
		'description' => lang('relationship::user-list-limit-desc'),
		'type' => 'text',
		'value' => 6
	),
));

register_block("relationship::block/followers", lang('relationship::user-followers'), null, array(
	'limit' => array(
		'title' => lang('relationship::user-list-limit'),
		'description' => lang('relationship::user-list-limit-desc'),
		'type' => 'text',
		'value' => 6
	),
));

register_block("relationship::block/following", lang('relationship::user-following'), null, array(
	'limit' => array(
		'title' => lang('relationship::user-list-limit'),
		'description' => lang('relationship::user-list-limit-desc'),
		'type' => 'text',
		'value' => 6
	),
));

if(is_loggedIn()) {
	add_menu("dashboard-main-menu", array("icon" => "<i class='ion-android-person-add'></i>", "id" => "find-friends", "title" => lang("find-friends"), "link" => url_to_pager("suggestions")));
}

register_hook('profile.started', function($user) {
	$count = (is_loggedIn() and $user['id'] != get_userid()) ? count(get_mutual_friends($user['id'])) : '';
	$count = ($count) ? "<span style='color:lightgrey;font-size:11px'>".$count.' '.lang('mutual')."</span>" : '';
	if(config('relationship-method', 3) == 2) {
        if(fire_hook("friendship.custom.relation", $user,array(true, 'profile'))) {
            add_menu("user-profile", array('title' => lang('relationship::friends') . ' ' . $count, 'link' => profile_url('friends', $user), 'id' => 'connections'));
        }
	} elseif((config('relationship-method', 3) == 1)) {
		add_menu("user-profile", array('title' => lang('relationship::followers'), 'link' => profile_url('followers', $user), 'id' => 'connections_followers'));
		add_menu("user-profile", array('title' => lang('relationship::following'), 'link' => profile_url('following', $user), 'id' => 'connections_following'));
	} elseif((config('relationship-method', 3) == 3)) {
        if(fire_hook("friendship.custom.relation", $user,array(true, 'profile'))) {
            add_menu("user-profile", array('title' => lang('relationship::friends') . ' ' . $count, 'link' => profile_url('friends', $user), 'id' => 'connections'));
        }
	}
});

register_pager("{id}/friends", array("use" => "relationship::profile@friends_pager", "as" => "profile-friends", 'filter' => 'profile'))
	->where(array('id' => '[a-zA-Z0-9\_\-\.]+'));
register_pager("{id}/following", array(
		"use" => "relationship::profile@following_pager",
		"as" => "profile-following",
		'filter' => 'profile')
)->where(array('id' => '[a-zA-Z0-9\_\-\.]+'));

register_pager("{id}/followers", array("use" => "relationship::profile@followers_pager", "as" => "profile-followers", 'filter' => 'profile'))
	->where(array('id' => '[a-zA-Z0-9\_\-\.]+'));

register_hook('user.delete', function($userid) {
	db()->query("DELETE FROM relationship WHERE from_userid='{$userid}' OR to_userid='{$userid}'");
});

register_hook("relationship.request.list", function($user) {
	echo $user['type'] == 2 ? lang('relationship::friend-request') : null;
});

register_hook("relationship.request.confirm.script", function($user) {
	echo ($user['type'] == 2) ? 'return confirm_friend_request('.$user['id'].')' : null;
});

register_hook("relationship.request.delete.script", function($user) {
	echo $user['type'] == 2 ? 'return delete_friend_request('.$user['id'].')' : null;
});

// Relationship status and family relationship
register_hook('user-profile-about', function($type, $app) {
	add_menu('user-profile-about', array('title' => lang('relationship::relationship-status'), 'link' => profile_url('about?type=relationship', $app->profileUser), 'id' => 'relationship'));
});

register_hook('user-profile-about-content', function($type, $app) {
	if($type == 'relationship') {
        get_menu("user-profile-about", "relationship")->setActive();
		return view('relationship::relationship', array('user' => $app->profileUser));
	}
});

register_pager("relationship/add/member", array('use' => 'relationship::relation@select_family_pager', 'filter' => 'user-auth'));
register_pager("relations/edit", array('use' => 'relationship::relation@relation_edit_pager', 'filter' => 'user-auth'));
register_pager("relationship/save", array('use' => 'relationship::relation@relation_save_pager', 'filter' => 'user-auth'));
register_pager("relationship/accept/request", array('use' => 'relationship::relation@accept_relation_request_pager', 'filter' => 'user-auth', 'as' => 'relation-accept'));
register_pager("relationship/decline/request", array('use' => 'relationship::relation@decline_relation_request_pager', 'filter' => 'user-auth', 'as' => 'relation-decline'));
register_pager("relationship/delete/request", array('use' => 'relationship::relation@delete_relation_request_pager', 'filter' => 'user-auth', 'as' => 'delete-request'));
register_pager("relationship/family/request", array('use' => 'relationship::relation@add_family_pager', 'filter' => 'user-auth'));
register_pager("relationship/edit/status", array('use' => 'relationship::relation@user_relation_update_pager', 'filter' => 'user-auth', 'as' => 'relation-update'));

register_hook('display.notification', function($notification) {
	if($notification['type'] == 'relationship.relation') {
		return view('relationship::notifications/relation', array('notification' => $notification, 'data' => unserialize($notification['data'])));
	}
});

register_hook('display.notification', function($notification) {
	if($notification['type'] == 'relationship.accept') {
		return view('relationship::notifications/accept', array('notification' => $notification, 'data' => unserialize($notification['data'])));
	}
});

register_hook('privacy', function($privacy) {
	$relationship_method = config('relationship-method', 3);
	$privacy['2'] = array(
		'icon' => 'ion-person-stalker',
		'title' => $relationship_method == 1 ? lang('followers-only') : ($relationship_method == 2 ? lang('friends-only') : lang('relationship::friends-followers')),
		'type' => 'relationship'
	);
	$privacy['3'] = array(
		'icon' => 'ion-android-lock',
		'title' => lang('only-me'),
		'type' => 'relationship'
	);
	return $privacy;
});

register_hook('privacy.sql', function($sql) {
	$user_id = get_userid();
	$user_id = $user_id ? $user_id : 0;
	$friends = get_friends($user_id);
	$followers = get_followers($user_id);
	$relationship_method = config('relationship-method', 3);
	$users = $relationship_method == 3 ? array_merge($friends, $followers) : ($relationship_method == 2 ? $friends : ($relationship_method == 1 ? $followers : array()));
	$users = array_merge($users, array($user_id));
	$sql .= " OR ((privacy = 2 AND user_id IN (".implode(',', $users).")) OR (privacy = 3 AND user_id = ".$user_id."))";
	return $sql;
});

register_hook('privacy.select', function($type = 'relationship', $default = 1, $name = 'val[privacy]') {
	echo view("relationship::privacy/select", array('type' => $type, 'default' => $default, 'name' => $name));
});


register_hook('relationship.connections', function($connections = array(0), $user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$relationship_method = config('relationship-method', 3);
	if($relationship_method == 1) {
		$connections = array_merge($connections, get_following($user_id));
		get_following($user_id);
	} elseif($relationship_method == 2) {
		$connections = array_merge($connections, get_friends($user_id));
	} elseif($relationship_method == 3) {
		$connections = array_merge($connections, get_following($connections), get_friends($connections));
	}
	return $connections;
});

register_hook('system.relationship.method', function($where_clause = null, $plugin, $userid = null) {
	$relationship_method = config('relationship-method', 3);
	switch($plugin) {
		case 'feed':
			$users = array($userid);
			if($relationship_method == 1) {
				$followings = array_merge($users, get_following($userid));
				$followings = implode(',', $followings);
				$where_clause .= " OR (entity_type='user' AND `privacy`='1' or privacy='2' AND `entity_id` IN ({$followings}))";
			} elseif($relationship_method == 2) {
				$friends = array_merge($users, get_friends($userid));
				$friends = implode(',', $friends);
				$where_clause .= " OR (entity_type='user' AND (privacy ='1' or privacy='2') AND `entity_id` IN ({$friends}))";
			} elseif($relationship_method == 3) {
				$followings = array_merge($users, get_following($userid));
				$followings = implode(',', $followings);
				$where_clause .= " OR (entity_type='user' AND `privacy`='1' AND `entity_id` IN ({$followings}))";

				$friends = array_merge($users, get_friends($userid));
				$friends = implode(',', $friends);
				$where_clause .= " OR (entity_type='user' AND (privacy ='1' or privacy='2') AND `entity_id` IN ({$friends}) AND entity_id IN ({$followings}))";
			}
		break;
		case 'music':
			if($relationship_method == 1) {
				$where_clause = array_merge($where_clause, get_following($userid));
			} elseif($relationship_method == 2) {
				$where_clause = array_merge($where_clause, get_friends($userid));
			} elseif($relationship_method == 3) {
				$where_clause = array_merge($where_clause, get_following($userid));
				$where_clause = array_merge($where_clause, get_friends($userid));
			}
		break;
		case 'video':
			if($relationship_method == 1) {
				$where_clause = array_merge($where_clause, get_following($userid));
			} elseif($relationship_method == 2) {
				$where_clause = array_merge($where_clause, get_friends($userid));
			} elseif($relationship_method == 3) {
				$where_clause = array_merge($where_clause, get_following($userid));
				$where_clause = array_merge($where_clause, get_friends($userid));
			}
		break;
		case 'livestream':
			if($relationship_method == 1) {
				$where_clause = array_merge($where_clause, get_following($userid));
			} elseif($relationship_method == 2) {
				$where_clause = array_merge($where_clause, get_friends($userid));
			} elseif($relationship_method == 3) {
				$where_clause = array_merge($where_clause, get_following($userid));
				$where_clause = array_merge($where_clause, get_friends($userid));
			}
		break;
		case 'photo':
			if($relationship_method == 1) {
				$where_clause = array_merge($where_clause, get_following($userid));
			} elseif($relationship_method == 2) {
				$where_clause = array_merge($where_clause, get_friends($userid));
			} elseif($relationship_method == 3) {
				$where_clause = array_merge($where_clause, get_following($userid));
				$where_clause = array_merge($where_clause, get_friends($userid));
			}
		break;
		case 'page':
			if($relationship_method == 1) {
				foreach(get_followers() as $user) {
					$where_clause = array_merge($where_clause, get_liked_items('page', $user));
				}
			} elseif($relationship_method == 2) {
				foreach(get_friends() as $user) {
					$where_clause = array_merge($where_clause, get_liked_items('page', $user));
				}
			} elseif($relationship_method == 3) {
				foreach(get_friends() as $user) {
					$where_clause = array_merge($where_clause, get_liked_items('page', $user));
				}
			}
		break;
		case 'event':
			if($relationship_method == 1) {
				$where_clause = (is_loggedIn()) ? get_followers() : array();
			} elseif($relationship_method == 2) {
				$where_clause = (is_loggedIn()) ? get_friends() : array();
			} elseif($relationship_method == 3) {
				$where_clause = (is_loggedIn()) ? get_friends() : array();
			}
		break;
		case 'group':
			if($relationship_method == 1) {
				foreach(get_followers() as $user) {
					$where_clause = array_merge($where_clause, get_joined_groups($user));
				}
			} elseif($relationship_method == 2) {
				foreach(get_friends() as $user) {
					$where_clause = array_merge($where_clause, get_joined_groups($user));
				}
			} elseif($relationship_method == 3) {
				foreach(get_friends() as $user) {
					$where_clause = array_merge($where_clause, get_joined_groups($user));
				}
			}
		break;
		case 'blog':
			if($relationship_method == 1) {
				$where_clause = array_merge($where_clause, get_following($userid));
			} elseif($relationship_method == 2) {
				$where_clause = array_merge($where_clause, get_friends($userid));
			} elseif($relationship_method == 3) {
				$where_clause = array_merge($where_clause, get_following($userid));
				$where_clause = array_merge($where_clause, get_friends($userid));
			}
		break;
	}
	return $where_clause;
});

register_hook('push.notification', function($result, $type, $detail) {
	if($type == 'friend-request') {
		$count = count($detail);
		if($count) {
			if($count == 1) {
				$relationship_id = $detail[0];
				$relationship = find_relationship($relationship_id);
				$from_user_id = $relationship['from_userid'];
				$from_user = find_user($from_user_id);
				$result['options']['title'] = lang('relationship::friend-request');
				$result['options']['body'] = lang('relationship::user-want-friend', array('name' => get_user_name($from_user)));
				$result['options']['link'] = profile_url('friends', $from_user);
				$result['options']['icon'] = get_avatar(75, $from_user);
				$result['status'] = true;
			} else {
				$notification['options']['title'] = lang('relationship::friend-requests');
				$notification['options']['body'] = lang('relationship::friend-requests-notification', array('num' => $count));
				$result['options']['link'] = url_to_pager('friend-request');
				$notification['status'] = true;
			}
		}
	}
	return $result;
});