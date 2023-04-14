<?php
get_menu("admin-menu", "cms")->setActive();
function lists_pager($app) {
	$app->setTitle(lang('announcement::manage-announcement'));

	return $app->render(view('announcement::list'));
}

function create_pager($app) {
	$app->setTitle(lang('announcement::add-new'));
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		add_announcement($val);
		redirect_to_pager("admin-announcement");
	}
	$view = view('announcement::create', array('message' => $message));
	$view = fire_hook("change.annoucement.add.view", $view, array());
	return $app->render($view);
}

function manage_pager($app) {
	$app->setTitle(lang("announcement::manage"));

	$action = input('action', 'delete');
	$id = input('id');
	$announcement = get_announcement($id);
	if(!$announcement) redirect_to_pager("admin-announcement");
	switch($action) {
		case 'edit':
			$val = input('val');
			if($val) {
				CSRFProtection::validate();
				save_announcement($val, $announcement);
				redirect_to_pager("admin-announcement");
			}
			$view = view('announcement::edit', array('announcement' => $announcement));
			$view = fire_hook("announcement.edit.view", $view, array($announcement));
			return $app->render($view);
		break;
		default:
			db()->query("DELETE FROM announcements WHERE id='{$id}'");
			redirect_to_pager("admin-announcement");
		break;
	}
}


function announcement_action_batch_pager($app) {
	$action = input('action');
	$ids = explode(',', input('ids'));

	foreach($ids as $id) {
		db()->query("DELETE FROM announcements WHERE id='{$id}'");
	}
	return redirect_back();
}
 