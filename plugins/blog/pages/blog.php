<?php
function blog_pager($app) {
	$app->setTitle(lang('blog::blogs'));
	$type = input('type', 'all');
	$category = input('category');
	$term = input('term');
	$filter = input('filter', 'all');
	return $app->render(view('blog::lists', array('blogs' => get_blogs($type, $category, $term, null, 10, $filter))));
}

function blog_page_pager($app) {
	$slug = segment(1);
	$blog = get_blog($slug);
	if(!$blog or (!$blog['status'] and !is_blog_owner($blog))) return redirect(url('blogs'));
	$app->blog = $blog;
	if($blog['status']) db()->query("UPDATE blogs SET views = views + 1 WHERE slug='{$slug}'");
	$app->setTitle($blog['title'])->setKeywords($blog['tags'])->setDescription(str_limit(strip_tags($blog['content']), 100));
	set_meta_tags(array('name' => get_setting("site_title", "Crea8social"), 'title' => $blog['title'], 'description' => str_limit(strip_tags($blog['content']), 100), 'image' => $blog['image'] ? url_img($blog['image'], 200) : '', 'keywords' => $blog['tags']));
	return $app->render(view('blog::view', array('blog' => $blog)));
}

function manage_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$action = input('type');
	$app->setTitle(lang('blog::manage-blogs'));
	$id = input('id');
	$blog = get_blog($id);
	if(is_blog_owner($blog)) {
		switch($action) {
			case 'delete':
				delete_blog($id);
				return redirect(url('blogs?type=mine'));
			break;
			case 'edit':
				$id = input('id');
				$blog = get_blog($id);
				$val = input('val', null, array('content'));
				$message = null;
				if($val) {
					CSRFProtection::validate();
					$validate = validator($val, array(
						'category' => 'required',
						'title' => 'required',
						'content' => 'required'
					));
					if(validation_passes()) {
						$save = save_blog($val, $blog);
						if($save) {
							$blog = get_blog($blog['id']);
							$status = 1;
							$message = lang('blog::blog-edit-success');
							$redirect_url = url('blog/'.$blog['slug']);
						} else {
							$message = lang('blog::blog-save-error');
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
					if($redirect_url) {
						return redirect($redirect_url);
					}
				}
				return $app->render(view('blog::edit', array('blog' => $blog, 'message' => $message)));
			break;
		}
	} else {
		$message = lang('blog::blog-edit-permission-denied');
		redirect(url('blogs'));
	}
}

function add_blog_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$app->setTitle(lang('blog::add-new-blog'));
	$val = input('val');
	if(user_has_permission('can-create-blog') && config('allow-members-create-blog', true)) {
		if($val) {
			CSRFProtection::validate();
			$validate = validator($val, array(
				'category' => 'required',
				'title' => 'required',
				'content' => 'required'
			));
			if(validation_passes()) {
				$blog_id = add_blog($val);
				if($blog_id) {
					$status = 1;
					$message = lang('blog::blog-add-success');
					$blog = get_blog($blog_id);
					if($blog['status']) {
						$redirect_url = url('blog/'.$blog['slug']);
					} else {
						$redirect_url = url('blogs?type=mine');
					}
				} else {
					$message = lang('blog::blog-add-error');
				}
			} else {
				$message = validation_first();
			}
		}
	} else {
		$message = lang('blog::blog-add-permission-denied');
		$redirect_url = url('blogs');
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

	if($redirect_url) {
		return redirect($redirect_url);
	}

	return $app->render(view("blog::add", array('message' => $message)));
}

function blog_api_pager($app) {
	$blogs = get_blogs(true);
	$b = array();
	foreach($blogs->results() as $blog) {
		$blog = arrange_blog($blog);
		$b[] = $blog;
	}
	return json_encode($b);
}