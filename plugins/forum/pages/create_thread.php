<?php
function create_thread_pager($app) {
	$status = 0;
	$messages = null;
	$message = '';
	$redirect_url = '';

	$app->setTitle(lang("forum::create-thread"));

	$page = input('page', 1);
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		$val = array();
		$val['category_id'] = input('category_id');
		$val['subject'] = input('subject');
		$val['postbox'] = input('postbox', null, false);
		$val['tags'] = input('tags');
		$val['type'] = input('type');
		$post_id = forum_execute_form($val);
		if($post_id) {
			$status = 1;
			$message = implode(', ', (array) $messages);
			$redirect_url = url_to_pager('forum-thread-slug', array('appends' => '/'.$post_id.'/'.forum_slugger($val['subject'])));
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
	$categories = forum_get_categories();
	return $app->render(view('forum::create_thread', array('categories' => $categories, 'page' => $page, 'messages' => $messages)));
}