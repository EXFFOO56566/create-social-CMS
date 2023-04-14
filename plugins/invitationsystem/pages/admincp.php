<?php
function invitations_pager($app) {
	get_menu("admin-menu", "admin-users")->setActive();
	$app->setTitle(lang('invitationsystem::invitations'));
	$search = input('s');
	$invitations = invitationsystem_get_invitations($search);
	return $app->render(view('invitationsystem::admincp/invitations', array('invitations' => $invitations, 'search' => $search)));
}

function ajax_pager($app) {
	CSRFProtection::validate(false);
	$action = input('action');
	switch($action) {
		case 'admin_inviteds':
			$message = null;
			$user_id = input('user_id');
			$inviter_id = invitationsystem_get_inviter_id($user_id);
			$inviter = $inviter_id ? find_user($inviter_id) : false;
			$user = find_user($user_id);
			$limit = config('invitationsystem-inviteds-limit', 5);
			$page = input('page', 1);
			$inviteds = invitationsystem_get_invitations(null, $user_id, $limit);
			$total_records = invitationsystem_count_invites($user_id);
			$total_pages = ceil($total_records / $limit);
			return $app->view('invitationsystem::admincp/ajax/invitation', array('user_id' => $user_id, 'user' => $user, 'inviter' => $inviter, 'inviteds' => $inviteds, 'total_pages' => $total_pages, 'page' => $page, 'message' => $message, 'total_records' => $total_records));
		break;

		default:
		break;
	}
}