<?php
function messages_pager($app) {
	$cid = input('cid', 'last');
	$userid = input('userid');
	$content = "";
	$conversation = null;
	$messageContent = null;
	if($userid) {
		$theirCid = get_conversation_id(array($userid));
		$cid = ($theirCid) ? $theirCid : 'new';
	}

	if($cid == 'last') {
		$conversation = get_last_conversation();
		$cid = ($conversation) ? $conversation['cid'] : 'new';
	}
	if($cid != 'new' and $cid != 'mobile') {
		$conversation = ($conversation) ? $conversation : get_conversation($cid);
		if(!$conversation or !is_conversation_member($cid)) return redirect(url_to_pager('messages').'?cid=new');
		$messages = get_chat_messages($cid);
		$app->setTitle($conversation['title']);
		$messageContent = '';
		foreach($messages as $message) {
			$messageContent .= view('chat::display/message', array('message' => $message));
		}
	} else {
		$app->setTitle(lang('chat::new-message'));
	}

	$app->setLayout("chat::layout");
	$content = view('chat::messages', array(
		'cid' => $cid,
		'userid' => $userid,
		'conversate' => $conversation,
		'messageContent' => $messageContent
	));
	return $app->render($content);
}

function chat_send_pager($app) {
	CSRFProtection::validate(false);
	$val = input('val');
	/**
	 * @var $user
	 * @var $cid
	 * @var $message
	 */
	extract($val);
	$result = array(
		'status' => 0,
		'error' => 'Failed to send message',
	);
	if(empty($cid) and !isset($user)) return json_encode($result);
	$image = null;
	$imageFile = input_file('image');
	if($imageFile) {
		$uploader = new Uploader($imageFile);
		$path = get_userid().'/'.date('Y').'/photos/messages/';
		$uploader->setPath($path);
		if($uploader->passed()) {
			$image = $uploader->noThumbnails()->resize()->result();
		} else {
			$result['status'] = 0;
			$result['error'] = $uploader->getError();
			return json_encode($result);
		}
	}
	$file = input_file('file');
	$messageFile = '';
	if($file) {
		$uploader = new Uploader($file, 'file');
		$path = get_userid().'/'.date('Y').'/files/posts/';
		$uploader->setPath($path);
		if($uploader->passed()) {
			$file = $uploader->uploadFile()->result();
			$messageFile = array(
				'path' => $file,
				'name' => $uploader->sourceName,
				'extension' => $uploader->extension
			);
			$messageFile = perfectSerialize($messageFile);
		} else {
			$result['status'] = 0;
			$result['error'] = $uploader->getError();
			return json_encode($result);
		}
	}

	$voice = input('voice');
	if($voice) {
		list($header, $voice) = array_pad(explode(',', $voice), 2, '');
		preg_match('/data\:audio\/(.*?);base64/i', $header, $matches);
		$default_extension = 'webm';
		$extension = isset($matches[1]) ? $matches[1] : $default_extension;
		if(!in_array($extension, array($default_extension))) {
			exit('Invalid Format '.$extension);
		}
		$voice = base64_decode(str_replace(' ', '+', $voice));
		$temp_dir = config('temp-dir', path('storage/tmp')).'/chat/voice';
		$file_name = 'voice_'.get_userid().'_'.time();
		if(!is_dir($temp_dir)) {
			mkdir($temp_dir, 0777, true);
		}
		$temp_path = $temp_dir.'/'.$file_name.'.'.$extension;
		file_put_contents($temp_path, $voice);
		$uploader = new Uploader($temp_path, 'audio', false, true);
		if($uploader->passed()) {
			$path = get_userid().'/'.date('Y').'/voices/chats/';
			$uploader->setPath($path);
			$voice = $uploader->uploadFile()->toDB('posts')->result();
		} else {
			$result['status'] = 0;
			$result['message'] = $uploader->getError();
			return json_encode($result);
		}
	}
	$gifLink = input('gif');
	if ($gifLink){
        $file_path = get_userid().'/'.date('Y').'/gif/chat/posts/';
        $gifLink = gifImageProcessing($gifLink,'/chat/gif',$file_path);
    }
	if(!$message and !$image and !$messageFile and !$voice and !$gifLink) return json_encode($result);
	$new = false;
	if(!$cid) {
		if(count($user) == 1) {
			//lets check if the user has not block each other
			if(is_blocked($user[0])) return json_encode($result);
		}
		$cid = get_conversation_id($user);
		$new = true;
	}

	if(!is_conversation_member($cid)) return json_encode($result);
	//send the message to the cid now
	$con = get_conversation($cid, false);
	if($con['type'] == 'single') {
		$theUser = ($con['user1'] == get_userid()) ? $con['user2'] : $con['user1'];
		if(is_blocked($theUser)) return json_encode($result);
	}
	$messageId = send_chat_message($cid, $message, $image, $messageFile, $voice, $gifLink);
	$result['cid'] = $cid;
	$result['messageid'] = $messageId;
	$result['status'] = 1;
	$uid = null;
	if($con['type'] == 'single') {
		$uid = ($con['user1'] == get_userid()) ? $con['user2'] : $con['user1'];
	}
	$result['type'] = $con['type'];
	$result['uid'] = $uid;
	if(input('val.cid') == '') {
		$result['title'] = get_conversation_title($cid);
		$app->setTitle($result['title']);
		$result['sitetitle'] = $app->title;
		$result['url'] = url_to_pager('messages').'?cid='.$cid;
		//$result
	}
	if($new) {
		$result['message'] = view('chat::display/list', array('messages' => get_chat_messages($cid)));
	} else {
		$result['message'] = view('chat::display/message', array('message' => get_chat_message($messageId)));
	}

	//ensure the conversation is not deleted
	revive_conversation($cid);

	if($con['type'] == 'single') {
		remove_typing_status($con);
		register_waiting_message($messageId, $con);
	}
	$result['avatar1'] = isset($con['avatars'][0]) ? $con['avatars'][0] : '';
	$result['avatar2'] = isset($con['avatars'][0]) ? $con['avatars'][1] : '';

	return json_encode($result);
}

function chat_load_messages_pager($app) {
	CSRFProtection::validate(false);
	$data = input('data');
	$result = array(
		'messages' => array()
	);

	if($data) {
		foreach($data as $cid) {
			$theCid = $cid[0];
			$s_m = array();
			$a_m = array();
			foreach($cid[1] as $m) {
				if(is_array($m)) {
					foreach($m as $a) {
						$a_m[] = $a;
					}
				} else {
					$s_m[] = $m;
				}
			}
			$ids = $a_m ? implode(',', $a_m) : implode(',', $s_m);
			if($ids) {
				$messages = get_chat_messages_by_ids($ids);
				$content = view("chat::display/list", array('messages' => $messages, 'mark' => false));
				$result['messages'][$theCid] = $content;
			}
		}
	}

	return json_encode($result);
}

function chat_load_groups_pager($app) {
	CSRFProtection::validate(false);
	echo view('chat::load-groups');
}

function chat_load_search_pager($app) {
	CSRFProtection::validate(false);
	$term = input('term');
	$paginator = list_connections(get_friends(), 10, $term);
	$users = $paginator->results();
	echo view('chat::load-search', array('users' => $users));
}

function chat_preload_pager($app) {
	CSRFProtection::validate(false);
	$cid = input('cid');
	$uid = input('uid');
	if(!$cid) {
		$cid = get_conversation_id(array($uid));
	}
	$conversation = ($cid) ? get_conversation($cid, false) : null;
	$result = array(
		'cid' => $cid,
		'uid' => $uid,
		'messages' => '',
		'type' => ($conversation) ? $conversation['type'] : null
	);
	if(empty($cid)) return json_encode($result);
	$result['messages'] = view("chat::display/list", array('messages' => get_chat_messages($cid, 10), 'mark' => true));

	return json_encode($result);
}

function chat_mark_read_pager($app) {
	CSRFProtection::validate(false);
	$ids = input('ids');
	foreach($ids as $id) {
		if($id) mark_message_read($id);
	}

	return count_unread_messages();
}

function chat_load_dropdown_pager($app) {
	CSRFProtection::validate(false);
	$conversations = get_user_conversations(10);
	$content = '';
	foreach($conversations as $conversation) {
		$content .= view('chat::conversation/display', array('conversation' => $conversation, 'box' => true));
	}

	return $content;
}

function chat_register_opened_pager() {
	CSRFProtection::validate(false);
	$cid = input('cid');
	$uid = input('uid');
	$action = input('action', 'add');
	$cacheName = 'user-chat-opened-'.get_userid();
	$cids = get_cache($cacheName, array());

	switch($action) {
		case 'add':
			if(!in_array($cid, $cids)) $cids[$cid] = $uid;
		break;
		case 'delete':
			$c = array();
			foreach($cids as $ci => $u) {
				if($cid != $ci) $c[$ci] = $u;
			}
			$cids = $c;
		break;
	}

	set_cacheForever($cacheName, $cids);
}

function chat_get_conversations_pager() {
	CSRFProtection::validate(false);
	$cids = input('cids');
	$results = array();
	foreach($cids as $cid) {
		$con = get_conversation($cid);
		$uid = null;
		if($con['type'] == 'single') {
			$uid = ($con['user1'] == get_userid()) ? $con['user2'] : $con['user1'];
		}
		$results[$cid] = array(
			'title' => $con['title'],
			'uid' => $uid,
			'avatar' => ($con['type'] == 'single') ? $con['avatar'] : $con['avatars'][0],
			'avatar2' => ($con['type'] == 'single') ? '' : $con['avatars'][1],
		);
		$results[$cid] = fire_hook('chat.get.conversations.result', $results[$cid]);
	}

	return json_encode($results);
}

function chat_set_status_pager($app) {
	CSRFProtection::validate(false);
	$type = input('type', 0);
	update_user(array('online_status' => $type));
}

function chat_paginate_pager() {
	CSRFProtection::validate(false);
	$offset = input('offset');
	$cid = input('cid');
	$limit = 10;
	$newOffset = $offset + $limit;
	$result = array(
		'cid' => $cid,
		'offset' => $newOffset,
		'messages' => ''
	);
	if(empty($cid)) return json_encode($result);
	$result['messages'] = view("chat::display/list", array('messages' => get_chat_messages($cid, 10, $newOffset)));

	return json_encode($result);

}

function chat_update_send_privacy_pager() {
	CSRFProtection::validate(false);
	$v = input('v');
	save_privacy_settings(array('chat-send-button' => $v));
}

function chat_delete_conversation_pager() {
	$cid = input('cid');
	$userid = get_userid();
	db()->query("UPDATE conversation_members SET active=0 WHERE member_cid='{$cid}' AND user_id='{$userid}'");
	delete_all_messages($cid);
	return redirect_to_pager('messages');
}

function chat_delete_message_pager() {
	$id = input('id');
	$deletedMessages = get_privacy('delete-messages', array(0));
	$deletedMessages = array_merge($deletedMessages, array($id));
	save_privacy_settings(array('delete-messages' => $deletedMessages));

	return true;
}

function mobile_online_pager($app) {
	$app->setTitle("onlines");

	return $app->render(view("chat::mobile/onlines", array("users" => chat_get_onlines())));
}

function chat_typing_pager($app) {
	CSRFProtection::validate(false);
	$con = get_conversation(input('cid'), false);
	if(!$con or $con['type'] == 'multiple') return false;
	$userid = ($con['user1'] == get_userid()) ? $con['user2'] : $con['user1'];
	$cacheName = "typing-{$userid}";
	$result = array();
	if(cache_exists($cacheName)) $result = get_cache($cacheName);
	$result[input('cid')] = time();
	set_cacheForever($cacheName, $result);
	//print_r($result);
}

function chat_remove_typing_pager($app) {
	CSRFProtection::validate();
	$con = get_conversation(input('cid'), false);
	remove_typing_status($con);
}

function chat_friends_online($app) {
	CSRFProtection::validate();
	$users = array_merge(chat_get_onlines(), get_few_offlines());
	return view('chat::load-onlines', array('users' => $users));
}

function chat_download_pager($app) {
	$path = input('path');
	if($path) {
		$name = input('name');
		$file_id = get_media_id($path);
		if($file_id) {
			return download_file($path, $name);
		} else {
			return download_file($path, $name);
		}
	}
}