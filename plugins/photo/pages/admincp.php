<?php
get_menu("admin-menu", "cms")->setActive()->findMenu("photo-manager")->setActive();

function albums_pager($app) {

}

function list_pager($app) {
	$app->setTitle(lang('photo::manage-photos'));
	$photos = photo_list($limit = 20);
	return $app->render(view('photo::photo/list', array('photos' => $photos)));
}

function edit_pager($app) {
	$app->setTitle(lang('photo::edit-photo'));
	$id = input('id');
	$photo = find_photo($id);
	$message = null;
	$val = input('val');
	if($val) {
		save_photo($val);
		redirect_to_pager('admin-photo-list');
	}
	return $app->render(view('photo::photo/edit', array('photo' => $photo, 'message' => $message)));
}

