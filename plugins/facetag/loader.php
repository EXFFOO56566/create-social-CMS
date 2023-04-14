<?php

load_functions('facetag::facetag');

register_asset('facetag::css/facetag.css');

register_asset('facetag::js/tracking-min.js');
register_asset('facetag::js/face-min.js');
register_asset('facetag::js/facetag.js');

register_pager('facetag/ajax/{action}', array(
	'use' => 'facetag::ajax@ajax_page',
	'as' => 'facetag-ajax-page',
))->where(array('action' => '.*?'));

register_hook('display.notification', function($notification) {
	if($notification['type'] == 'facetag') {
		$tag = perfectUnserialize($notification['data']);
		$photo = find_photo($tag['photo_id']);
		return view('facetag::notifications/facetag', array('notification' => $notification, 'photo' => $photo));
	}
});

register_hook('user.delete', function($user_id) {
	$tags = facetag_get_tags_by_user($user_id);
	foreach($tags as $tag) {
		facetag_remove_tag($tag['id']);
	}
	$tags = facetag_get_tags_by_tagged_user($user_id);
	foreach($tags as $tag) {
		facetag_remove_tag($tag['id']);
	}
});

register_hook('photo.deleted', function($photo_id) {
	$tags = facetag_get_tags($photo_id);
	foreach($tags as $tag) {
		facetag_remove_tag($tag['id']);
	}
});

register_hook('facetag.tag.added', function($tag_id) {
	facetag_delete_notifications($tag_id);
	$tag = facetag_get_tag($tag_id);
	if($tag['tagged_user_id'] != get_userid()) {
		send_notification($tag['tagged_user_id'], 'facetag', $tag_id, $tag);
	}
});

register_hook('facetag.tag.updated', function($tag_id) {
	facetag_delete_notifications($tag_id);
	$tag = facetag_get_tag($tag_id);
	if($tag['tagged_user_id'] != get_userid()) {
		send_notification($tag['tagged_user_id'], 'facetag', $tag_id, $tag);
	}
});

register_hook('facetag.tag.removed', function($tag_id) {
	facetag_delete_notifications($tag_id);
});

register_hook("after-render-js", function($html) {
	$html .= view('facetag::image');
	return $html;
});

register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => lang('facetag::face-tag-permissions'),
		'description' => '',
		'roles' => array(
			'can-tag-photos' => array('title' => lang('facetag::can-tag-photos'), 'value' => 1),
		)
	);
	return $roles;
});
