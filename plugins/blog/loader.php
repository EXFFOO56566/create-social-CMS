<?php
load_functions("blog::blog");
register_asset("blog::css/blog.css");
register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => lang('blog::blog-permissions'),
		'description' => '',
		'roles' => array(
			'can-create-blog' => array('title' => lang('blog::can-create-blog'), 'value' => 1),

		)
	);
	return $roles;
});
register_pager("blogs", array('use' => 'blog::blog@blog_pager', 'as' => 'blogs'));
register_pager("blog/add", array('use' => 'blog::blog@add_blog_pager', 'filter' => 'auth', 'as' => 'blog-add'));
register_pager("blog/manage", array('use' => 'blog::blog@manage_pager', 'as' => 'blog-manage', 'filter' => 'auth'));
register_pager("blogs/api", array('use' => 'blog::blog@blog_api_pager'));
register_pager("blog/{slugs}", array('use' => 'blog::blog@blog_page_pager', 'as' => 'blog-page'))->where(array('slugs' => '[a-zA-Z0-9\-\_\.]+'));


register_pager("admincp/blogs", array('use' => "blog::admincp@lists_pager", 'filter' => 'admin-auth', 'as' => 'admincp-blogs'));
register_pager("admincp/blog/add", array('use' => "blog::admincp@add_pager", 'filter' => 'admin-auth', 'as' => 'admincp-blog-add'));
register_pager("admincp/blog/manage", array('use' => "blog::admincp@manage_pager", 'filter' => 'admin-auth', 'as' => 'admincp-blog-manage'));
register_pager("admincp/blog/categories", array('use' => "blog::admincp@categories_pager", 'filter' => 'admin-auth', 'as' => 'admincp-blog-categories'));
register_pager("admincp/blog/categories/add", array('use' => "blog::admincp@categories_add_pager", 'filter' => 'admin-auth', 'as' => 'admincp-blog-categories-add'));
register_pager("admincp/blog/category/manage", array('use' => "blog::admincp@manage_category_pager", 'filter' => 'admin-auth', 'as' => 'admincp-blog-manage-category'));


register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
		register_asset("help::css/help.css");
		register_asset("help::js/help.js");
	}
});

register_hook("admin-started", function() {

	add_menu("admin-menu", array('icon' => 'ion-document-text', "id" => "admin-blogs", "title" => lang('blog::manage-blogs'), "link" => '#'));
	get_menu("admin-menu", "plugins")->addMenu(lang('blog::blogs-manager'), '#', 'admin-blogs');
	get_menu("admin-menu", "plugins")->findMenu('admin-blogs')->addMenu(lang('blog::lists'), url_to_pager("admincp-blogs"), "manage");
	get_menu("admin-menu", "plugins")->findMenu('admin-blogs')->addMenu(lang('blog::add-new-blog'), url_to_pager("admincp-blog-add"), "add");
	get_menu("admin-menu", "plugins")->findMenu('admin-blogs')->addMenu(lang('blog::manage-categories'), url_to_pager("admincp-blog-categories"), "categories");

});

register_hook('admin.statistics', function($stats) {
	$stats['blogs'] = array(
		'count' => count_total_blogs(),
		'title' => lang('blog::blogs'),
		'icon' => 'ion-document-text',
		'link' => url_to_pager('admincp-blogs'),
	);
	return $stats;
});

register_hook("comment.add", function($type, $typeId, $text) {
	if($type == 'blog') {
		$blog = get_blog($typeId);
		$subscribers = get_subscribers($type, $typeId);
		foreach($subscribers as $userid) {
			if($userid != get_userid()) {
				send_notification_privacy('notify-site-comment', $userid, 'blog.comment', $typeId, $blog, null, $text);
			}
		}

	}
});

register_hook("like.item", function($type, $typeId, $userid) {
	if($type == 'blog') {
		$blog = get_blog($typeId);
		if($blog['user_id'] and $blog['user_id'] != get_userid()) {
			send_notification_privacy('notify-site-like', $blog['user_id'], 'blog.like', $typeId, $blog);
		}
	} elseif($type == 'comment') {
		$comment = find_comment($typeId, false);
		if($comment and $comment['user_id'] != get_userid()) {
			if($comment['type'] == 'blog') {
				$blog = get_blog($comment['type_id']);
				send_notification_privacy('notify-site-like', $comment['user_id'], 'blog.like.comment', $comment['type_id'], $blog);
			}
		}
	}
});

register_hook("comment.add", function($type, $typeId, $text) {
	if($type == 'blog') {
		$blog = get_blog($typeId);
		$subscribers = get_subscribers($type, $typeId);
		if(!in_array($blog['user_id'], $subscribers)) {
			$subscribers[] = $blog['user_id'];
		}
		foreach($subscribers as $userid) {
			if($userid != get_userid()) {
				send_notification_privacy('notify-site-comment', $userid, 'blog.comment', $typeId, $blog, null, $text);
			}
		}

	}
});

register_hook("display.notification", function($notification) {
	if($notification['type'] == 'blog.like') {
		return view("blog::notifications/like", array('notification' => $notification, 'blog' => unserialize($notification['data'])));
		delete_notification($notification['notification_id']);
	} elseif($notification['type'] == 'blog.like.comment') {
		return view("blog::notifications/like-comment", array('notification' => $notification, 'blog' => unserialize($notification['data'])));
		delete_notification($notification['notification_id']);
	} elseif($notification['type'] == 'blog.comment') {
		return view("blog::notifications/comment", array('notification' => $notification, 'blog' => unserialize($notification['data'])));
		delete_notification($notification['notification_id']);
	}
});

add_menu_location('blogs-menu', lang('blog::blogs-menu'));
add_available_menu('blog::blogs', 'blogs', 'ion-android-clipboard');

register_pager("{id}/blogs", array("use" => "blog::user-profile@blogs_pager", "as" => "profile-blogs", 'filter' => 'profile'))
	->where(array('id' => '[a-zA-Z0-9\_\-\.]+'));


register_hook('profile.started', function($user) {
	add_menu('user-profile-more', array('title' => lang('blog::blogs'), 'as' => 'blogs', 'link' => profile_url('blogs', $user)));
});

register_block("blog::block/profile-recent", lang('blog::user-profile-recent-blogs'), null, array(
		'limit' => array(
			'title' => lang('list-limit'),
			'description' => lang('list-limit-desc'),
			'type' => 'text',
			'value' => 6
		),)
);

register_block("blog::block/latest", lang('blog::latest-blogs'), null, array(
		'limit' => array(
			'title' => lang('list-limit'),
			'description' => lang('list-limit-desc'),
			'type' => 'text',
			'value' => 6
		),)
);


//page blocks
register_hook('admin-started', function() {
	register_block_page('blogs', lang('blog::blogs'));

});

register_hook('user.delete', function($userid) {
	$d = db()->query("SELECT * FROM blogs WHERE user_id='{$userid}'");
	while($blog = $d->fetch_assoc()) {
		delete_blog($blog['id']);
	}
});

register_hook('uid.check', function($result, $value, $type = null, $type_id = null) {
	if(!$type || $type == 'blog') {
		$blog = get_blog($value);
		if($blog) {
			if(!$type_id || ($type_id && $type_id != $blog['id'])) {
				$result[0] = false;
			}
		}
	}
	return $result;
});

register_hook('blog.added', function($blogId, $blog) {
    if($blog['entity_type'] == 'user' and $blog['status']) {
        add_activity($blog['slug'], null, 'blog', $blogId, $blog['privacy']);
        add_feed(array(
            'entity_id' => $blog['entity_id'],
            'entity_type' => $blog['entity_type'],
            'type' => 'feed',
            'type_id' => 'blog-added',
            'type_data' => $blog['id'],
            'blog' => $blogId,
            'privacy' => $blog['privacy'],
            'images' => '',
            'auto_post' => true,
            'can_share' => 1
        ));
    }
});

register_hook('feed.arrange', function($feed) {
    if(is_numeric($feed['blog'])) {
        $blog = get_blog($feed['blog']);

        if($blog) {
            if($blog['status'] == 0 and ($blog['user_id'] != get_userid())) $feed['status'] = 0;
            $feed['blogDetails'] = $blog;
        }
    }
    return $feed;
});
register_hook("activity.title", function($title, $activity, $user) {
    switch($activity['type']) {
        case 'blog':
            $blogId = $activity['type_id'];
            $blog = get_blog($blogId);
            if(!$blog) return "invalid";
            $link = $blog['slug'];
            $owner = get_blog_publisher($blog);
            $owner['link'] = url($owner['id']);
            return activity_form_link($owner['link'], $owner['name'], true)." ".lang("activity::added-new")." ".activity_form_link($activity['link'], lang('blog::blog'), true, true);
            break;
    }
    return $title;
});

register_hook('feed-title', function($feed) {
    if($feed['type_id'] == "blog-added") {
        echo lang('blog::added-blog');
    }
});

register_hook('feed.custom.column.sql', function ($columns, $val){
    if (isset($val['blog'])){
        $columns[0] = !($columns) ? '' : ',blog';
    }
    return $columns;
});

register_hook('feed.custom.value.sql', function ($value, $val){
    if (isset($val['blog'])){
        $value[0] = !($value) ? '' : ",'{$val['blog']}'";
    }
    return $value;
});

register_hook('feeds.query.fields', function ($fields, $more = null){
    $fields .=",blog";
   return $fields;
});
register_hook('feed.post.plugins.hook', function($feed) {
    if ($feed['blog']){
        $blog = get_blog($feed['blog']);
        if ($blog) echo view("blog::feed-content", array('blog'=> $blog));
    }
});