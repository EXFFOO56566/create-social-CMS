<?php

function livestreams_pager($app) {
	$app->setTitle(lang('livestreaming::livestreams'));
	$category = input('category', 'all');
	$term = input('term');
	$type = input('type', 'browse');
	$filter = input('filter', 'all');
	$livestreams = get_livestreams($type, $category, $term, null, null, $filter);
	return $app->render(view('livestreaming::index', array('livestreams' => $livestreams)));
}

function livestream_page_pager($app) {
	$livestreamId = segment(1);
	$livestream = $app->livestream;
	fire_hook('livestream.playing', null, array($livestream));
	set_meta_tags(array('name' => get_setting("site_title", "crea8socialPRO"), 'title' => $livestream['title'], 'description' => $livestream['description'], 'image' => $livestream['preview_path'], 'keywords' => ''));
	return $app->render(view('livestreaming::page', array('livestream' => $livestream)));
}

function livestream_edit_pager($app) {
	$livestream = get_livestream(input('id'));
	if(!$livestream or !is_livestream_owner($livestream)) redirect('livestreams');

	$app->setTitle(lang('livestreaming::edit-livestream'));
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		save_livestream($val, $livestream);
		return redirect(get_livestream_url($livestream));
	}
	return $app->render(view('livestreaming::edit', array('livestream' => $livestream, 'message' => $message)));
}

function livestream_delete_pager($app) {
	$livestream = get_livestream(input('id'));
	if(!$livestream or !is_livestream_owner($livestream)) return redirect('livestreams');
	delete_livestream($livestream['id']);

	return redirect_to_pager('livestreams');
}

function start_pager($app) {
	$app->setTitle(lang('livestreaming::add-new-livestream'));
	$message = null;
	$return = array();
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		//$livestreamFile = input_file('livestream_file');
		$validator = validator($val, array(
			'title' => 'required',
			'description' => 'required',
		));
		if(validation_fails()) {
			$message = validation_first();
		} else {
			$added = add_livestream($val);
			$return = array();
			if($added) {
				$return = array('status' => 'success', 'id' => $added['id'], 'content' => view('livestreaming::viewer/right', array('livestream' => $added)));
			} else {
				$message = lang('livestreaming::livestream-add-error-message');
				$return = array('status' => 'error', 'message' => $message);
			}
		}
		echo(json_encode($return));
		return;
	}
	return $app->render(view('livestreaming::start', array('message' => $message, 'username' => get_user_name())));
}

function end_pager($app) {
	$app->setTitle(lang('livestreaming::add-new-livestream'));
	$message = null;
	$id = input('id');
	if($id) {
		CSRFProtection::validate();
		$livestream = get_livestream($id);
		if(!$livestream or !is_livestream_owner($livestream)) redirect('livestreams');
		//check for livestream files

		$_FILES['video-blob']['name'] = 'video.webm';
		$video = input_file('video-blob');
		//$audio = input_file('audio-blob');
		$filename = input('filename');

		if($filename == 'nofile' || !$video) {
			if(!$livestream or !is_livestream_owner($livestream)) redirect('livestreams');
			db()->query("UPDATE livestreams SET status='0' WHERE id='{$id}'");
		}

		$videoUploader = new Uploader($video, 'video');
		$videoUploader->setPath(get_userid().'/'.date('Y').'/livestreams/video')->disableCDN();
		$videoUploader->uploadFile();
		$video_path = $videoUploader->result();
		//$audioUploader = new Uploader($audio, 'audio');
		//$audioUploader->setPath(get_userid().'/'.date('Y').'/livestreams/tmp/audio')->disableCDN();
		//$audioUploader->uploadFile();
		//$audio_path = $audioUploader->result();
		$livestreamId = $livestream['id'];
		if($videoUploader->passed()) {
			db()->query("UPDATE livestreams SET status='2', end_time = ".time().", file_path = '".$video_path."' WHERE id='{$id}'");
			fire_hook('livestream.end', null, array($livestream, $livestreamId));
			return json_encode(array("status" => 1, "message" => "Livestream Successfully Uploaded"));
			//$merge = combineVideoAudio($video_path, $audio_path);
			//if($merge['status']) {
			//	db()->query("UPDATE livestreams SET status='2', end_time = ".time()." file_path = '".$merge['file_path']."'; WHERE id='{$id}'");
			//	return json_encode(array("status" => 1, "message" => "File Merged Successfully"));
			//} else {
			//	return json_encode(array("status" => 0, "message" => "File Merge not Successful"));
			//}
		} else {
			fire_hook('livestream.end', null, array($livestream, $livestreamId));
			db()->query("UPDATE livestreams SET status='0', end_time = ".time()." WHERE id='{$id}'");
			return json_encode(array("status" => 0, "message" => "Unknown Error: File is not uploaded successfully, please try again"));
		}
	}
}

function livestill_pager($app) {
	$id = input('id');
	$now = time();
	db()->query("UPDATE livestreams SET updated_last='{$now}' WHERE id='{$id}'");
}

function render_ongoing_pager($app) {
	$limit = config('livestream-list-limit', 10);
	return view('livestreaming::ongoing', array('limit' => $limit));
}

function comment_pager($app) {
	$values = input('val');
	$type = "livestream";
	$typeId = input('id');
	$offset = isset($values['offset']) ? $values['offset'] : 0;
	$comments = get_comments($type, $typeId, 5, $offset);
	$r_comments = array();
	foreach($comments as $comm) {
		$comm['like_count'] = count_likes("comment", $comm['comment_id']);
		$r_comments[] = $comm;
	}
	$parked = array(
		"comments" => $r_comments,
		"comment_count" => count_comments($type, $typeId),
	);
	$participants = get_participants($typeId);
	$participants_count = count($participants);
	return view('livestreaming::comment', array('comments' => $r_comments, 'participants' => $participants));
}

function go_live_pager($app) {
	$app->setLayout('layouts/blank');
	return $app->render(view('livestreaming::golive', array()));
}

function livestream_view_pager($app) {
	$app->setLayout('layouts/blank');
	set_meta_tags(array('name' => get_setting("site_title", "crea8socialPRO"), 'title' => lang('livestreaming::livestream'), 'description' => '', 'image' => "", 'keywords' => ''));
	return $app->render(view('livestreaming::view-page', array('livestream' => 0)));
}

function livestream_view_right_pager($app) {
	$livestream = get_livestream(input('id'));
	if(!$livestream) redirect_back();
	return view('livestreaming::viewer/right', array('livestream' => $livestream));
}

function livestream_cred_pager($app) {
	$values = input('val');
	$type = "livestream";
	$typeId = $values['livestream_id'];

	$last_get_time = $values['last_get_time'] != 'undefined' ? $values['last_get_time'] : time();
	$offset = isset($values['offset']) ? $values['offset'] : 0;
	$likes = get_likes($type, $typeId);
	$last_like_user = count($likes) > 0 ? get_user($likes[count($likes) - 1][0]) : array('username' => null);
	$comments = get_unseen_comments($type, $typeId, $last_get_time);
	$participants = get_participants($typeId);
	$participants_count = count($participants);

	$parked = array(
		"last_get_time" => time() - 1,
		"last_liker" => $last_like_user['username'],
		"like_count" => count_likes($type, $typeId),
		"unseen_comments" => $comments,
		"comment_count" => count_comments($type, $typeId),
		"_join" => get_join($typeId, $last_get_time),
		"_left" => get_left($typeId, $last_get_time),
		"participants_count" => $participants_count,
		"has_ended" => livestream_ended($typeId)
	);
	echo(json_encode($parked));
	return;
}

function more_livestream_comments_pager($app) {
	$values = input('val');
	$type = "livestream";
	$typeId = $values['livestreamId'];
	$offset = isset($values['offset']) ? $values['offset'] : 0;
	$comments = get_comments($type, $typeId, 5, $offset);
	echo(json_encode(array("comments" => $comments)));
	return;
}

function submit_livestream_cred_pager($app) {
	$values = input('val');
	$type = "livestream";
	$typeId = $values['livestreamId'];
	$action = $values['action'];
	$message = null;
	switch($action) {
		case 'comment_like':
			like_item('comment', $values['comment_id'], 1);
			echo json_encode(array("success" => true));
		break;
		case 'like':
			like_item($type, $typeId, 1);
			echo json_encode(array("success" => true));
		break;
		case 'dislike':
			like_item($type, $typeId, 0);
			echo json_encode(array("success" => true));
		break;
		case 'comment':
			if(is_null($values["text"])) return;
			add_comment(array(
				"type" => $type,
				"type_id" => $typeId,
				"text" => $values["text"]));
			echo json_encode(array("success" => true));
		break;
		case 'delete_comment':
			delete_comment($values['comment_id']);
			echo json_encode(array("success" => true));
		break;
		case 'join':
			joinlivestream($typeId);
			echo json_encode(array("success" => true));
		break;
		case 'leave':
			leavelivestream($typeId);
			echo json_encode(array("success" => true));
		break;
		case 'still_active':
			update_active($typeId);
			echo json_encode(array("success" => true));
		break;
		default:
			return;
	}
}

function load_commentdata_pager($app) {
	CSRFProtection::validate(false);
	$id = input('id');
	echo json_encode(count_comment_data($id));
}