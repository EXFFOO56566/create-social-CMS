<?php
function playlists_pager($app) {
	$app->setTitle(lang('music::playlists'));
	$term = input('term');
	$type = input('type', 'browse');
	$filter = input('filter', 'all');
	$playlists = get_playlists($type, $term, null, null, $filter);
	return $app->render(view('music::playlists', array('playlists' => $playlists)));
}

function playlist_page_pager($app) {
	$playlistId = segment(2);
	$play_list = get_playlist($playlistId);
	if(!$play_list) return MyError::error404();
	set_meta_tags(array('name' => get_setting("site_title", "Crea8social"), 'title' => $play_list['title'], 'description' => $play_list['description'], 'image' => img('music::images/playlist.png'), 'keywords' => ''));
	$playlist = get_playlist_musics($play_list['id']);
	$empty_music = array("id" => 0, "slug" => "0", "title" => "", "artist" => "", "album" => "", "user_id" => 0, "entity_type" => "user", "entity_id" => "0", "cover_art" => "", "category_id" => "0", "source" => "upload", "code" => "", "status" => 1, "file_path" => img('music::audio/empty.mp3'), "play_count" => "", "" => "", "privacy" => "", "auto_posted" => "", "time" => "");
	$playlist = empty($playlist) ? array('0' => $empty_music) : $playlist;
	$first_track = reset($playlist)['slug'];
	$music = input('now_playing') ? get_music(input('now_playing')) : $playlist[$first_track];
	$music['file_path'] = fire_hook('filter.url', url($music['file_path']));
	$music['cover_art'] = fire_hook('filter.url', url_img($music['cover_art'], 600));
	$app->setTitle($play_list['title']);
	return $app->render(view('music::page', array('music' => $music, 'playlist' => $playlist, 'play_list' => $play_list)));
}

function playlist_edit_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$playlist = get_playlist(input('id'));
	if(!$playlist or !is_playlist_owner($playlist)) {
		$message = lang('music::playlist-edit-permission-denied');
		$redirect_url = url('music-playlists');
	}
	$app->setTitle(lang('music::edit-playlist'));
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		if(isset($val['musics']) && !empty($val['musics'])) {
			$save = save_playlist($val, $playlist);
			if($save) {
				$status = 1;
				$message = lang('music::playlist-saved');
				$redirect_url = get_playlist_url($playlist);
			} else {
				$message = lang('music::music-edit-error-message');
			}
		} else {
			$message = lang('music::empty-playlist-error');
		}

		if(input('ajax')) {
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
	return $app->render(view('music::edit_playlist', array('playlist' => $playlist, 'message' => $message)));
}

function playlist_delete_pager($app) {
	$playlist = get_playlist(input('id'));
	if(!$playlist or !is_playlist_owner($playlist)) return redirect_to_pager('music-playlists');
	delete_playlist($playlist['id']);
	return redirect_to_pager('music-playlists');
}

function playlist_create_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$app->setTitle(lang('music::add-new-playlist'));
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		$validator = validator($val, array('title' => 'required'));
		if(validation_fails()) {
			$message = validation_first();
		} else {
			if(isset($val['musics']) && !empty($val['musics'])) {
				$added = add_playlist($val);
				if($added) {
					$status = 1;
					$message = lang('music::playlist-create-success');
					$redirect_url = get_playlist_url($added);
				} else {
					$message = lang('music::playlist-create-error-message');
				}
			} else {
				$message = lang('music::empty-playlist-error');
			}
		}

		if(input('ajax')) {
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
	return $app->render(view('music::create_playlist', array('message' => $message)));
}

function playlist_editor_search_result_pager($app) {
	$category = input('category', 'all');
	$term = input('term');
	$type = input('type', 'browse');
	$filter = input('filter', 'all');
	$music_items = input('music_items');
	$search_result = input('search_result');
	$musics = get_musics($type, $category, $term, null, null, $filter);
	return view('music::ajax/playlist_editor_search_result', array('musics' => $musics, 'music_items' => $music_items, 'search_result' => $search_result));
}