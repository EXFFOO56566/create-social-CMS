<?php

function list_pager($app) {
	$app->setTitle(lang('static-pages'));
	return $app->render(view('site_page/list', array('pages' => Pager::getSitePages(20))));
}

function add_pager($app) {
	$app->setTitle(lang('add-new-page'));
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		if(validation_passes()) {
			$id = Pager::addSitePage(null, $val);
			return redirect(url_to_pager('admin-site-page-builder').'?id='.$id);

		} else {
			$message = validation_first();
		}
	}

	return $app->render(view('site_page/add', array('message' => $message)));
}

function edit_pager($app) {
	$id = input('id');
	$page = Pager::getSitePage($id);
	if (!$page) {
		redirect_back();
	}
	$message = null;
	$val = input('val', null, array('content'));

	if ($val) {
		$val['id'] = $id;
		CSRFProtection::validate();
		Pager::updatePage($val);
		return  redirect_to_pager('manage-statics');
	}
	return $app->render(view('site_page/edit', array('message' => $message, 'page' => $page)));
}

function delete_pager($app) {
	Pager::deleteSitePage(input('id'));
	return redirect_back();
}

function ajax_pager() {
	CSRFProtection::validate(false);
	$action = input('action');
	$result = array('status' => 0);
	switch($action) {
		case 'load_page':
			$id = input('id');
			$page = Pager::getSitePage($id);
			$message = '';
			if($page) {
				$result['status'] = 1;
				$result['html'] = view('site_page/builder/index', array('message' => $message, 'page' => $page));
			}
		break;

		case 'add_page':
			CSRFProtection::validate(false);
			$val = input('val', null, false);
			$title = input('val.title', null, false);
			$content = input('val.content', null, false);
			$val['title'] = $title;
			$val['content'] = $content;
			$val['page_type'] = 'auto';
			if(input('val.slug')) {
				$val['slug'] = input('val.slug', null, false);
			}
			$add = Pager::addSitePage(null, $val);
			if($add) {
				$result['status'] = 1;
				if(is_numeric($add)) {
					$result['page_id'] = $add;
				}
			}
		break;

		case 'save_page':
			$val = input('val');
			if($val) {
				CSRFProtection::validate(false);
				$val['title'] = input('val.title');
				$val['content'] = input('val.content');
				$val['deleted_widgets'] = input('val.deleted_widgets');
				$val['top'] = input('top');
				$val['left'] = input('left');
				$val['middle'] = input('middle');
				$val['right'] = input('right');
				$val['bottom'] = input('bottom');
				$page_id = Pager::updatePage($val);
				if($page_id) {
					$page = Pager::getSitePage($page_id);
					if($page) {
						$message = '';
						$result['status'] = 1;
						$result['html'] = view('site_page/builder/index', array('message' => $message, 'page' => $page));
					}
				}
			}
		break;

		default:
		break;
	}
	$response = json_encode($result);
	return $response;
}

function build_pager($app) {
	$app->setTitle(lang('page-builder'));
	$id = input('id');
	$page = Pager::getSitePage($id);
	if(!$page) {
		$pages = Pager::getSitePages();
		foreach($pages as $page) {
			return redirect(url_to_pager('admin-page-builder').'?id='.$page['id']);
			break;
		}
	}
	$message = '';
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		$val['title'] = input('val.title');
		$val['content'] = input('val.content');
		$val['deleted_widgets'] = input('val.deleted_widgets');
		$val['top'] = input('top');
		$val['left'] = input('left');
		$val['middle'] = input('middle');
		$val['right'] = input('right');
		$val['bottom'] = input('bottom');
		Pager::updatePage($val);
		return redirect(url_to_pager('admin-site-page-builder').'?id='.$id);
	}
	return $app->render(view('site_page/builder/index', array('message' => $message, 'page' => $page)));
}
