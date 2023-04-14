<?php
//get_menu('user-profile', 'connections')->setActive();
function blogs_pager($app) {
	$blogs = get_blogs('mine', null, null, $app->profileUser['id']);
	return $app->render(view('blog::profile/list', array('blogs' => $blogs)));
}