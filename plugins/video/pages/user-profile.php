<?php
function videos_pager($app) {
	$profile_url = profile_url('videos', $app->profileUser);
	$videos = get_videos('user-profile', input('category', 'all'), input('term'), $app->profileUser['id'], null, input('filter'));
	return $app->render(view('video::profile/list', array('videos' => $videos, 'profile_url' => $profile_url)));
}