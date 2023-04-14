<?php
load_functions('event::event');
register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
		register_asset("event::css/event.css");
		register_asset("event::css/jcarousel.css");
		register_asset("event::js/event.js");
		register_asset("event::js/jcarousel.js");
	}
});

register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => 'Event Permissions',
		'description' => '',
		'roles' => array(
			'can-create-event' => array('title' => lang('event::can-create-event'), 'value' => 1),
		)
	);
	return $roles;
});


register_hook('feeds.query', function($type, $type_id) {
	if($type == 'event') {
		$sql_fields = get_feed_fields();
		$sql = "SELECT ".$sql_fields." FROM `feeds` WHERE ";
		$sql .= " ((type = 'event' AND type_id = '".$type_id."') OR (entity_type = 'event' AND entity_id = '".$type_id."'))";
		$pinned_posts = get_pinned_feeds();
		$pinned_posts[] = 0;
		$pinned_posts = implode(',', $pinned_posts);
		$sql .= " AND feed_id NOT IN (".$pinned_posts.")";
		return $sql;
	}
});

register_hook('feed.edit.privacy.check', function($result, $feed) {
	if($feed['type'] == 'event') {
		$result['edit'] = false;
	}
	return $result;
});

register_hook('feed.edit.check', function($result, $feed) {
	if($feed['type'] == 'event') {
		$event = (isset($feed['event'])) ? $feed['event'] : find_event($feed['type_id']);
		$feed['event'] = $event;
		if(is_event_admin($event)) $result['edit'] = true;
	}
	return $result;
});


register_hook('feed.pin.check', function($result, $feed) {
	if($feed['type'] == 'event') {
		$event = (isset($feed['event'])) ? $feed['event'] : find_event($feed['type_id']);
		$feed['event'] = $event;
		if(is_event_admin($event)) $result['edit'] = true;
	}
	return $result;
});
register_hook('feed-title', function($feed) {
	if($feed['type'] == 'event' and !isset(app()->profileEvent)) {
		$event = find_event($feed['type_id'], true);
		echo "<i class='ion-arrow-right-c'></i> "."<a ajax='true' href='".event_url(null, $event)."'>".$event['event_title']."</a>";
	}
});

register_hook('feed.added', function($feedId, $val) {
	if($val['type'] == 'event') {
		$event = find_event($val['type_id']);
		if($event['user_id'] != get_userid()) send_notification($event['user_id'], 'event.post', $event['event_id']);
	}
});

register_hook('search-dropdown-start', function($content, $term) {
	$events = get_events('search', $term, 5);
	if($events->total) {
		$content .= view('event::search/dropdown', array('events' => $events));
	}
	return $content;
});

register_hook('register-search-menu', function($term) {
	add_menu("search-menu", array('title' => lang('event::events'), 'id' => 'event', 'link' => form_search_link('event', $term)));
});

register_hook('search-result', function($content, $term, $type) {
	if($type == 'event') {
		get_menu('search-menu', 'event')->setActive();
		$content = view('event::browse', array('events' => get_events('search', $term)));
	}
	return $content;
});

register_filter("event-profile", function($app) {
	$slug = segment(1);

	$event = find_event($slug);

	if(!$event) return false;

	if($event['privacy'] == 6) {
		if(!is_event_admin($event) and !event_already_invited($slug, get_userid())) return false;
	}

	$app->profileEvent = $event;

	fire_hook('event.profile.started', null, array($event));

	return true;
});

register_hook("admin-started", function() {
	add_menu("admin-menu", array("id" => "events-manager", "title" => lang("event::events-manager"), "link" => "#", "icon" => "ion-android-calendar"));
	get_menu("admin-menu", "plugins")->addMenu(lang("event::events-manager"), '#', 'events-manager');
	get_menu("admin-menu", "plugins")->findMenu("events-manager")->addMenu(lang("event::events"), url_to_pager('admin-event-lists'));
	get_menu("admin-menu", "plugins")->findMenu("events-manager")->addMenu(lang("event::categories"), url_to_pager("admin-event-categories"), 'list');
	get_menu("admin-menu", "plugins")->findMenu("events-manager")->addMenu(lang("event::add-category"), url_to_pager("admin-event-category-add"), 'add-category');

	/**get_menu("admin-menu", "events-manager")->addMenu(lang("event::run-notifications"), '#', 'admin-events-notification');
	 * get_menu("admin-menu", "events-manager")->findMenu("admin-events-notification")->addMenu(lang("event::today-events"), url_to_pager("event-run").'?type=event&web=true', 'events');
	 * get_menu("admin-menu", "events-manager")->findMenu("admin-events-notification")->addMenu(lang("event::remind-events"), url_to_pager("event-run").'?type=event&when=before&web=true', 'remind-events');
	 * get_menu("admin-menu", "events-manager")->findMenu("admin-events-notification")->addMenu(lang("event::remind-birthdays"), url_to_pager("event-run").'?type=birthday&when=before&web=true', 'remind-birthday');
	 * get_menu("admin-menu", "events-manager")->findMenu("admin-events-notification")->addMenu(lang("event::today-birthdays"), url_to_pager("event-run").'?type=birthday&web=true', 'today');
	 * //get_menu("admin-menu", "events-manager")->addMenu(lang("settings"), url('admincp/plugin/settings/game'), "admin-game-settings");
	 **/
	register_block_page('events', lang('event::events-pages'));
});

register_pager("admincp/event/action/batch", array('use' => "event::admincp@event_action_batch_pager", 'filter' => 'admin-auth', 'as' => 'admin-event-batch-action'));


register_pager("admincp/events", array('use' => "event::admincp@lists_pager", 'filter' => 'admin-auth', 'as' => 'admin-event-lists'));
register_pager("admincp/event/categories", array('use' => "event::admincp@categories_pager", 'filter' => 'admin-auth', 'as' => 'admin-event-categories'));
register_pager("admincp/event/category/add", array('use' => "event::admincp@add_category_pager", 'filter' => 'admin-auth', 'as' => 'admin-event-category-add'));
register_pager("admincp/event/category/manage", array('use' => "event::admincp@manage_category_pager", 'filter' => 'admin-auth', 'as' => 'admin-event-manage-category'));


register_post_pager("event/change/cover", array('use' => 'event::profile@upload_cover_pager', 'filter' => 'user-auth'));
register_pager("event/cover/reposition", array('use' => 'event::profile@reposition_cover_pager', 'filter' => 'user-auth'));
register_pager("event/cover/remove", array('use' => 'event::profile@remove_cover_pager', 'filter' => 'user-auth'));

if(is_loggedIn()) {
	if(user_has_permission('can-create-event')) register_pager("event/create", array('use' => "event::event@create_event_pager", 'filter' => 'user-auth', 'as' => 'event-create'));
}

register_pager("event/invite/user", array('use' => "event::profile@invite_user_pager", 'filter' => 'user-auth'));
register_pager("event/invite/search", array('use' => "event::profile@search_invite_user_pager", 'filter' => 'user-auth'));
register_pager("event/rsvp", array('use' => "event::profile@rsvp_pager", 'filter' => 'user-auth'));
register_pager("events", array('use' => "event::event@events_pager", 'as' => 'events'));
register_pager('event/calender/{slug}', array('use' => "event::event@event_calender_pager", 'as' => 'events-calender'))->where(array('slug' => '[a-zA-Z0-9\_\-]+'));
register_pager("event/delete/{id}", array('use' => "event::event@event_delete_pager", 'as' => 'event-delete', 'filter' => 'user-auth'))->where(array('id' => '[0-9]+'));
register_pager("events/run", array('use' => "event::event@events_run_pager", 'as' => 'event-run'));
register_pager("event/about/discussion", array('use' => "event::profile@event_about_discussion", 'as' => 'event_about_discussion'));

//frontend pager registration
register_hook('system.started', function() {
	register_pager("event/{slug}", array('use' => "event::profile@event_profile_pager", 'filter' => 'event-profile', 'as' => 'event-profile'))->where(array('slug' => '[a-zA-Z0-9\_\-]+'));
	//register_pager("event/{slug}/play", array('use' => "game::profile@game_play_profile_pager", 'filter' => 'game-profile', 'as' => 'game-profile-play'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
	register_pager("event/{slug}/edit", array('use' => "event::profile@event_edit_profile_pager", 'filter' => 'event-profile', 'as' => 'event-profile-edit'))->where(array('slug' => '[a-zA-Z0-9\_\-]+'));
	//register_pager("{slug}/photos", array('use' => "page::profile@page_photos_profile_pager", 'filter' => 'page-profile', 'as' => 'page-profile-photos'))->where(array('slug' => '[a-zA-Z0-9\_\-\.]+'));
});

if(is_loggedIn()) {
	//add_menu("dashboard-main-menu", array("icon" => "<i class='ion-android-calendar'></i>", "id" => "events", "title" => lang("event::events"), "link" => url("events")));
	//add_menu("dashboard-menu", array("icon" => "<i class='ion-android-calendar'></i>", "id" => "events", "title" => lang("event::manage-events"), "link" => url('events')));
}

register_hook("display.notification", function($notification) {
	if($notification['type'] == 'event.rsvp') {
		$event = find_event($notification['type_id']);
		if($event) {
			$data = unserialize($notification['data']);
			return view("event::notifications/rsvp", array('notification' => $notification, 'event' => $event, 'rsvp' => $data['rsvp']));
		} else {
			delete_notification($notification['notification_id']);
		}

	} elseif($notification['type'] == 'event.events') {
		$event = find_event($notification['type_id']);
		if($event) {
			$data = unserialize($notification['data']);
			return view("event::notifications/events", array('notification' => $notification, 'event' => $event, 'when' => $data['when']));
		} else {
			delete_notification($notification['notification_id']);
		}
	} elseif($notification['type'] == 'event.invite') {
		$event = find_event($notification['type_id']);
		if($event) {
			$data = unserialize($notification['data']);
			return view("event::notifications/invite", array('notification' => $notification, 'event' => $event, 'event' => $data['event']));
		} else {
			delete_notification($notification['notification_id']);
		}
	} elseif($notification['type'] == 'event.birthday') {
		return view("event::notifications/birthday", array('notification' => $notification, 'when' => $notification['type_id']));
	} elseif($notification['type'] == 'event.post') {
		$event = find_event($notification['type_id']);
		if($event) {
			return view("event::notifications/post", array('notification' => $notification, 'event' => $event));
		} else {
			delete_notification($notification['notification_id']);
		}
	}
});

register_hook('admin.statistics', function($stats) {
	$stats['events'] = array(
		'count' => count_total_events(),
		'title' => lang('event::events'),
		'icon' => 'ion-android-calendar',
		'link' => url_to_pager('admin-event-lists'),
	);
	return $stats;
});


register_block("event::block/birthdays", lang('event::birthdays-coming-up'), null, array(
		'limit' => array(
			'title' => lang('list-limit'),
			'description' => lang('list-limit-desc'),
			'type' => 'text',
			'value' => 6
		),)
);

register_hook('user.delete', function($userid) {
	$d = db()->query("SELECT * FROM events WHERE user_id='{$userid}'");
	while($event = $d->fetch_assoc()) {
		delete_event($event);
	}
});

register_hook('saved.content', function($content, $type) {
	add_menu('saved', array('title' => lang('event::events').' <span style="color:lightgrey;font-size:12px">'.count(get_user_saved('event')).'</span>', 'link' => url('saved/events'), 'id' => 'events'));
	if($type == 'events') {
		$content = view('event::saved/content', array('events' => get_events('saved')));
	}

	return $content;
});

register_hook('privacy', function($privacy) {
	$privacy['6'] = array(
		'icon' => 'ion-android-mail',
		'title' => lang('private'),
		'type' => 'event'
	);
	return $privacy;
});

register_hook('privacy.sql', function($sql) {
	$events = array_merge(get_invited_events(), get_user_events(), array(0));
	$sql .= " OR ((entity_type = 'event' OR privacy = 6) AND entity_id IN (".implode(',', $events)."))";
	return $sql;
});

add_available_menu('event::events', 'events', 'ion-android-calendar');

register_hook('photo.get.publisher', function($photo) {
	if(in_array($photo['type'], array('event', 'event-posts', 'event-logo'))) {
		$user = find_user($photo['type_id'], false);
		if($user) {
			$publisher = $user;
			$publisher['avatar'] = get_avatar(75, $user);
			$publisher['url'] = profile_url(null, $user);
			return $publisher;
		}
	}
});
register_hook('event.create', function($eventId) {
    $event = find_event($eventId);
    if($event['entity_type'] == 'user' and $event['privacy']) {
        add_activity('event/'.$event['event_id'], null, 'blog', $eventId, $event['privacy']);
        add_feed(array(
            'entity_id' => $event['entity_id'],
            'entity_type' => $event['entity_type'],
            'type' => 'feed',
            'type_id' => 'event-added',
            'type_data' => $event['event_id'],
            'event' => $eventId,
            'privacy' => $event['privacy'],
            'images' => '',
            'auto_post' => true,
            'can_share' => 1
        ));
    }
});

register_hook('feed.arrange', function($feed) {
    if(is_numeric($feed['event'])) {
        $event = find_event($feed['event']);

        if($event) {
            if(($event['user_id'] != get_userid())) $feed['status'] = 0;
            $feed['eventDetails'] = $event;
        }
    }
    return $feed;
});
register_hook("activity.title", function($title, $activity, $user) {
    switch($activity['type']) {
        case 'event':
            $eventId = $activity['type_id'];
            $event = arrange_event(find_event($eventId));
            if(!$event) return "invalid";
            $link = 'event/'.$event['event_id'];
            $owner = $event['host'];
            $owner['link'] = url($owner['id']);
            return activity_form_link($owner['link'], $owner['name'], true)." ".lang("activity::added-new")." ".activity_form_link($activity['link'], lang('event::event'), true, true);
            break;
    }
    return $title;
});

register_hook('feed-title', function($feed) {
    if($feed['type_id'] == "event-added") {
        echo lang('event::added-event');
    }
});

register_hook('feed.custom.column.sql', function ($columns, $val){
    if (isset($val['event'])){
        $columns[0] = !isset($columns) ? '' : ',event';
    }
    return $columns;
});

register_hook('feed.custom.value.sql', function ($value, $val){
    if (isset($val['event'])){
        $value[0] = !isset($value) ? '' : ",'{$val['event']}'";
    }
    return $value;
});

register_hook('feeds.query.fields', function ($fields, $more = null){
    $fields .=",event";
    return $fields;
});
register_hook('feed.post.plugins.hook', function($feed) {
    if ($feed['event']){
        $event = find_event($feed['event']);
        echo view("event::feed-content", array('event'=> $event));
    }
});