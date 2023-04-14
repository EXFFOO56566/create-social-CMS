<?php
function general_pager($app) {
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		$message = lang('page::page-successfully-saved');
		save_page_general_setings($val, $app->profilePage);
		$app->profilePage = find_page($app->profilePage['page_id']);
	}
	return $app->render(view('page::manage/general', array('message' => $message)));
}

function custom_field_pager($app) {
	$fields = get_custom_fields('page', segment(4));
	if(!$fields) return redirect(manage_page_url('general', $app->profilePage));
	$message = null;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		$message = lang('page::page-successfully-saved');
		save_page_fields_setings($val, $app->profilePage);
		$app->profilePage = find_page($app->profilePage['page_id']);
	}
	return $app->render(view('page::manage/fields', array('message' => $message, 'fields' => $fields)));
}

function roles_pager($app) {
	$message = null;
	if(!is_page_admin($app->profilePage)) return redirect(manage_page_url('general', $app->profilePage));
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		$message = lang('page::page-successfully-saved');
		save_page_roles($val, $app->profilePage);
		$app->profilePage = find_page($app->profilePage['page_id']);
	}
	return $app->render(view('page::manage/roles', array('message' => $message)));
}

function remove_role_pager($app) {
	CSRFProtection::validate(false);
	$pageId = input('page');
	$user = input('user');
	return remove_page_role($user, $pageId);
}
function social_connections_pager($app){
    $message = null;
    $val = input('val');
    $page = $app->profilePage;
    if ($val){
        save_social_more_settings($val, 'pages',$page['page_id']);
        $page = find_page($page['page_id']);
    }
    return $app->render(view('page::manage/page-connection-form', array('page' => $page, 'message' => $message)));
}
function page_info_pager($app){
    $message = null;
    $val = input('val');
    $page = $app->profilePage;
    if ($val){
        more_social_update_page($page['page_id'], $val);
        $page = find_page($page['page_id']);
    }
    return $app->render(view('page::manage/page-info', array('page' => $page, 'message' => $message)));
}