<?php
function ajax_pager($app) {
	CSRFProtection::validate(false);
	$action = input('action');
	switch($action) {
		case 'music_played':
			$id = input('id');
			if($id) fire_hook('music.played', $id);
		break;

		case 'music_page_dashboard':
			$id = input('id');
			if($id) {
				$music = get_music($id);
				if($music) {
					$refId = $music['id'];
					$refName = 'music';
					echo view('music::music_page_dashboard', array('refName' => $refName, 'refId' => $refId, 'music' => $music));
				}
			}
		break;

		case 'music_page_comment':
			$id = input('id');
			if($id) {
				$music = get_music($id);
				if($music) {
					$refId = $music['id'];
					$refName = 'music';
					echo view('music::music_page_comment', array('refName' => $refName, 'refId' => $refId, 'music' => $music));
				}
			}
		break;

		default:
		break;
	}
}