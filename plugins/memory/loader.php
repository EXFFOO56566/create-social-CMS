<?php
load_functions('memory::memory');
register_asset('memory::css/memory.css');
register_asset('memory::js/memory.js');
register_pager('onthisday', array('use' => 'memory::memory@onthisday_pager', 'as' => 'on-this-day', 'filter' => 'user-auth'));
register_pager('memory/feed/share', array('use' => 'memory::memory@feed_share_pager', 'as' => 'memory-feed-share', 'filter' => 'user-auth'));
register_hook('feed.lists.inline', function($index) {
	$index = $index + 1;
	if($index == config('render-memory-after-post-number', 1)) {
		$memories = memory_get_memories(get_userid(), 2, true);
		echo view('memory::post', array('memories' => $memories));
	}
});

register_hook('memory.add', function($memories) {
	foreach($memories as $memory) {
		add_feed(array(
			'entity_id' => $memory['user_id'],
			'entity_type' => 'user',
			'type' => 'feed',
			'type_id' => 'memory',
			'type_data' => $memory['id'],
			'privacy' => 3,
			'images' => '',
			'auto_post' => true,
			'can_share' => 1
		));
	}
});

register_hook('system.started', function($app) {
	if(is_loggedIn() && !memory_added()) {
		$memories = memory_add();
		fire_hook('memory.add', null, array($memories));
	}
});


register_hook('feed.post.plugins.hook', function($feed) {
	if($feed['type_id'] == 'memory') {
		$memory = memory_get($feed['type_data']);
		$shared = true;
		if($memory) {
			$feed = memory_get_feed($memory['id']);
			if(date('Y', strtotime($memory['date'])) == date('Y')) {
				return view('memory::feed-content', array('memory' => $memory, 'feed' => $feed, 'shared' => $shared));
			}
		}
	}
});

register_hook('feed.get.sql.where.extend', function($sql) {
		$sql .= " AND (type_id != 'memory' OR (type_id = 'memory' AND DAY(timestamp) = DAY(NOW()) AND privacy != 3))";
		return $sql;
});

register_hook('feed-title', function($feed) {
	if($feed['type_id'] == 'memory') {
		echo lang('memory::memory');
	}
});

register_hook('user.delete', function($user_id) {
	$db = db();
	$db->query("DELETE FROM memories WHERE user_id = '".$user_id."'");
	return $user_id;
});