<?php
function create_group_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$val = input('val');
	$app->setTitle(lang('group::create-group'));

	if($val) {
		CSRFProtection::validate();
		$title = input('val.title');
		$group_name = unique_slugger($title);
		$val['name'] = $group_name;
		$rules = array(
			'title' => 'required|min:2',
			'name' => 'required|min:2|username'
		);

		$fieldRules = array();
		foreach(get_form_custom_fields('page') as $field) {
			if($field['required']) {
				$fieldRules[$field['title']] = 'required';
			}
		}

		$validator = validator($val, $rules);
		$status = '';
		if(validation_passes()) {
			$groupId = group_add($val);
			if($groupId) {
				$group = find_group($groupId);
				$group_logo = input_file('group_logo');
				if($group_logo) {
					$uploader = new Uploader($group_logo, 'image');
					$uploader->setPath($group['group_id'].'/'.date('Y').'/photos/logo/');
					if($uploader->passed()) {
						$image = $uploader->resize()->toDB("group-logo", $group['group_id'])->result();
						update_group_details(array('group_logo' => $image), $group['group_id']);
					}
				}
				$status = 1;
				$message = lang('group::group-create-success');
				$redirect_url = group_url(null, $group);
			} else {
				$message = lang('group::group-create-error');
			}
		} else {
			$message = validation_first();
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
	}
	if($redirect_url) {
		return redirect($redirect_url);
	}
	return group_render(view('group::create', array('message' => $message)), 'create', true);
}

function manage_group_pager($app) {
	$app->setTitle(lang('group::groups'));
	$type = input('type', 'recommend');
	$app->groupType = $type;
	return group_render(view('group::lists', array('groups' => get_groups($type, input('term'), 10, input('filter'), input('category')), 'type' => $type)), 'manage');
}

function group_delete_pager($app) {
	$groupId = segment(2);
	$group = find_group($groupId);
	if(!is_group_admin($group)) return redirect_to_pager('group-manage');
	delete_group($group);

	return (input('admin', false)) ? redirect_back() : redirect_to_pager('group-manage');
}

/**
 * Help function to render page with its layout
 */
function group_render($content, $type = "all", $fullWidth = false) {

	return app()->render(view("group::layout", array('content' => $content, 'type' => $type, 'fullWidth' => $fullWidth)));
}

function admin_group_pager($app) {
	$app->setTitle(lang('group::groups-manager'));
	get_menu('admin-menu', 'plugins')->setActive();
	return $app->render(view("group::lists", array("groups" => get_all_groups())));
}

function group_admin_edit_pager($app) {
	$app->setTitle(lang('group::groups-manager'));
	get_menu('admin-menu', 'plugins')->setActive();
	$group = find_group(segment(3));
	if(!$group) redirect_back();
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		save_group_settings($val, $group['group_id']);
		redirect(url('admincp/groups'));
	}
	return $app->render(view("group::edit", array("group" => $group)));
}

function group_action_batch_pager($app) {
	$action = input('action');
	$ids = explode(',', input('ids'));

	foreach($ids as $id) {

		$groupId = $id;
		db()->query("DELETE FROM group_members WHERE member_group_id='{$groupId}'");
		//delete its posts too
		delete_posts('group', $groupId);
		db()->query("DELETE FROM groups WHERE group_id='{$groupId}'");
	}
	return redirect_back();
}