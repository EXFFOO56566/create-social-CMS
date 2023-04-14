<?php
load_functions('invitationsystem::invitationsystem');

register_asset('invitationsystem::css/invitationsystem.css');

register_asset('invitationsystem::js/invitationsystem.js');

register_pager('admincp/invitations', array(
		'use' => 'invitationsystem::admincp@invitations_pager',
		'as' => 'admin-invitationsystem-invitations'
	)
);

register_pager('admincp/invitationsystem/ajax', array(
		'use' => 'invitationsystem::admincp@ajax_pager',
		'as' => 'admin-invitationsystem-ajax'
	)
);

get_menu('admin-menu', 'admin-users')->addMenu(lang('invitationsystem::invitations'), url_to_pager('admin-invitationsystem-invitations'));
register_hook('admin.modals', function() {
	echo view('invitationsystem::admincp/modals');
});

register_hook('admin.user.manager.members.actions.buttons.extend', function($html, $user_id) {
	$html .= '<a class="btn btn-sm btn-secondary invitationsystem-admin-see-invitation-button" href="'.url_to_pager('admin-invitationsystem-ajax').'?action=admin_inviteds&user_id='.$user_id.'" title="'.lang('invitationsystem::invitation').'"> <i class="ion-ios-people"></i></a>';
	return $html;
});

register_pager('invitationsystem/ajax', array('use' => 'invitationsystem::ajax@ajax_pager', 'as' => 'invitationsystem-ajax'));

register_hook('admin.user.manager.members.actions.buttons.extend', function($html, $user_id) {
	$html .= '<a class="btn btn-sm btn-secondary invitationsystem-admin-see-invitation-button" href="'.url_to_pager('admin-invitationsystem-ajax').'?action=admin_inviteds&user_id='.$user_id.'" title="'.lang('invitationsystem::invitation').'"> <i class="ion-ios-people"></i></a>';
	return $html;
});

register_hook('role.permissions', function($roles) {
	$roles[] = array(
		'title' => lang('invitationsystem::invitation-system-permissions'),
		'description' => '',
		'roles' => array(
			'can-invite-user' => array(
				'title' => lang('invitationsystem::can-invite-user'),
				'value' => 1
			),
		)
	);
	return $roles;
});

if(is_loggedIn() && user_has_permission('can-invite-user')) {

	register_hook('account.general.form.buttons.extend', function($html) {
		//$invitation_code = invitationsystem_get_invitation_code();
		//$html .= '<button class="btn btn-secondary invitationsystem-invitation-code-modal-button" data-invitation-code="'.$invitation_code['code'].'" data-invitation-signup-link="'.url('signup?invitation_code='.$invitation_code['code']).'" data-invitation-code-expiry-time="'.$invitation_code['expiry_time'].'">'.lang('invitationsystem::show-invitation-code').'</button>';
		return $html;
	});

	register_hook('account.settings.menu', function() {
		add_menu('account-menu', array(
				'id' => 'invitationsystem-invitations',
				'link' => url_to_pager('account').'?action=invitations',
				'title' => lang('invitationsystem::invitations')
			)
		);
	});

	register_hook('account.settings', function($action) {
		if($action == 'invitations') {
			app()->setTitle(lang('invitationsystem::invitations'));
			$message = null;
			$user_id = get_userid();
			$inviter_id = invitationsystem_get_inviter_id($user_id);
			$inviter = $inviter_id ? find_user($inviter_id) : false;
			$user = find_user($user_id);
			$limit = config('invitationsystem-inviteds-page-limit', 20);
			$page = input('page', 1);
			$inviteds = invitationsystem_get_invitations(null, $user_id, $limit);
			$invitation_code = invitationsystem_get_invitation_code();
			return view('invitationsystem::invitations', array('user_id' => $user_id, 'user' => $user, 'inviter' => $inviter, 'inviteds' => $inviteds, 'page' => $page, 'message' => $message, 'invitation_code' => $invitation_code));
		}
	});
}

register_hook('signup.form.extend', function($html = '') {
	$invitation_code = input('invitation_code');
	if(!$invitation_code) {
		$val = input('val');
		$invitation_code = isset($val['invitation_code']) ? $val['invitation_code'] : $invitation_code;
	}
	if(segment(0) == 'signup') {
		$html .= '<input type="hidden" name="val[invitation_code]" value="'.$invitation_code.'" />';
	} else {
		$html .= '<div class="form-group"><input name="val[invitation_code]" type="text" value="'.$invitation_code.'" class="form-control form-control-lg" placeholder="'.lang('invitation-code').'" required></div>';
	}
	return $html;
});

register_hook('signup.form.check', function($result) {
	$result = (array) $result;
	$invitation_code = input('invitation_code');
	if(!$invitation_code) {
		$val = input('val');
		$invitation_code = isset($val['invitation_code']) ? $val['invitation_code'] : $invitation_code;
	}
	if($invitation_code) {
		if(invitationsystem_validate_invitatation_code($invitation_code)) {
			$result['status'] = true;
			$result['message'] = null;
		} else {
			$result['status'] = false;
			$result['message'] = lang('invitationsystem::invalid-invitation-code');
			go_to_user_home();
		}
	} else {
		$result['status'] = false;
		$result['message'] = lang('invitationsystem::invitation-code-not-set');
		go_to_user_home();
	}
	return $result;
});

register_hook('signup.completed', function($user_id, $form_vals) {
	$invitation = invitationsystem_signup($user_id, $form_vals['invitation_code']);
	if($invitation) {
		$data = invitationsystem_get_invitation($invitation['id']);
		send_notification($invitation['inviter_id'], 'invitation.signup', $invitation['id'], $data);
	}
});

register_hook('user.delete', function($user_id) {
	invitation_user_delete_hook($user_id);
});

add_available_menu('invitationsystem::invitations', url_to_pager('account').'?action=invitations');

register_hook('display.notification', function($notification) {
	if($notification['type'] == 'invitation.signup') {
		return view('invitation::notifications/signup', array(
				'notification' => $notification,
				'invitation' => unserialize($notification['data'])
			)
		);
	}
});

register_hook('footer', function() {
	echo view('invitationsystem::modals');
});
