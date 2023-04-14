<?php
function ajax_pager($app) {
	CSRFProtection::validate(false);
	$action = input('action');
	switch($action) {
		case 'validate_invitation_code':
			$invitation_code = input('invitation_code');
			$validation = invitationsystem_validate_invitatation_code($invitation_code);
			return json_encode(array('status' => $validation));
		break;

		case 'regenerate_invitation_code':
			invitationsystem_set_invitation_code();
			$invitation_code = invitationsystem_get_invitation_code();
			if($invitation_code) {
				$result = $invitation_code;
				$result['status'] = true;
			} else {
				$result['status'] = false;
			}
			return json_encode($result);
		break;

		default:
		break;
	}
}