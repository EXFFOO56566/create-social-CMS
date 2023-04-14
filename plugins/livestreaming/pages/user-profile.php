<?php
function livestreams_pager($app) {
	$profile_url = profile_url('livestreams', $app->profileUser);
	$livestreams = get_livestreams('user-profile', input('category', 'all'), input('term', null), $app->profileUser['id'], null, input('filter'));
	return $app->render(view('livestreaming::profile/list', array('livestreams' => $livestreams, 'profile_url' => $profile_url)));
}