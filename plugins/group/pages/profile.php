<?php
function group_profile_pager($app) {
	get_menu('group-profile', 'posts')->setActive();
	set_meta_tags(array('name' => get_setting("site_title", "Crea8social"), 'title' => get_group_details('page_title'), 'description' => get_group_details('page_desc'), 'image' => get_group_details('group_logo') ? url_img(get_group_details('group_logo'), 200) : '', 'keywords' => ''));
	return $app->render(view('group::profile/posts', array('feeds' => get_feeds('group', $app->profileGroup['group_id']))));
}

function group_photos_profile_pager($app) {
	get_menu("group-profile", 'photos')->setActive();
	return $app->render(view('group::profile/photos', array('photos' => get_photos($app->profileGroup['group_id'], 'group-posts'))));
}

function group_events_profile_pager($app) {
	get_menu("group-profile", 'events')->setActive();
	$type = input('type', 'upcoming');
	return $app->render(view('event::profile/list', array('events' => get_events($type, input('term'), 10, false, input('category'), 'group', $app->profileGroup['group_id']))));
}

function group_blogs_profile_pager($app) {
	get_menu("group-profile", 'blogs')->setActive();
	$type = input('type', 'all');
	$category = input('category');
	$term = input('term');
	$filter = input('filter', 'all');
	$blogs = get_blogs($type, $category, $term, null, null, $filter, null, 'group', $app->profileGroup['group_id'], null, $filter);
	return $app->render(view('blog::profile/list', array('blogs' => $blogs)));
}

function group_musics_profile_pager($app) {
	get_menu("group-profile", 'musics')->setActive();
	$type = input('type', 'all');
	$category = input('category');
	$term = input('term');
	$filter = input('filter', 'all');
	$musics = get_musics($type, $category, $term, null, null, $filter, false, 'group', $entity_id = $app->profileGroup['group_id']);
	$playlist = array();
	foreach($musics->results() as $music) {
		$music['file_path'] = fire_hook('filter.url', url($music['file_path'], false));
		$playlist[$music['slug']] = $music;
	}
	return $app->render(view('music::profile/list', array('musics' => $musics, 'playlist' => $playlist)));
}

function group_videos_profile_pager($app) {
	get_menu("group-profile", 'videos')->setActive();
	$type = input('type', 'all');
	$category = input('category');
	$term = input('term');
	$filter = input('filter', 'all');
	$videos = get_videos($type, $category, $term, null, null, $filter, false, 'group', $entity_id = $app->profileGroup['group_id']);
	return $app->render(view('video::profile/list', array('videos' => $videos)));
}

function group_livestreams_profile_pager($app) {
	get_menu("group-profile", 'livestreams')->setActive();
	$type = input('type', 'all');
	$category = input('category');
	$term = input('term');
	$filter = input('filter', 'all');
	$livestreams = get_livestreams($type, $category, $term, null, null, $filter, false, 'group', $entity_id = $app->profileGroup['group_id']);
	return $app->render(view('livestreaming::profile/list', array('livestreams' => $livestreams)));
}

function group_profile_edit_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		save_group_settings($val, $app->profileGroup['group_id']);
		$status = 1;
		$message = lang('group::group-saved');
		$redirect_url = group_url();
		if(input('ajax')) {
			$result = array(
				'status' => (int) $status,
				'message' => (string) $message,
				'redirect_url' => (string) $redirect_url,
			);
			$response = json_encode($result);
			return $response;
		}
	}
	if($redirect_url) {
		return redirect($redirect_url);
	}
	return $app->render(view('group::profile/edit', array('message' => $message)));
}

function group_profile_members_pager($app) {
	get_menu('group-profile', 'members')->setActive();
	return $app->render(view('group::profile/members', array('users' => get_group_members($app->profileGroup['group_id']))));
}

function add_member_pager($app) {
	CSRFProtection::validate(false);
	$id = input('id');
	$uid = input('uid');
	$group = find_group($id);

	if($group and !group_can_add_member($group)) return false;
	if(is_group_member($id, $uid)) {
		return lang('group::is-already-group-member');
	} else {
		group_add_member($id, $uid);
		//send notification to this user
		send_notification($uid, 'group.add.member', $id);
		return lang('group::member-added-successfully');
	}
}

function remove_member_pager($app) {
	CSRFProtection::validate(false);

	$result = array(
		'status' => 0,
		'message' => ''
	);

	$user_id = input('user_id');
	$group_id = input('group_id');
	$group = find_group($group_id);

	if($group) {
		if(is_group_admin($group) || is_group_moderator($group)) {
			if($group['user_id'] != $user_id) {
				if(is_group_member($group_id, $user_id)) {
					group_remove_member($group_id, $user_id);
					$result['status'] = 1;
					$result['message'] = lang('group::member-removed-successfully');
				} else {
					$result['status'] = 1;
					$result['message'] = lang('group::not-group-member');
				}
			} else {
				$result['message'] = lang('group::cant-remove-admin');
			}
		} else {
			$result['message'] = lang('group::cant-remove-members');
		}
	} else {
		$result['message'] = lang('group::group-not-exist');
	}
	$response = json_encode($result);
	return $response;
}

function member_role_pager($app) {
	CSRFProtection::validate(false);
	$id = input('id');
	$uid = input('uid');
	$value = input('v');
	$group = find_group($id);

	if($group and !is_group_admin($group)) return false;
	if($value == 1) {
		make_group_moderator($group, $uid);
	} else {
		remove_group_moderator($group, $uid);
	}

	return 'Member role set successfully';
}

function join_pager($app) {
	CSRFProtection::validate(false);
	$groupId = input('id');
	$status = input('status');
	$group = find_group($groupId);
	if($status == 0) {
		//we want to join this group
		if(!can_join_group($group)) return false;
		group_add_member($groupId);
	} else {
		group_remove_member($groupId);
	}

}

function upload_cover_pager($app) {
	CSRFProtection::validate(false);
	$result = array(
		'status' => 0,
		'message' => lang('general-image-error'),
		'image' => ''
	);
	$groupId = input('id');
	$group = find_group($groupId);
	if(!$group) return json_encode($result);
	if(!is_group_admin($group)) return json_encode($result);

	if(input_file('image')) {
		$uploader = new Uploader(input_file('image'), 'image');
		$uploader->setPath($group['group_id'].'/'.date('Y').'/photos/cover/');
		if($uploader->passed()) {
			$original = $uploader->resize($uploader->getWidth(), null, 'fill', 'any')->toDB("group", $group['group_id'])->result();


			//delete the old resized cover
			if($group['group_cover_resized']) {
				delete_file(path($group['group_cover_resized']));
			}

			//lets now crop this image for the resized cover
			$uploader->setPath($group['group_id'].'/'.date('Y').'/photos/cover/resized/');
			$cover = $uploader->crop(0, 0, $uploader->getWidth(), ($uploader->getWidth() * 0.4))->result();
			$result['image'] = url_img($cover);
			$result['original'] = url_img($original);
			$result['id'] = $uploader->insertedId;
			update_group_details(array('group_cover' => $original, 'group_cover_resized' => $cover), $group['group_id']);
			$result['status'] = 1;
		} else {
			$result['message'] = $uploader->getError();
		}
	}

	return json_encode($result);
}

function reposition_cover_pager($app) {
	CSRFProtection::validate(false);
	$pos = input('pos');
	$width = input('width', 623);
	$groupId = input('id');
	$group = find_group($groupId);
	if(!$group) return false;
	if(!is_group_admin($group)) return false;

	$cover = path($group['group_cover']);
	$uploader = new Uploader($cover, 'image', false, true);
	$uploader->setPath($group['group_id'].'/'.date('Y').'/photos/cover/resized/');
	$pos = abs($pos);
	$pos = ($pos / $width);
	$yCordinate = 0;
	$srcWidth = $uploader->getWidth();
	$srcHeight = $srcWidth * 0.4;
	if(!empty($pos) & $pos < $srcWidth) {
		$yCordinate = $pos * $uploader->getWidth();
	}
	$cover = $uploader->crop(0, $yCordinate, $srcWidth, $srcHeight)->result();

	//delete old resized image if available
	if($group['group_cover_resized']) {
		delete_file(path($group['group_cover_resized']));
	}
	update_group_details(array('group_cover_resized' => $cover), $group['group_id']);
	return url_img($cover);
}

function remove_cover_pager($app) {
	CSRFProtection::validate(false);
	$groupId = input('id');
	$group = find_group($groupId);
	if(!$group) return false;
	if(!is_group_admin($group)) return false;
	delete_file(path($group['group_cover_resized']));

	update_group_details(array('group_cover' => '', 'group_cover_resized' => ''), $group['group_id']);
}

function change_logo_pager($app) {
	CSRFProtection::validate(false);
	$groupId = input('id');
	$group = find_group($groupId);
	$result = array(
		'status' => 0,
		'message' => lang('general-image-error'),
		'image' => ''
	);
	if(!$group) return json_encode($result);
	if(!is_group_admin($group)) return json_encode($result);

	$src = input('avatar_src');
	$data = input('avatar_data');
	$file = input_file('avatar_file');
	if($file) {
		$avatar_sizes = array(75, 200, 600, 920);
		$old_avatars = array();
		$old_avatar = $group['group_logo'];
		$old_media_id = get_media_id($old_avatar);
		if(preg_match('/_%w_/', $old_avatar)) {
			foreach($avatar_sizes as $size) {
				$old_avatars[] = url(str_replace('%w', $size, $old_avatar));
			}
		} else {
			$old_avatars[] = url($old_avatar);
		}
		require path('includes/libraries/CropAvatar.php');
		$crop = new CropAvatar($src, $data, $file);
		$path = $crop->getResult();
		$uploader = new Uploader($path, 'image', false, true);
		$uploader->setPath($group['group_id'].'/'.date('Y').'/photos/logo/');
		if($uploader->passed()) {
			$image = $uploader->resize()->toDB("group-logo", $group['group_id'])->result();

			update_group_details(array('group_logo' => $image), $group['group_id']);
			fire_hook('group.logo.updated', null, array($group['group_id'], $uploader->insertedId, $image));
			$new_avatars = array();
			$group = find_group($groupId);
			$new_avatar = $group['group_logo'];
			$new_media_id = get_media_id($new_avatar);
			if(preg_match('/_%w_/', $new_avatar)) {
				foreach($avatar_sizes as $size) {
					$new_avatars[] = url(str_replace('%w', $size, $new_avatar));
				}
			} else {
				$new_avatars[] = url($new_avatar);
			}
			$avatars = array();
			for($i = 0; $i < count($old_avatars); $i++) {
				$avatars[$old_avatars[$i]] = $new_avatars[$i];
			}
			$result['avatars'] = $avatars;
			$result['oldMediaId'] = $old_media_id;
			$result['newMediaId'] = $new_media_id;
			$result['avatars'] = $avatars;
			$result['message'] = null;
			$result['result'] = url_img($image, 200);
			$result['state'] = 200;
			$result['status'] = 1;
			$result['image'] = url_img($image, 200);
			$result['id'] = $uploader->insertedId;
			$result['large'] = url_img($image, 920);
		} else {
			$result['message'] = $uploader->getError();
		}
	}

	return json_encode($result);
}

function group_category_pager($app) {
	$categorySlug = segment(2);
	$category = find_group_category($categorySlug);
	$type = input('type', 'category');
	$app->groupType = $type;
	$app->setTitle($category['title'])->setLayout("group::category/layout", array('category' => $category));
	$groups = get_groups($type, '', '10', '', $category['id']);
	return app()->render(view('group::lists', array('category' => $category, 'groups' => $groups, 'type' => $type)));
}