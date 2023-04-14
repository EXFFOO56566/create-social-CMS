<?php
get_menu("admin-menu", "plugins")->setActive();
get_menu("admin-menu", "plugins")->findMenu("livestream-manager")->setActive();

function livestream_pager($app) {
	$term = input('term');
	$category = input('category');
	$limit = input('limit', 10);
	$livestreams = get_all_livestreams($category, $term, $limit);
	$app->setTitle(lang('livestreaming::livestreams'));
	return $app->render(view('livestreaming::admincp/lists', array('livestreams' => $livestreams)));
}

function livestreams_manage_pager($app) {
	$app->setTitle(lang('livestreaming::livestreams-manager'));
	$action = input('action');
	$id = input('id');
	$livestream = get_livestream($id);
	if(!$livestream) return redirect_to_pager('admincp-livestream-pager');
	$backUrl = (isset($_POST['back_url'])) ? $_POST['back_url'] : $_SERVER['HTTP_REFERER'];
	switch($action) {
		case 'edit':
			$val = input('val');
			if($val) {
				CSRFProtection::validate();
				save_livestream($val, $livestream);
				return redirect($backUrl);
			}
			return $app->render(view('livestreaming::admincp/edit', array('livestream' => $livestream, 'url' => $backUrl)));
		break;
		case 'delete':
			delete_livestream($livestream['id']);
			return redirect($backUrl);
		break;
	}
}

function categories_pager($app) {
	$app->setTitle(lang('livestreaming::livestream-categories'));
	$categories = (input('id')) ? get_livestream_parent_categories(input('id')) : get_livestream_categories();
	return $app->render(view('livestreaming::admincp/categories/list', array('categories' => $categories)));
}

function manage_categories_pager($app) {
	$action = input('action');
	$app->setTitle(lang('livestreaming::livestream-categories'));
	switch($action) {
		case 'order':
			$id = input('id');
			$ids = input('data');
			for($i = 0; $i < count($ids); $i++) {
				update_livestream_category_order($ids[$i], $i, $id);
			}
		break;
		case 'edit':
			$category = get_livestream_category(input('id'));
			$val = input('val');
			if($val) {
				CSRFProtection::validate();
				save_livestream_category($val, $category);
				$url = ($category['parent_id']) ? url('admincp/livestream/categories?id='.$category['parent_id']) : url('admincp/livestream/categories');
				redirect($url);
			}
			return $app->render(view('livestreaming::admincp/categories/edit', array('category' => $category)));
		break;
		case 'delete':
			$category = get_livestream_category(input('id'));
			$url = ($category['parent_id']) ? url('admincp/livestream/categories?id='.$category['parent_id']) : url('admincp/livestream/categories');
			delete_livestream_category($category);
			redirect($url);
		break;
	}
}

function add_categories_pager($app) {
	$app->setTitle(lang('add-category'));
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		livestream_add_category($val);
		/**
		 * @var $category
		 */
		extract($val);
		$url = ($category) ? url('admincp/livestream/categories?id='.$category) : url('admincp/livestream/categories');
		return redirect(url($url));
	}
	return $app->render(view('livestreaming::admincp/categories/add', array('message' => $message)));
}

function servers_pager($app) {
	$app->setTitle(lang('livestreaming::livestream-servers'));
	$servers = get_livestream_servers();
	return $app->render(view('livestreaming::admincp/servers/list', array('servers' => $servers)));
}

function manage_servers_pager($app) {
	$action = input('action');
	$app->setTitle(lang('livestreaming::livestream-servers'));
	switch($action) {
		case 'order':
			$id = input('id');
			$ids = input('data');
			for($i = 0; $i < count($ids); $i++) {
				update_livestream_server_order($ids[$i], $i, $id);
			}
		break;
		case 'edit':
			$message = null;
			$server = get_livestream_server(input('id'));
			$val = input('val');
			if($val) {
				CSRFProtection::validate();
				save_livestream_server($val, $server);
				$url = isset($server['id']) ? url('admincp/livestream/servers?id='.$server['id']) : url('admincp/livestream/servers');
				redirect($url);
			}
			return $app->render(view('livestreaming::admincp/servers/edit', array('server' => $server, 'message' => $message)));
		break;
		case 'delete':
			$server = get_livestream_server(input('id'));
			$url = ($server['id']) ? url('admincp/livestream/servers?id='.$server['id']) : url('admincp/livestream/servers');
			delete_livestream_server($server);
			redirect($url);
		break;
	}
}

function add_server_pager($app) {
	$app->setTitle(lang('add-server'));
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		livestream_add_server($val);
		/**
		 * @var $server
		 */
		extract($val);
		$url = isset($server) ? url('admincp/livestream/servers?id='.$server) : url('admincp/livestream/servers');
		return redirect(url($url));
	}
	return $app->render(view('livestreaming::admincp/servers/add', array('message' => $message)));
}


function livestream_action_batch_pager($app) {
	$action = input('action');
	$ids = explode(',', input('ids'));

	foreach($ids as $id) {
		switch($action) {

			case 'delete_cat':
				$category = array('id' => $id);
				delete_livestream_category($category);
			break;
			case 'delete_server':
				$server = array('id' => $id);
				delete_livestream_server($server);
			break;
			case 'delete':
				delete_livestream($id);
			break;
		}
	}
	return redirect_back();
}