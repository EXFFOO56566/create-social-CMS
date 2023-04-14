<?php
load_functions('chat::chat');
register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => lang('chat::chat-permissions'),
		'description' => '',
		'roles' => array(
			'can-send-message' => array('title' => lang('can-send-message'), 'value' => 1),
		)
	);
	return $roles;
});

register_pager("messages", array('as' => 'messages', 'use' => 'chat::message@messages_pager', 'filter' => 'auth'));
register_pager('chat/friends/online', array('as' => 'chat-friends-online', 'use' => 'chat::message@chat_friends_online'));

if(user_has_permission('can-send-message')) {
	register_hook('system.started', function($app) {
		if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
			register_asset("chat::css/chat.css");
			register_asset("chat::js/chat.js");
		}
	});

	register_hook("user.profile.buttons", function($user) {
		//echo view('chat::button', array('user' => $user));
	});

	register_hook('ajax.push.result', function($pushes, $user_id = null) {
		$user_id = isset($user_id) ? $user_id : get_userid();
		$count = count_unread_messages(null, $user_id);
		$pushes['types']['unread'] = $count;
		$users = chat_get_onlines($user_id);
		$countOnline = count($users);
		$users = array_merge($users, get_few_offlines($user_id));
		$pushes['types']['onlines'] = view('chat::load-onlines', array('users' => $users));
		$pushes['types']['count-onlines'] = $countOnline;
		$pushes['types']['chat-opened'] = get_cache('user-chat-opened-'.$user_id, array());
		$cache_name = 'typing-'.$user_id;
		$result = array();
		if(cache_exists($cache_name)) $result = get_cache($cache_name);
		$pushes['types']['chat-typing'] = array('now' => time(), 'cid' => $result, 'typing' => lang('chat::typing'), 'img' => img('images/typing.gif'));
		$cache_name = 'message-waiting-'.$user_id;
		$result = array();
		if(cache_exists($cache_name)) $result = get_cache($cache_name);
		$seen = array();
		if(is_array($result)) {
			foreach($result as $cid => $detail) {
				if(has_read_message($detail[0], $detail[1]) and !isset($detail[2])) {
					$seen[$cid] = $detail[0];
					$result[$cid][2] = 'seen';
				}
			}
		}
		set_cacheForever($cache_name, $result);
		$pushes['types']['chat-seen'] = $seen;
		return $pushes;
	});

	register_hook('footer', function() {
		if(is_loggedIn() and !isMobile()) echo view('chat::footer');
	});

	register_hook("privacy-settings", function() {
		echo view('chat::privacy');
	});
	register_pager("chat/send", array('as' => 'chat-send', 'use' => 'chat::message@chat_send_pager', 'filter' => 'auth'));
	register_pager("chat/load/messages", array('use' => 'chat::message@chat_load_messages_pager', 'filter' => 'auth'));
	register_pager("chat/load/search", array('use' => 'chat::message@chat_load_search_pager', 'filter' => 'auth'));
	register_pager("chat/load/groups", array('use' => 'chat::message@chat_load_groups_pager', 'filter' => 'auth'));
	register_pager("chat/preload", array('use' => 'chat::message@chat_preload_pager', 'filter' => 'auth'));
	register_pager("chat/typing", array('use' => 'chat::message@chat_typing_pager', 'filter' => 'auth'));
	register_pager("chat/remove/typing", array('use' => 'chat::message@chat_remove_typing_pager', 'filter' => 'auth'));
	register_pager("chat/mark/read", array('use' => 'chat::message@chat_mark_read_pager', 'filter' => 'auth'));
	register_pager("chat/load/dropdown", array('use' => 'chat::message@chat_load_dropdown_pager', 'filter' => 'auth'));
	register_pager("chat/register/open", array('use' => 'chat::message@chat_register_opened_pager', 'filter' => 'auth'));
	register_pager("chat/get/conversations", array('use' => 'chat::message@chat_get_conversations_pager', 'filter' => 'auth'));
	register_pager("chat/set/status", array('use' => 'chat::message@chat_set_status_pager', 'filter' => 'auth'));
	register_pager("chat/paginate", array('use' => 'chat::message@chat_paginate_pager', 'filter' => 'auth'));
	register_pager("chat/delete/conversation", array('use' => 'chat::message@chat_delete_conversation_pager', 'filter' => 'auth'));
	register_pager("chat/delete/message", array('use' => 'chat::message@chat_delete_message_pager', 'filter' => 'auth'));
	register_pager("chat/update/send/privacy", array('use' => 'chat::message@chat_update_send_privacy_pager', 'filter' => 'auth'));
	register_pager("mobile/online", array('as' => 'mobile-online', 'use' => 'chat::message@mobile_online_pager', 'filter' => 'auth'));
	register_pager("chat/download", array('as' => 'chat-download', 'use' => 'chat::message@chat_download_pager'));
}

register_hook('user.delete', function($userid) {
	db()->query("DELETE FROM conversation_messages WHERE sender='{$userid}'");
	db()->query("DELETE FROM conversation_members WHERE user_id='{$userid}'");
});

register_hook('plugin.loaded.result', function($result, $plugin = null) {
	if($plugin == 'chat') {
		$result['result'] = user_has_permission('can-send-message') ? $result['result'] : false;
	}
	return $result;
});

register_hook('push.notification', function($result, $type, $detail) {
	if($type == 'unread') {
		$conversation_id = $detail;
		$conversation = get_conversation($conversation_id, true);
		$from_user = find_user($conversation['last_sender']);
		$result['options']['title'] = lang('chat::message-from', array('name' => get_user_name($from_user)));
		$result['options']['body'] = strip_tags(str_limit($conversation['last_message'], 200));
		$result['options']['link'] = url_to_pager('messages');
		$result['options']['icon'] = get_avatar('200', $from_user);
		$result['status'] = true;
	} elseif($type == 'chat') {
		foreach($detail as $message) {
			$sender_id = $message['user'];
			$sender = find_user($sender_id);
			$message_id = $message['id'];
			$message = get_chat_message($message_id);
			$result['options']['title'] = lang('chat::message-from', array('name' => get_user_name($sender)));
			$result['options']['body'] = strip_tags(str_limit($message['message'], 200));
			$result['options']['link'] = url_to_pager('messages');
			$result['options']['icon'] = get_avatar('200', $sender);
			$result['status'] = true;
		}
	}
	return $result;
});