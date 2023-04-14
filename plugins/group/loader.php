<?php
load_functions('group::group');
register_asset('group::css/group.css');
register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
		//register_asset("group::css/group.css");
		register_asset("group::js/group.js");
	}
});

register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => 'Group Permissions',
		'description' => '',
		'roles' => array(
			'can-create-group' => array('title' => lang('group::can-create-group'), 'value' => 1),
		)
	);
	return $roles;
});

register_hook('uid.check', function($result, $value, $type = null, $type_id = null) {
	if(!$type || $type == 'group') {
		$group = find_group($value);
		if($group) {
			if(!$type_id || ($type_id && $type_id != $group['group_id'])) {
				$result[0] = false;
			}
		}
	}
	return $result;
});

register_hook('group.added', function($groupId, $val) {
    $group = find_group($groupId);
    if($group['privacy'] == '1' and $group['user_id']) {
        add_activity($group['group_name'], null, 'group', $groupId, $group['privacy']);
        add_feed(array(
            'entity_id' => $group['user_id'],
            'entity_type' => 'user',
            'type' => 'feed',
            'type_id' => 'group-added',
            'type_data' => $groupId,
            'group' => $groupId,
            'privacy' => $group['privacy'],
            'images' => '',
            'auto_post' => true,
            'can_share' => 1
        ));
    }
});

register_hook('feed.arrange', function($feed) {
    if(is_numeric($feed['a_group'])) {
        $group = find_group($feed['a_group']);

        if($group) {
            if($group['privacy'] == 1 and ($group['user_id'] != get_userid())) $group['status'] = 0;
            $feed['groupDetails'] = $group;
        }
    }
    return $feed;
});
register_hook("activity.title", function($title, $activity, $user) {
    switch($activity['type']) {
        case 'group':
            $groupId = $activity['type_id'];
            $group = find_group($groupId);
            if(!$group) return "invalid";
            $link = $group['group_name'];
            $owner = find_user($group['user_id']);
            $owner['link'] = url($owner['username']);
            $ownerName = get_user_name($owner);
            return activity_form_link($owner['link'], $ownerName, true)." ".lang("activity::added-new")." ".activity_form_link($activity['link'], lang('group::group'), true, true);
            break;
    }
    return $title;
});

register_hook('feed-title', function($feed) {
    if($feed['type_id'] == "group-added") {
        echo lang('group::added-group');
    }
});

register_hook('feed.custom.column.sql', function ($columns, $val){
    if (isset($val['group'])){
        $columns[0] = !isset($columns) ? '' : ',a_group';
    }
    return $columns;
});

register_hook('feed.custom.value.sql', function ($value, $val){
    if (isset($val['group'])){
        $value[0] = !isset($value) ? '' : ",'{$val['group']}'";
    }
    return $value;
});

register_hook('feeds.query.fields', function ($fields, $more = null){
    $fields .=",a_group";
    return $fields;
});
register_hook('feed.post.plugins.hook', function($feed) {
    if ($feed['a_group']){
        $group = find_group($feed['a_group']);
        echo view("group::feed-content", array('group'=> $group));
    }
});

if(is_loggedIn() && user_has_permission('can-create-group')) {
	register_pager("group/create", array('use' => "group::group@create_group_pager", 'filter' => 'user-auth', 'as' => 'group-create'));
}

register_pager("groups", array('use' => "group::group@manage_group_pager", 'filter' => 'user-auth', 'as' => 'group-manage'));
register_post_pager("group/change/cover", array('use' => 'group::profile@upload_cover_pager', 'filter' => 'user-auth'));
register_pager("group/cover/reposition", array('use' => 'group::profile@reposition_cover_pager', 'filter' => 'user-auth'));
register_pager("group/cover/remove", array('use' => 'group::profile@remove_cover_pager', 'filter' => 'user-auth'));
register_pager("group/change/logo", array('use' => 'group::profile@change_logo_pager', 'filter' => 'user-auth'));
register_pager("group/member/role", array('use' => 'group::profile@member_role_pager', 'filter' => 'user-auth'));
register_pager("group/add/member", array('use' => 'group::profile@add_member_pager', 'filter' => 'user-auth'));
register_pager("group/remove/member", array('use' => 'group::profile@remove_member_pager', 'filter' => 'user-auth'));
register_pager('group/category/{slug}', array('use' => 'group::profile@group_category_pager', 'filter' => 'user-auth', 'as' => 'group-category-profile'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
register_pager("group/join", array('use' => 'group::profile@join_pager', 'filter' => 'user-auth'));

register_hook('system.started', function() {
	register_pager("{slug}", array('use' => "group::profile@group_profile_pager", 'filter' => 'group-profile', 'as' => 'group-profile'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	register_pager("{slug}/edit", array('use' => "group::profile@group_profile_edit_pager", 'filter' => 'group-profile', 'as' => 'group-profile-edit'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	register_pager("{slug}/members", array('use' => "group::profile@group_profile_members_pager", 'filter' => 'group-profile', 'as' => 'group-profile-members'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	//register_pager("{slug}/about", array('use' => "group::profile@group_about_profile_pager", 'filter' => 'group-profile', 'as' => 'group-profile-about'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	register_pager("{slug}/photos", array('use' => "group::profile@group_photos_profile_pager", 'filter' => 'group-profile', 'as' => 'group-profile-photos'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	if(plugin_loaded('event')) {
		register_pager("{slug}/events", array('use' => "group::profile@group_events_profile_pager", 'filter' => 'group-profile', 'as' => 'group-profile-events'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	}
	if(plugin_loaded('blog')) {
		register_pager("{slug}/blogs", array('use' => "group::profile@group_blogs_profile_pager", 'filter' => 'group-profile', 'as' => 'group-profile-blogs'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	}
	if(plugin_loaded('music')) {
		register_pager("{slug}/musics", array('use' => "group::profile@group_musics_profile_pager", 'filter' => 'group-profile', 'as' => 'group-profile-musics'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	}
	if(plugin_loaded('video')) {
		register_pager("{slug}/videos", array('use' => "group::profile@group_videos_profile_pager", 'filter' => 'group-profile', 'as' => 'group-profile-videos'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	}
	if(plugin_loaded('livestreaming')) {
		register_pager("{slug}/livestreams", array('use' => "group::profile@group_livestreams_profile_pager", 'filter' => 'group-profile', 'as' => 'group-profile-livestreams'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	}
});

register_filter("group-profile", function($app) {
	$slug = segment(0);

	$group = find_group($slug);
	if(!$group) return false;
	if($group['privacy'] == 2 and !is_group_member($group['group_id'])) return false;
	$app->profileGroup = $group;
	$app->groupUser = find_user($group['user_id']);
	$app->setTitle($group['group_title'])->setLayout("group::profile/layout");

	//register page profile menu
	add_menu("group-profile", array('id' => 'posts', 'title' => lang('group::discussion'), 'link' => group_url('', $group)));
	add_menu("group-profile", array('id' => 'members', 'title' => lang('group::members'), 'link' => group_url('members', $group)));
	add_menu("group-profile", array('id' => 'photos', 'title' => lang('group::photos'), 'link' => group_url('photos', $group)));
	if(plugin_loaded('event')) {
		add_menu("group-profile", array('id' => 'events', 'title' => lang('group::events'), 'link' => group_url('events', $group)));
	}
	if(plugin_loaded('blog')) {
		add_menu("group-profile", array('id' => 'blogs', 'title' => lang('group::blogs'), 'link' => group_url('blogs', $group)));
	}
	if(plugin_loaded('music')) {
		add_menu("group-profile", array('id' => 'musics', 'title' => lang('group::musics'), 'link' => group_url('musics', $group)));
	}
	if(plugin_loaded('video')) {
		add_menu("group-profile", array('id' => 'videos', 'title' => lang('group::videos'), 'link' => group_url('videos', $group)));
	}
	if(plugin_loaded('livestreaming')) {
		add_menu("group-profile", array('id' => 'livestreams', 'title' => lang('group::livestreams'), 'link' => group_url('livestreams', $group)));
	}
	//add_menu("page-profile-more", array('id' => 'likes', 'title' => lang('page::likes'), 'link' => page_url('likes', $page)));
	fire_hook('group.profile.started', null, array($group));

	return true;
});

register_hook('feeds.query', function($type, $type_id) {
	if($type == 'group') {
		$sql_fields = get_feed_fields();
		$sql = "SELECT ".$sql_fields." FROM `feeds` WHERE ";
		$sql .= " ((type = 'group' AND type_id = '".$type_id."') OR (entity_type = 'group' AND entity_id = '".$type_id."'))";
		$pinned_posts = get_pinned_feeds();
		$pinned_posts[] = 0;
		$pinned_posts = implode(',', $pinned_posts);
		$sql .= " AND feed_id NOT IN (".$pinned_posts.")";
		return $sql;
	}
});

register_hook('feed.edit.check', function($result, $feed) {
	if($feed['type'] == 'group') {
		$group = (isset($feed['group'])) ? $feed['group'] : find_group($feed['type_id']);
		$feed['group'] = $group;
		if(is_group_admin($group, false, false)) $result['edit'] = true;
	}
	return $result;
});

register_hook('feed.pin.check', function($result, $feed) {
	if($feed['type'] == 'group') {
		$group = (isset($feed['group'])) ? $feed['group'] : find_group($feed['type_id']);
		$feed['group'] = $group;
		if(is_group_admin($group, false, false)) $result['edit'] = true;
	}
	return $result;
});
register_hook('feed.get.publisher', function($feed) {
	if((isset($feed['entity_type']) && $feed['entity_type'] == 'group') || (isset($feed['type']) && $feed['type'] == 'group')) {
		$group_id = isset($feed['entity_type']) && $feed['entity_type'] == 'group' ? $feed['entity_id'] : $feed['type_id'];
		if(config('anonymous-group', false)) {
			$group = find_group($group_id, false);
			if($group) {
				if(config('feed-user-title', 2) == 1) $group['group_name'] = $group['group_title'];
				$feed['publisher'] = $group;
				$feed['publisher']['avatar'] = get_group_logo(75, $group);
				$feed['publisher']['url'] = group_url(null, $group);
				$feed['publisher']['name'] = $group['group_title'];
			}
		} else {
			$user = find_user($feed['user_id'], false);
			if($user) {
				if(config('feed-user-title', 2) == 1) $user['name'] = $user['username'];
				$feed['publisher'] = $user;
				$feed['publisher']['avatar'] = get_avatar(75, $user);
				$feed['publisher']['url'] = profile_url(null, $user);
			}
		}
		return $feed['publisher'];
	}
});

if(config('enable-group-posts-in-timeline', true)) {
	register_hook('user.feeds.query', function($sql) {
		$groups = get_joined_groups();
		$groups[] = 0;
		$groupsId = implode(',', $groups);
		//4 is for public and 5 is private group
		$sql .= " OR (privacy='4' AND type='group' AND type_id IN ({$groupsId})) ";
		return $sql;
	});
}

register_hook('feed.edit.privacy.check', function($result, $feed) {
	if($feed['type'] == 'group') {
		$result['edit'] = false;
	}
	return $result;
});
register_hook('feed-title', function($feed) {
	if($feed['type'] == 'group' and !isset(app()->profileGroup)) {
		$group = find_group($feed['type_id'], true);
		echo "<i class='ion-arrow-right-c'></i> "."<a ajax='true' href='".group_url(null, $group)."'>".$group['group_title']."</a>";
	}
});
register_hook("display.notification", function($notification) {
	if($notification['type'] == 'group.role') {
		$group = find_group($notification['type_id']);
		if($group) return view("group::notifications/role", array('notification' => $notification, 'group' => $group));
		delete_notification($notification['notification_id']); //ensure deletion of this notification
	} elseif($notification['type'] == 'group.add.member') {
		$group = find_group($notification['type_id']);
		if($group) return view("group::notifications/member", array('notification' => $notification, 'group' => $group));
		delete_notification($notification['notification_id']); //ensure deletion of this notification
	} elseif($notification['type'] == 'group.post') {
		$group = find_group($notification['type_id']);
		if($group) {
			return view("group::notifications/post", array('notification' => $notification, 'group' => $group));
		} else {
			delete_notification($notification['notification_id']);
		}
	}
});


register_hook('search-dropdown-start', function($content, $term) {
	$groups = get_groups('search', $term, 5);
	if($groups->total) {
		$content .= view('group::search/dropdown', array('groups' => $groups));
	}
	return $content;
});

register_hook('register-search-menu', function($term) {
	add_menu("search-menu", array('title' => lang('group::groups'), 'id' => 'group', 'link' => form_search_link('group', $term)));
});

register_hook('search-result', function($content, $term, $type) {
	if($type == 'group') {
		get_menu('search-menu', 'group')->setActive();
		$content = view('group::search/page', array('groups' => get_groups('search', $term)));
	}
	return $content;
});

register_hook("admin-started", function() {
	get_menu('admin-menu', 'plugins')->addMenu(lang('group::group-manager'), '#', 'groups-manager');
	get_menu('admin-menu', 'plugins')->findMenu('groups-manager')->addMenu(lang("group::groups-manager"), url('admincp/groups'), "groups-manager");
	get_menu('admin-menu', 'plugins')->findMenu('groups-manager')->addMenu(lang('group::categories'), url_to_pager('admin-group-categories-pager'), 'categories');

});

register_pager("admincp/group/action/batch", array('use' => "group::group@group_action_batch_pager", 'filter' => 'admin-auth', 'as' => 'admin-group-batch-action'));


register_pager("admincp/groups", array('use' => "group::group@admin_group_pager", 'filter' => 'admin-auth', 'as' => 'admin-group-lists'));
register_pager("group/delete/{id}", array('use' => "group::group@group_delete_pager", 'as' => 'group-delete', 'filter' => 'user-auth'))->where(array('id' => '[0-9]+'));
register_pager("admincp/group/edit/{id}", array('use' => "group::group@group_admin_edit_pager", 'as' => 'group-admin-edit', 'filter' => 'admin-auth'))->where(array('id' => '[0-9]+'));
register_pager("admincp/group/manage/categories", array('use' => 'group::admincp@manage_categories_pager', 'as' => 'admin-group-manage-category', 'filter' => 'admin-auth'));
register_pager("admincp/group/categories/add", array('use' => 'group::admincp@add_categories_pager', 'as' => 'admin-group-add-category-pager', 'filter' => 'admin-auth'));
register_pager("admincp/group/categories", array('use' => 'group::admincp@categories_pager', 'as' => 'admin-group-categories-pager', 'filter' => 'admin-auth'));


//page blocks
register_hook('admin-started', function() {
	register_block_page('group-profile', lang('group::group-profile'));
	register_block_page('groups', lang('group::groups'));

});

register_block("group::block/suggestion", lang('group::group-suggestions'), null, array(
		'limit' => array(
			'title' => lang('list-limit'),
			'description' => lang('list-limit-desc'),
			'type' => 'text',
			'value' => 6
		),)
);

register_block("group::block/profile", lang('group::user-profile-groups'), null, array(
		'limit' => array(
			'title' => lang('list-limit'),
			'description' => lang('list-limit-desc'),
			'type' => 'text',
			'value' => 6
		),)
);


register_hook('admin.statistics', function($stats) {
	$stats['groups'] = array(
		'count' => count_total_groups(),
		'title' => lang('group::groups'),
		'icon' => 'ion-ios-people-outline',
		'link' => '#',
	);
	return $stats;
});
register_hook('admin.charts', function($result, $months, $year) {
	$c = array(
		'name' => lang('group::groups'),
		'points' => array()
	);


	foreach($months as $name => $n) {
		$c['points'][$name] = count_groups_in_month($n, $year);

	}

	$result['charts']['members'][] = $c;


	return $result;
});

register_hook('user.delete', function($userid) {
	$d = db()->query("SELECT * FROM groups WHERE user_id='{$userid}'");
	while($group = $d->fetch_assoc()) {
		delete_group($group);
	}

	db()->query("DELETE FROM group_members WHERE member_id='{$userid}'");
});

register_hook('saved.content', function($content, $type) {
	add_menu('saved', array('title' => lang('group::groups').' <span style="color:lightgrey;font-size:12px">'.count(get_user_saved('group')).'</span>', 'link' => url('saved/groups'), 'id' => 'groups'));
	if($type == 'groups') {
		$content = view('group::saved/content', array('groups' => get_groups('saved')));
	}

	return $content;
});

register_pager("{id}/groups", array("use" => "group::user-profile@groups_pager", "as" => "profile-groups", 'filter' => 'profile'))
	->where(array('id' => '[a-zA-Z0-9\_\-\.]+'));

register_hook('profile.started', function($user) {
	add_menu('user-profile-more', array('title' => lang('group::groups'), 'as' => 'groups', 'link' => profile_url('groups', $user)));
});

add_available_menu('group::groups', 'groups', 'ion-ios-people');


register_hook('find.feed', function($result) {
	if($result['feed']['type'] == 'group' && !is_group_member($result['feed']['type_id'])) {
		$result['status'] = false;
	}
	return $result;
});

register_hook('feed.exclude.type', function($result) {
	$result[] = 'group';
	return $result;
});

register_hook('feed.added', function($feed_id, $val) {
	$val = fire_hook('group.notification.enabled', $val, array());
	if($val['type'] == 'group') {
		$group_id = $val['type_id'];
		$group = find_group($group_id);
		$group_members_id = get_group_members_id($group_id);
		foreach($group_members_id as $user_id) {
			if($user_id != get_userid()) {
				send_notification($user_id, 'group.post', $group['group_id']);
			}
		}
	}
});

register_hook('preview.card', function($type, $id) {
	if($type == 'group') {
		$group = find_group($id);
		echo view('group::preview-card', array('group' => $group));
	}
});

register_hook('entity.data', function($entity, $type, $type_id) {
	if($type == 'group') {
		$group = find_group($type_id, false);
		$entity = array(
			'id' => $group['group_name'],
			'name' => $group['group_title'],
			'avatar' => get_group_logo(200, $group)
		);
	}
	return $entity;
});

register_hook('entity.select.list', function() {
	echo view('group::select/list');
});

register_hook('privacy', function($privacy) {
	$privacy['4'] = array(
		'icon' => 'ion-android-mail',
		'title' => lang('private'),
		'type' => 'group'
	);
	return $privacy;
});

register_hook('privacy.sql', function($sql) {
	$groups = get_joined_groups();
	$groups = array_merge($groups, array(0));
	$sql .= " OR ((entity_type = 'group' OR privacy = 4) AND entity_id IN (".implode(',', $groups)."))";
	return $sql;
});

register_hook('photos.get.sql', function($sql, $type, $id, $limit, $offset) {
	if($type == 'group') {
		$sql = "SELECT * FROM medias WHERE file_type = 'image' AND ((entity_type = 'group' AND entity_id = '".$id."') OR (type = 'group-posts' AND type_id = '".$id."')) ORDER BY id DESC LIMIT ".$offset.", ".$limit;
	}
	return $sql;
});

register_hook('photo.get.publisher', function($photo) {
	if(in_array($photo['type'], array('group', 'group-posts', 'group-logo'))) {
		$user = find_user($photo['user_id'], false);
		if($user) {
			$publisher = $user;
			$publisher['avatar'] = get_avatar(75, $user);
			$publisher['url'] = profile_url(null, $user);
			return $publisher;
		}
	}
});