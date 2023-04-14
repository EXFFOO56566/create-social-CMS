<?php
function musics_pager($app) {
	if(isset($_SERVER['REDIRECT_URL']) && preg_match('/musics\//i', $_SERVER['REDIRECT_URL'])) {
		$redir = http_build_query($_GET) == '' ? url_to_pager('musics') : url_to_pager('musics').'?'.http_build_query($_GET);
		header('location: '.$redir);
	}
	$app->setTitle(lang('music::musics'));
	$category = input('category', 'all');
	$term = input('term');
	$type = input('type', 'browse');
	$filter = input('filter', 'all');
	$musics = get_musics($type, $category, $term, null, null, $filter);
	$playlist = array();
	foreach($musics->results() as $music) {
		$music['file_path'] = fire_hook('filter.url', url($music['file_path']));
		$music['cover_art'] = fire_hook('filter.url', url_img($music['cover_art'], 600));
		$playlist[$music['slug']] = $music;
	}
	return $app->render(view('music::index', array('musics' => $musics, 'playlist' => $playlist)));
}

function music_page_pager($app) {
	$musicId = segment(1);
	$music = get_music($musicId);
	if(!$music) return MyError::error404();
	$music['file_path'] = url($music['file_path']);
	$music['cover_art'] = $music['cover_art'] ? url_img($music['cover_art'], 200) : img('music::images/preview.png', 200);
	$playlist = array($music['slug'] => $music);
	$app->setTitle($music['title']);
	$meta_tags = array('name' => get_setting("site_title", "Crea8social"), 'title' => $music['title'], 'description' => $music['artist'].' - '.$music['title'], 'image' => $music['cover_art'] ? url_img($music['cover_art'], 920) : img('music::images/preview.png', 920), 'keywords' => '');
	if($music['file_path']) {
		$meta_tags['audio'] = url($music['file_path']);
	}
	set_meta_tags($meta_tags);
	return $app->render(view('music::page', array('music' => $music, 'playlist' => $playlist)));
}

function music_edit_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$music = get_music(input('id'));
	if(!$music or !is_music_owner($music)) {
		$message = lang('music::music-edit-permission-denied');
		$redirect_url = url('musics');
	}
	$app->setTitle(lang('music::edit-music'));
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		$cover_art = input_file('cover_art');
		$cover_art_path = $music['cover_art'];
		if($cover_art) {
			$uploader = new Uploader($cover_art);
			if($uploader->passed()) {
				$uploader->setPath(get_userid().'/'.date('Y').'/musiccovers/');
				$cover_art_path = $uploader->resize()->result();
			} else {
				$message = $uploader->getError();
			}
		}
		$val['cover_art'] = $cover_art_path;
		$save = save_music($val, $music);
		if($save) {
			$status = 1;
			$message = lang('music::music-saved');
			$redirect_url = get_music_url($music);
		} else {
			$message = lang('music::music-edit-error-message');
		}

		if(input('val') && input('ajax')) {
			$result = array(
				'status' => (int) $status,
				'message' => (string) $message,
				'redirect_url' => (string) $redirect_url,
			);
			$response = json_encode($result);
			return $response;
		}
		if($redirect_url) {
			return redirect($redirect_url);
		}
	}
	return $app->render(view('music::edit', array('music' => $music, 'message' => $message)));
}

function music_delete_pager($app) {
	$music = get_music(input('id'));
	if(!$music or !is_music_owner($music)) return redirect('musics');
	delete_music($music['id']);
	return redirect_to_pager('musics');
}

function create_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$app->setTitle(lang('music::add-new-music'));
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		//check for music files
		$musicFile = input_file('music_file');

		if($musicFile) {
			$validator = validator($val, array(
				'title' => 'required',
			));
			if(validation_fails()) {
				$message = validation_first();
			} else {
				$uploader = new Uploader($musicFile, 'audio');
				if($uploader->passed()) {
					$cover_art = input_file('cover_art');
					$cover_art_path = '';
					if($cover_art) {
						$uploader = new Uploader($cover_art);
						if($uploader->passed()) {
							$uploader->setPath(get_userid().'/'.date('Y').'/musiccovers/');
							$cover_art_path = $uploader->resize()->result();
						} else {
							$message = $uploader->getError();
						}
					}
					$val['cover_art'] = $cover_art_path;
					$added = add_music($val);
					if($added) {
						$status = 1;
						$message = lang('music::music-add-success');
						$redirect_url = get_music_url($added);
					} else {
						$message = lang('music::music-add-error-message');
					}
				} else {
					$message = $uploader->getError();
				}
			}
		} else {
			fire_hook('add.music.file.path', array('result' => 0), array($val));
			$message = lang('music::music-add-error-message');
		}

		if(input('val') && input('ajax')) {
			$result = array(
				'status' => (int) $status,
				'message' => (string) $message,
				'redirect_url' => (string) $redirect_url,
			);
			$response = json_encode($result);
			return $response;
		}
		if($redirect_url) {
			return redirect($redirect_url);
		}
	}
	return $app->render(view('music::create', array('message' => $message)));
}