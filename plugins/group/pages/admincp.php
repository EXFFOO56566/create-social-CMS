<?php
/**
 * Created by PhpStorm.
 * User: OKE AYODEJI
 * Date: 3/1/2018
 * Time: 4:45 PM
 */
function manage_categories_pager($app) {
	$action = input('action');
	$app->setTitle(lang('group::group-categories'));
	switch($action) {
		case 'edit':
			$category = get_group_category(input('id'));
			$val = input('val');
			if($val) {
				CSRFProtection::validate();
				$image = input_file('image');
				$path = "";
				if($image) {
					$uploader = new Uploader($image);
					if($uploader->passed()) {
						$uploader->setPath('admincp'.'/'.date('Y').'/group/');
						$path = $uploader->resize()->result();
						$val['image'] = $path;
					} else {
						$message = $uploader->getError();
					}
				}
				save_group_category($val, $category);
				$url = url_to_pager('admin-group-categories-pager');
				redirect($url);
			}
			return $app->render(view('group::categories/edit', array('category' => $category)));
		break;
		case 'delete':
			$category = get_group_category(input('id'));
			$url = url_to_pager('admin-group-categories-pager');
			delete_group_category($category);
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
		$image = input_file('image');
		$path = "";
		if($image) {
			$uploader = new Uploader($image);
			if($uploader->passed()) {
				$uploader->setPath('admincp'.'/'.date('Y').'/group/');
				$path = $uploader->resize()->result();
				$val['image'] = $path;
			} else {
				$message = $uploader->getError();
			}
		}
		group_add_category($val);
		/**
		 * @var $category
		 */
		extract($val);
		$url = url_to_pager('admin-group-categories-pager');
		return redirect(url($url));
	}
	return $app->render(view('group::categories/add', array('message' => $message)));
}

function categories_pager($app) {
	$app->setTitle(lang('group::group-categories'));
	$categories = get_group_categories();
	return $app->render(view('group::categories/list', array('categories' => $categories)));
}