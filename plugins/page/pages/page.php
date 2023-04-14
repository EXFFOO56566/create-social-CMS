<?php
load_functions("page::page");
function create_page_pager($app) {
	$app->setTitle(lang('page::create-a-page'));
	$message = null;
	$status = 0;
	$redirect_url = null;
	$val = input('val');
	if(!user_has_permission('can-create-page')) redirect_to_pager('page-all');
	if($val) {
		CSRFProtection::validate();
		$title = input('val.name');
		$page_url = unique_slugger($title);
		$val['page_url'] = $page_url;
		$rules = array(
			'name' => 'required|min:2',
			'category' => 'required',
			'page_url' => 'required|min:2|username'
		);

		$fieldRules = array();
		foreach(get_form_custom_fields('page') as $field) {
			if($field['required']) {
				$fieldRules[$field['title']] = 'required';
			}
		}

		$validator = validator($val, $rules);
		if($fieldRules) $validator = validator(input('val.fields'), $fieldRules);

		if(validation_passes()) {
			$page_id = page_add($val);
			$page = find_page($page_id);
			if($page) {
				$status = 1;
				$redirect_url = page_url(null, $page);
			}
		} else {
			$message = validation_first();
		}
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
	return page_render(view('page::create', array('message' => $message)), 'create', true);
}

function my_pages_pager($app) {
	$app->setTitle(lang('page::my-pages'));
	return page_render(view('page::mine', array('pages' => get_pages('mine'))), 'mine');
}

function pages_pager($app) {
	$app->setTitle(lang('page::pages'));
	$type = input('type', 'browse');
	$term = input('term');
	$category = input('category', 'all');
	$filter = input('filter', 'all');
	$app->pageType = $type;
	return page_render(view('page::mine', array('pages' => get_pages($type, $term, 10, $category, $filter))));
}

function page_more_invite_pager() {
	CSRFProtection::validate(false);
	$offset = input('offset');
	$page_id = input('id');
	$limit = 20;
	$newOffset = $offset + $limit;
	$users = '';
	foreach(get_invite_friends(null, $limit, $newOffset) as $user) {
		$users .= view('page::block/invite/display', array('user' => $user, 'page_id' => $page_id));
	}

	return json_encode(array(
		'offset' => $newOffset,
		'users' => $users
	));
}

function page_invite_pager() {
	CSRFProtection::validate(false);
	$user = input('user');
	$page = input('page');
	if($user != get_userid() and !has_liked('page', $page, 1, $user) and !has_page_invited($page, $user)) {
		send_notification($user, 'page.invite', $page);
		add_page_invite($page, $user);
	}
}

function page_search_invite_pager() {
	CSRFProtection::validate(false);
	$page = input('page');
	$term = input('term');

	$users = '';
	foreach(get_invite_friends($term) as $user) {
		$users .= view('page::block/invite/display', array('user' => $user, 'page_id' => $page));
	}

	return $users;
}

function delete_page_pager($app) {
	$id = input('id');
	delete_page($id);
	if(input('admin')) redirect_back();
	redirect_to_pager('page-mine');
}

/**
 * Help function to render page with its layout
 */
function page_render($content, $type = "all", $fullWidth = false) {

	return app()->render(view("page::layout", array('content' => $content, 'type' => $type, 'fullWidth' => $fullWidth)));
}