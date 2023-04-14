<?php
load_functions("comment::comment");
register_post_pager("comment/add", array('use' => 'comment::comment@comment_add_pager', 'filter' => 'auth'));
register_get_pager('comment/delete', array('use' => 'comment::comment@comment_delete_pager', 'filter' => 'auth'));
register_get_pager('comment/more', array('use' => 'comment::comment@comment_more_pager'));
register_post_pager('comment/save', array('use' => 'comment::comment@comment_save_pager', 'filter' => 'auth'));
register_get_pager('comment/load/replies', array('use' => 'comment::comment@load_replies_pager'));

register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
		register_asset("comment::css/comment.css");
		register_asset("comment::js/comment.js");
	}
});

register_hook("comment.add", function($type, $typeId, $text) {
	if($type == 'comment') {
		$comment = find_comment($typeId);
		fire_hook('reply.add', null, array($typeId, $comment['type'], $comment['type_id'], $text));
	}
});

register_hook('admin.statistics', function($stats) {
	$stats['comments'] = array(
		'count' => count_total_comments(),
		'title' => lang('comment::comments'),
		'icon' => 'ion-android-chat',
		'link' => 'javascript::void(0)',
	);
	return $stats;
});

register_hook('user.delete', function($userid) {
	$d = db()->query("SELECT * FROM comments WHERE user_id='{$userid}'");
	while($comment = $d->fetch_assoc()) {
		do_delete_comment($comment);
	}

	db()->query("DELETE FROM comments WHERE user_id='{$userid}'");
});

register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => 'Comment Permissions',
		'description' => '',
		'roles' => array(
			'can-post-comment' => array('title' => lang('feed::can-post-comment'), 'value' => 1),
		)
	);
	return $roles;
});

register_hook('app.view.result', function($result, $view, $param) {
	$entity_type = isset($param['entity_type']) ? $param['entity_type'] : 'user';
	$entity_id = isset($param['entity_id']) ? $param['entity_id'] : get_userid();
	if($view == 'comment::editor' && !can_post_comment($entity_type, $entity_id)) {
		$result = '';
	}
	return $result;
});

register_hook('can.post.comment', function($result, $type, $typeId) {
	if(!(is_loggedIn() && user_has_permission('can-post-comment'))) {
		$result['result'] = false;
	}
	return $result;
});