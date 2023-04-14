<?php
load_functions("page::page");

register_hook('uid.check', function($result, $value, $type = null, $type_id = null) {
	if(!$type || $type == 'page') {
		$page = find_page($value);
		if($page) {
			if(!$type_id || ($type_id && $type_id != $page['id'])) {
				$result[0] = false;
			}
		}
	}
	return $result;
});

register_hook("profile-started", function($profileOwner) {
	if($profileOwner['id'] != get_userid()) {
		$userid = get_userid();
		$profileOwnerId = $profileOwner['id'];

	}
});

register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => 'Page Permissions',
		'description' => '',
		'roles' => array(
			'can-create-page' => array('title' => 'Can create a page', 'value' => 1)
		)
	);
	return $roles;
});

register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
		register_hook("dashboard-menu", function() {
			echo view('page::dashboard-menu');
		});
		register_asset("page::js/page.js");
		register_asset("page::css/page.css");
	}
});

register_hook('feeds.query', function($type, $type_id) {
	if($type == 'page') {
		$sql_fields = get_feed_fields();
		$sql = "SELECT ".$sql_fields." FROM `feeds` WHERE ";
		$sql .= " ((type = 'page' AND type_id = '".$type_id."') OR (entity_type = 'page' AND entity_id = '".$type_id."'))";
		$pinned_posts = get_pinned_feeds();
		$pinned_posts[] = 0;
		$pinned_posts = implode(',', $pinned_posts);
		$sql .= " AND feed_id NOT IN (".$pinned_posts.")";
		return $sql;
	}
});

register_hook('feed.get.publisher', function($feed) {
	if(isset($feed['entity_type']) and $feed['entity_type'] == 'page') {
		$page = find_page($feed['entity_id'], true);
		if($page) {
			$publisher = $page;
			$publisher['avatar'] = get_page_logo(75, $page);
			$publisher['url'] = page_url(null, $page);
			$publisher['name'] = $page['page_title'];
			return $publisher;
		}
	}
});

register_hook('comment.get.publisher', function($comment) {
	if($comment['entity_type'] == 'page') {
		$page = find_page($comment['entity_id'], true);
		if($page) {
			$publisher = $page;
			$publisher['avatar'] = get_page_logo(75, $page);
			$publisher['url'] = page_url(null, $page);
			$publisher['name'] = $page['page_title'];
			return $publisher;
		}
	}
});

register_hook('photo.get.publisher', function($photo) {
	if(in_array($photo['type'], array('page', 'page-posts', 'page-logo'))) {
		$page = find_page($photo['type_id'], false);
		if($page) {
			$publisher = $page;
			$publisher['avatar'] = get_page_logo(75, $page);
			$publisher['url'] = page_url(null, $page);
			$publisher['name'] = $page['page_title'];
			return $publisher;
		}
	}
});

register_hook('feed.arrange', function($feed) {
	if($feed['entity_type'] == 'page') {
		if(is_page_admin($feed['publisher'])) {
			$feed['editor'] = array(
				'avatar' => $feed['publisher']['avatar'],
				'id' => $feed['entity_id'],
				'type' => 'page'
			);
		}
	}
	return $feed;
});

register_hook('user.feeds.query', function($sql) {
	$pagesLiked = implode(',', get_liked_items('page'));
	$sql .= " OR (entity_type='page' AND entity_id IN ({$pagesLiked})) ";
	return $sql;
});

register_hook('preview.card', function($type, $id) {
	if($type == 'page') {
		$page = find_page($id);
		echo view('page::preview-card', array('page' => $page));
	}
});

register_hook('photo.arrange', function($photo) {
	if(in_array($photo['type'], array('page', 'page-posts', 'page-logo'))) {
		$page = $photo['publisher'];
		if($page and (is_page_admin($page))) {
			$photo['editor'] = array(
				'avatar' => $photo['publisher']['avatar'],
				'id' => $photo['publisher']['page_id'],
				'type' => 'page'
			);
		}
	}
	return $photo;
});

register_hook('comment.arrange', function($comment) {
	if($comment['owner'] != null and $comment['type'] == 'feed') {
		$feed = $comment['owner'];
		if($feed['entity_type'] == 'page') {
			if(is_page_admin($feed['publisher'])) {
				$comment['editor'] = array(
					'avatar' => $feed['publisher']['avatar'],
					'id' => $feed['entity_id'],
					'type' => 'page'
				);
			}
		}
	}

	if($comment['owner'] != null and $comment['type'] == 'photo') {
		$photo = $comment['owner'];
		if(in_array($photo['type'], array('page', 'page-posts'))) {
			$page = $photo['publisher'];
			if($page and (is_page_admin($page))) {
				$comment['editor'] = array(
					'avatar' => $photo['publisher']['avatar'],
					'id' => $photo['publisher']['page_id'],
					'type' => 'page'
				);
			}
		}
	}
	return $comment;
});

register_hook('comment.can-edit', function($result, $comment) {
	if($comment['entity_type'] == 'page') {
		$page = $comment['publisher'];
		if(is_page_admin($page) or is_page_editor($page) or is_page_moderator($page)) $result['status'] = true;
	}

	return $result;
});
register_hook('feed.edit.check', function($result, $feed) {
	if($feed['type'] == 'page') {
		$page = $feed['publisher'];
		if(is_page_admin($page) or is_page_editor($page) or is_page_moderator($page)) $result['edit'] = true;
	}
	return $result;
});

register_hook('feed.pin.check', function($result, $feed) {
	if($feed['type'] == 'page') {
		$page = $feed['publisher'];
		if(is_page_admin($page) or is_page_editor($page) or is_page_moderator($page)) $result['edit'] = true;
	}
	return $result;
});


register_hook('feed.edit.privacy.check', function($result, $feed) {
	if($feed['type'] == 'page') {
		$result['edit'] = false;
	}
	return $result;
});

register_hook('can.post.feed', function($result, $type, $typeId) {
	if($type == 'page') {
		$page = find_page($typeId, true);
		$result['result'] = (is_page_admin($page) or is_page_editor($page)) ? true : false;
	}
	return $result;
});

register_hook('page.logo.updated', function($pageId, $imgId, $image) {
	$images = array($imgId => $image);
	add_feed(array(
		'entity_id' => $pageId,
		'entity_type' => 'page',
		'type' => 'feed',
		'type_id' => 'change-avatar',
		'privacy' => 1,
		'auto_post' => true,
		'images' => perfectSerialize($images),
	));
});

register_hook('search-dropdown-start', function($content, $term) {
	$pages = get_pages('search', $term, 5);
	if($pages->total) {
		$content .= view('page::search/dropdown', array('pages' => $pages));
	}
	return $content;
});

register_hook('register-search-menu', function($term) {
	add_menu("search-menu", array('title' => lang('page::pages'), 'id' => 'page', 'link' => form_search_link('page', $term)));
});

register_hook('search-result', function($content, $term, $type) {
	if($type == 'page') {
		get_menu('search-menu', 'page')->setActive();
		$content = view('page::search/page', array('pages' => get_pages('search', $term)));
	}
	return $content;
});

register_hook("admin-started", function() {
	get_menu("admin-menu", "plugins")->addMenu(lang("page::page-manager"), '#', 'page-manager');
	get_menu("admin-menu", "plugins")->findMenu("page-manager")->addMenu(lang("page::lists"), url_to_pager('admin-page-lists'));
	get_menu("admin-menu", "plugins")->findMenu("page-manager")->addMenu(lang("page::manage-categories"), url_to_pager('admin-page-categories'), "admin-page-categories");
	get_menu("admin-menu", "plugins")->findMenu("page-manager")->findMenu("admin-page-categories")->addMenu(lang("page::lists"), url_to_pager("admin-page-categories"), 'list');
	get_menu("admin-menu", "plugins")->findMenu("page-manager")->findMenu("admin-page-categories")->addMenu(lang("page::add-category"), url_to_pager("admin-page-category-add"), 'add-category');

	//add custom field menus
	//get_menu('admin-menu', 'admin-custom-field')->addMenu(lang('page::pages'), '#', 'page-custom-fields');
	get_menu('admin-menu', 'settings')->findMenu('admin-custom-field')//->findMenu('users-custom-fields')
	->addMenu(lang("page::page-categories"), url_to_pager("admin-custom-fields-category").'?type=page', "page-categories")
		//->addMenu(lang("page::add-page-category"), url_to_pager("admin-custom-fields-category").'?action=add&type=page', "add-page-category")
		->addMenu(lang("page::page-fields"), url_to_pager("admin-user-custom-fields").'?type=page', "page-fields")//->addMenu(lang("page::add-page-field"), url_to_pager("admin-user-custom-fields").'?action=add&type=page', "add-page-field")
	;

	get_menu("admin-menu", "plugins")->findMenu("page-manager")->addMenu(lang("page::settings"), url_to_pager('admin-page-lists'), "admin-page-settings");
});

register_hook("admincp.custom-field", function($type) {
	if($type == 'page') {
		get_menu('admin-menu', 'admin-custom-field')->findMenu('page-custom-fields')->setActive();
	}
});

register_hook("custom-field.add", function($titleSlug) {
	db()->query("ALTER TABLE  `page_details` ADD  `{$titleSlug}` text NULL ;");
});

register_hook('photos.query', function($sql, $type, $id, $limit, $offset) {
	if($type == 'page') {
		$types = "'page','page-posts','page-logo'";
		$sql = "SELECT id,path FROM medias WHERE file_type = 'image' AND type_id='{$id}' AND type IN ({$types}) ORDER BY `id` DESC LIMIT {$offset},{$limit}";
	}
	return $sql;
});

register_filter("page-profile", function($app) {
	$slug = segment(0);

	$page = find_page($slug);

	if(!$page) return false;
	$app->profilePage = $page;
	$app->pageUser = find_user($page['page_user_id']);
	$app->setTitle($page['page_title'])->setLayout("page::profile/layout");

	$design = get_user_design_details($page);
	if(config('design-profile', true) and $design) app()->design = $design;
	//register page profile menu
	add_menu("page-profile", array('id' => 'timeline', 'title' => lang('page::timeline'), 'link' => page_url('', $page)));
	add_menu("page-profile", array('id' => 'about', 'title' => lang('page::about'), 'link' => page_url('about', $page)));
	add_menu("page-profile", array('id' => 'photos', 'title' => lang('page::photos'), 'link' => page_url('photos', $page)));
	if(plugin_loaded('event')) {
		add_menu("page-profile", array('id' => 'events', 'title' => lang('page::events'), 'link' => page_url('events', $page)));
	}
	if(plugin_loaded('blog')) {
		add_menu("page-profile", array('id' => 'blogs', 'title' => lang('page::blogs'), 'link' => page_url('blogs', $page)));
	}
	if(plugin_loaded('music')) {
		add_menu("page-profile", array('id' => 'musics', 'title' => lang('page::musics'), 'link' => page_url('musics', $page)));
	}
	if(plugin_loaded('video')) {
		add_menu("page-profile", array('id' => 'videos', 'title' => lang('page::videos'), 'link' => page_url('videos', $page)));
	}
	if(plugin_loaded('livestreaming')) {
		add_menu("page-profile", array('id' => 'livestreams', 'title' => lang('page::livestreams'), 'link' => page_url('livestreams', $page)));
	}
	//add_menu("page-profile-more", array('id' => 'likes', 'title' => lang('page::likes'), 'link' => page_url('likes', $page)));
	fire_hook('page.profile.started', null, array($page));

	return true;
});

register_filter("page-manage", function($app) {
	$slug = segment(1);

	$page = find_page($slug);
	if(!$page) return false;
	if(!is_page_admin($page) and !is_page_editor($page)) return false;
	$app->profilePage = $page;
	$app->pageUser = find_user($page['page_user_id']);
	$app->setTitle($page['page_title'])->setLayout('page::manage/layout');
	$design = get_user_design_details($page);
	if(config('design-profile', true) and $design) app()->design = $design;

	add_menu("page-manage", array('id' => 'general', 'title' => lang('page::general'), 'link' => manage_page_url('general', $page)));
	//add_menu("page-manage", array('id' => 'notifications', 'title' => lang('page::notifications'), 'link' => manage_page_url('notifications', $page)));
	if(is_page_admin($page)) {
		add_menu("page-manage", array('id' => 'page-roles', 'title' => lang('page::page-roles'), 'link' => manage_page_url('roles', $page)));
        add_menu("page-manage", array('id' => 'page-social-connections', 'title' => lang('social-connections'), 'link' => manage_page_url('social-connections', $page)));
        add_menu("page-manage", array('id' => 'page-info', 'title' => lang('page::page-info'), 'link' => manage_page_url('page-info', $page)));
	}
	foreach(get_custom_field_categories('page') as $category) {
		add_menu("page-manage", array('id' => $category['title'], 'title' => lang($category['title']), 'link' => manage_page_url('fields/'.$category['id'], $page)));
	}
	fire_hook('page.manage.started', null, array($page));
	return true;
});

register_pager("admincp/pages/action/batch", array('use' => "page::admincp@page_action_batch_pager", 'filter' => 'admin-auth', 'as' => 'admin-page-batch-action'));

register_pager("admincp/pages", array('use' => "page::admincp@lists_pager", 'filter' => 'admin-auth', 'as' => 'admin-page-lists'));
register_pager("admincp/page/edit", array('use' => "page::admincp@edit_pager", 'filter' => 'admin-auth', 'as' => 'admin-page-edit'));
register_pager("admincp/pages/categories", array('use' => "page::admincp@categories_pager", 'filter' => 'admin-auth', 'as' => 'admin-page-categories'));
register_pager("admincp/pages/category/add", array('use' => "page::admincp@add_category_pager", 'filter' => 'admin-auth', 'as' => 'admin-page-category-add'));
register_pager("admincp/pages/category/manage", array('use' => "page::admincp@manage_category_pager", 'filter' => 'admin-auth', 'as' => 'admin-page-manage-category'));


//frontend pager registration
register_hook('system.started', function() {
	register_pager("{slug}", array('use' => "page::profile@page_profile_pager", 'filter' => 'page-profile', 'as' => 'page-profile'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	register_pager("{slug}/about", array('use' => "page::profile@page_about_profile_pager", 'filter' => 'page-profile', 'as' => 'page-profile-about'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	register_pager("{slug}/photos", array('use' => "page::profile@page_photos_profile_pager", 'filter' => 'page-profile', 'as' => 'page-profile-photos'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	if(plugin_loaded('event')) {
		register_pager("{slug}/events", array('use' => "page::profile@page_events_profile_pager", 'filter' => 'page-profile', 'as' => 'page-profile-events'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	}
	if(plugin_loaded('blog')) {
		register_pager("{slug}/blogs", array('use' => "page::profile@page_blogs_profile_pager", 'filter' => 'page-profile', 'as' => 'page-profile-blogs'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	}
	if(plugin_loaded('music')) {
		register_pager("{slug}/musics", array('use' => "page::profile@page_musics_profile_pager", 'filter' => 'page-profile', 'as' => 'page-profile-musics'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	}
	if(plugin_loaded('video')) {
		register_pager("{slug}/videos", array('use' => "page::profile@page_videos_profile_pager", 'filter' => 'page-profile', 'as' => 'page-profile-videos'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	}
	if(plugin_loaded('livestreaming')) {
		register_pager("{slug}/livestreams", array('use' => "page::profile@page_livestreams_profile_pager", 'filter' => 'page-profile', 'as' => 'page-profile-livestreams'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	}
});
register_post_pager("page/change/cover", array('use' => 'page::profile@upload_cover_pager', 'filter' => 'user-auth'));
register_pager("page/cover/reposition", array('use' => 'page::profile@reposition_cover_pager', 'filter' => 'user-auth'));
register_pager("page/cover/remove", array('use' => 'page::profile@remove_cover_pager', 'filter' => 'user-auth'));
register_pager("page/change/logo", array('use' => 'page::profile@change_logo_pager', 'filter' => 'user-auth'));
register_pager("page/create", array('use' => "page::page@create_page_pager", 'filter' => 'user-auth', 'as' => 'page-create'));
//register_pager("page/mine", array('use' => "page::page@my_pages_pager", 'filter' => 'user-auth', 'as' => 'page-mine'));
register_pager("pages?type=mine", array('use' => "page::page@pages_pager", 'as' => 'page-mine'));
register_pager("pages", array('use' => "page::page@pages_pager", 'as' => 'pages'));

register_pager("page/delete", array('use' => "page::page@delete_page_pager", 'filter' => 'user-auth', 'as' => 'page-delete'));

register_pager("page/more/invite", array('use' => "page::page@page_more_invite_pager", 'filter' => 'user-auth'));
register_pager("page/invite/friend", array('use' => "page::page@page_invite_pager", 'filter' => 'user-auth'));
register_pager("page/invite/search", array('use' => "page::page@page_search_invite_pager", 'filter' => 'user-auth'));

//manage pagers registration
register_pager("page/{id}/manage/general", array('use' => "page::manage@general_pager", 'filter' => 'user-auth|page-manage', 'as' => 'page-manage'))->where(array('id' => '[0-9]+'));
register_pager("page/{id}/manage/roles", array('use' => "page::manage@roles_pager", 'filter' => 'user-auth|page-manage', 'as' => 'page-roles'))->where(array('id' => '[0-9]+'));
register_pager("page/{id}/manage/social-connections", array('use' => "page::manage@social_connections_pager", 'filter' => 'user-auth|page-manage', 'as' => 'page-social-connections'))->where(array('id' => '[0-9]+'));
register_pager("page/{id}/manage/page-info", array('use' => "page::manage@page_info_pager", 'filter' => 'user-auth|page-manage', 'as' => 'page-info'))->where(array('id' => '[0-9]+'));
register_pager("page/{id}/manage/fields/{cat_id}", array('use' => "page::manage@custom_field_pager", 'filter' => 'user-auth|page-manage', 'as' => 'page-manage-fields'))->where(array('id' => '[0-9]+', 'cat_id' => '[0-9]+'));

register_pager("page/role/remove", array('use' => "page::manage@remove_role_pager", 'filter' => 'user-auth'));

//notifications display
register_hook("display.notification", function($notification) {
	if($notification['type'] == 'page.invite') {
		$page = find_page($notification['type_id']);
		if($page) return view("page::notifications/invite", array('notification' => $notification, 'page' => $page));
		delete_notification($notification['notification_id']); //ensure deletion of this notification
	} elseif($notification['type'] == 'page.new.role') {
		$page = find_page($notification['type_id']);
		$data = unserialize($notification['data']);
		$role = $data['role'];
		switch($role) {
			case 1:
				$role = lang('admin');
			break;
			case 2 :
				$role = lang('moderator');
			break;
			case 3 :
				$role = lang('editor');
			break;
		}
		if($page) return view("page::notifications/role", array('notification' => $notification, 'page' => $page, 'role' => $role));
		delete_notification($notification['notification_id']); //ensure deletion of this notification
	} elseif($notification['type'] == 'page.like') {
		return view("page::notifications/like", array('notification' => $notification, 'type' => 'like'));
	}
});


//page blocks
register_hook('admin-started', function() {
	register_block_page('page-profile', lang('page::page-profile'));
	register_block_page('pages', lang('page::pages'));

});
register_block("page::block/invite", lang('page::page-invite-friends'), null);
register_block("page::block/suggestion", lang('page::page-suggestions'), null, array(
		'limit' => array(
			'title' => lang('list-limit'),
			'description' => lang('list-limit-desc'),
			'type' => 'text',
			'value' => 6
		),)
);

register_block("page::block/photos", lang('page::page-recent-photos'), null, array(
		'limit' => array(
			'title' => lang('list-limit'),
			'description' => lang('list-limit-desc'),
			'type' => 'text',
			'value' => 6
		),)
);

register_block("page::block/likes", lang('page::user-profile-likes'), null, array(
		'limit' => array(
			'title' => lang('list-limit'),
			'description' => lang('list-limit-desc'),
			'type' => 'text',
			'value' => 6
		),)
);


register_hook('admin.statistics', function($stats) {
	$stats['pages'] = array(
		'count' => count_total_pages(),
		'title' => lang('page::pages'),
		'icon' => 'ion-ios-book-outline',
		'link' => '#',
	);
	return $stats;
});
register_hook('admin.charts', function($result, $months, $year) {
	$c = array(
		'name' => lang('page::pages'),
		'points' => array()
	);


	foreach($months as $name => $n) {
		$c['points'][$name] = count_pages_in_month($n, $year);

	}

	$result['charts']['members'][] = $c;


	return $result;
});

register_hook('user.delete', function($userid) {
	$d = db()->query("SELECT * FROM pages WHERE page_user_id='{$userid}'");
	while($page = $d->fetch_assoc()) {
		delete_page($page['page_id'], $page);
	}
});

register_hook('saved.content', function($content, $type) {
	add_menu('saved', array('title' => lang('page::pages').' <span style="color:lightgrey;font-size:12px">'.count(get_user_saved('page')).'</span>', 'link' => url('saved/pages'), 'id' => 'pages'));
	if($type == 'pages') {
		$content = view('page::saved/content', array('pages' => get_pages('saved')));
	}

	return $content;
});


register_hook('design.save', function($type, $type_id, $val) {
	if($type == 'page') {
		$page = find_page($type_id);
		if(is_page_admin($page)) {
			$details = serialize($val);
			db()->query("UPDATE pages SET design_details='{$details}' WHERE page_id='{$type_id}'");
		}
	}
});

register_pager("{id}/likes", array("use" => "page::user-profile@likes_pager", "as" => "profile-likes", 'filter' => 'profile'))
	->where(array('id' => '[a-zA-Z0-9\_\-\.]+'));

register_hook('profile.started', function($user) {
	add_menu('user-profile-more', array('title' => lang('page::likes'), 'as' => 'likes', 'link' => profile_url('likes', $user)));
});

register_hook("like.item", function($type, $typeId, $userid) {
	if($type == 'page') {
		$page = find_page($typeId);
		if($page['page_user_id'] && $page['page_user_id'] != get_userid()) {
			send_notification_privacy('notify-site-like', $page['page_user_id'], 'page.like', $typeId, $page);
		}
	}
});

register_hook('entity.data', function($entity, $type, $type_id) {
	if($type == 'page') {
		$page = find_page($type_id, false);
		$entity = array(
			'id' => $page['page_url'],
			'name' => $page['page_title'],
			'avatar' => get_page_logo(200, $page)
		);
	}
	return $entity;
});

register_hook('entity.select.list', function() {
	echo view('page::select/list');
});

register_hook('photos.get.sql', function($sql, $type, $id, $limit, $offset) {
	if($type == 'page') {
		$sql = "SELECT * FROM medias WHERE file_type = 'image' AND ((entity_type = 'page' AND entity_id = '".$id."') OR (type = 'page-posts' AND type_id = '".$id."')) ORDER BY id DESC LIMIT ".$offset.", ".$limit;
	}
	return $sql;
});

register_hook('page.updated',function ($page, $val){
    if (is_array($val)){
        $insertId = $page['page_id'];
        unset($val['category']);
        unset($val['name']);
        unset($val['description']);
        $val['page_url'] = str_replace(" ","-",$val['page_url']);
        more_social_update_page($insertId, $val);
    }
});

// Visitor role check
register_hook('page.visitors',function ($bool, $page){
    $page = find_page($page['page_id']);
    if ($page['visitor_editor'] == 1) return array(true);
    return $bool;
});

//Page role
register_hook('page.roles.updated',function ($page){
    $val = input('val');
    $role = array('visitor_editor' => $val['visitor_editor']);
    $insertId = $page['page_id'];
    more_social_update_page($insertId, $role);
});
register_hook('page.manage.middle',function ($page){
    echo view("page::manage/page-general", array('page' => $page));
});

// Page role html
register_hook('more.page.role',function ($page){
    echo view("page::manage/page-visitor-role", array('page' => $page));
});

register_hook('page.more.info',function ($page){
    echo view("page::profile/about/about", array('page' => $page));
});

register_hook('page.created', function($pageId, $val) {
    $page = find_page($pageId);
    if($page['page_user_id']) {
        add_activity($page['page_title'], null, 'page', $pageId, 1);
        add_feed(array(
            'entity_id' => $page['page_user_id'],
            'entity_type' => 'user',
            'type' => 'feed',
            'type_id' => 'page-added',
            'type_data' => $pageId,
            'page' => $pageId,
            'privacy' => 1,
            'images' => '',
            'auto_post' => true,
            'can_share' => 1
        ));
    }
});

register_hook('feed.arrange', function($feed) {
    if(is_numeric($feed['page'])) {
        $page = find_page($feed['page']);

        if($page) {
            if(($page['page_user_id'] != get_userid())) $page['status'] = 0;
            $feed['pageDetails'] = $page;
        }
    }
    return $feed;
});
register_hook("activity.title", function($title, $activity, $user) {
    switch($activity['type']) {
        case 'page':
            $pageId = $activity['type_id'];
            $page = find_page($pageId);
            if(!$page) return "invalid";
            $link = $page['page_title'];
            $owner = find_user($page['page_user_id']);
            $owner['link'] = url($owner['username']);
            $ownerName = get_user_name($owner);
            return activity_form_link($owner['link'], $ownerName, true)." ".lang("activity::added-new")." ".activity_form_link($activity['link'], lang('page::page'), true, true);
            break;
    }
    return $title;
});

register_hook('feed-title', function($feed) {
    if($feed['type_id'] == "page-added") {
        echo lang('page::added-page');
    }
});

register_hook('feed.custom.column.sql', function ($columns, $val){
    if (isset($val['page'])){
        $columns[0] = !($columns) ? '' : ',page';
    }
    return $columns;
});

register_hook('feed.custom.value.sql', function ($value, $val){
    if (isset($val['page'])){
        $value[0] = !($value) ? '' : ",'{$val['page']}'";
    }
    return $value;
});

register_hook('feeds.query.fields', function ($fields, $more = null){
    $fields .=",page";
    return $fields;
});
register_hook('feed.post.plugins.hook', function($feed) {
    if ($feed['page']){
        $page = find_page($feed['page']);
        echo view("page::feed-content", array('page'=> $page));
    }
});


//pages you may like
register_hook('feed.lists.inline', function($index) {
    if (config('enable-inline-pages-suggestion', true) ) {
        $index = $index + 1;
        if ($index == config('pages-render-after-post-number', 10)) {
            echo view("page::inline-page");
        }
    }
});