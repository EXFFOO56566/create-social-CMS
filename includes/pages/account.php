<?php

get_menu("dashboard-main-menu", 'profile')->setActive(true);

register_account_menu();

/**
 * @param $app
 * @return mixed
 */
function general_pager($app) {
	$app->topMenu = lang('settings');

	$action = input('action', 'general');
	$status = 0;
	$message = '';
	$redirect_url = '';

	switch($action) {
		case 'delete':
			$app->setTitle(lang('delete-my-account'));
			$message = null;
			$password = input('password');
			if(!hash_check($password, get_user_data('password'))) {
				$message = lang('invalid-password');
			} else {
				$status = delete_user();
				if($status) {
					$message = lang('user-deleted');
					$redirect_url = url_to_pager('logout');
				}
			}
			$content = view('account/delete', array('message' => $message));
		break;

		case 'general':
			$val = input('val');
			if($val) {
				CSRFProtection::validate();
				$process = true;
				$username_changed = false;
				if(input_file('image')) {
					$uploader = new Uploader(input_file('image'), 'image');
					$uploader->setPath(get_userid().'/'.date('Y').'/photos/profile/');
					if($uploader->passed()) {
						$avatar = $uploader->resize()->toDB('profile-avatar', get_userid(), 1)->result();
						update_user_avatar($avatar, null, $uploader->insertedId);
					} else {
						$process = false;
						$message = $uploader->getError();
					}
				}

				/**
				 * @var $username
				 * @var $email_address
				 */
				extract($val);
				if(config('allow-change-username', true) and isset($username) and $username != get_user_data('username')) {
					$username_validator = validator(array('username' => $username), array('username' => 'required|alphanum|usernameedit'));
					if(validation_passes()) {
						$username_changed = true;
						if(config('loose-verify-badge-username', true)) {
							$val['verified'] = 0;
						}
					} else {
						$process = false;
						$message = validation_first();
					}
				}

				if(config('allow-change-email', true) and isset($email_address) and $email_address != get_user_data('email_address')) {
					$email_validator = validator(array('email_address' => $email_address), array('email_address' => 'email'));
					if(validation_passes()) {
						$userid = get_userid();
						$check = db()->query("SELECT `id` FROM `users` WHERE `email_address` = '".$email_address."' AND id != '".$userid."'");
						if($check->num_rows > 0) {
							$process = false;
							$message = lang('email-address-is-in-use');
						}
					} else {
						$process = false;
						$message = validation_first();
					}
				}

				$name_validator = validator($val, array(
					'first_name' => 'required',
					'last_name' => 'required'
				));
				if(!validation_passes()) {
					$message = validation_first();
					$process = false;
				}
				if($process) {
				    $val['completion'] = profile_completion($val);
					$status = save_user_general_settings($val);
					if($status) {
						$message = lang('general-settings-saved');
						fire_hook('user.type.signup.completed', get_userid());
						fire_hook('users.category.update', 'update', array($val, get_userid(), true));
						if($username_changed) {
							login_with_user(find_user(get_userid()), true);
						}
						$redirect_url = url_to_pager('account');
					}
				}
			}
			$app->setTitle(lang('general-settings'));
            $account_page = "account/general";
            $account_page = fire_hook("account.custom.page", $account_page);
            $content = view($account_page, array('message' => $message));
		break;

		case 'password':
			$val = input('val');
			$app->setTitle(lang('update-password'));
			$success = '';
			if($val) {
				CSRFProtection::validate();
				/**
				 * expected values
				 * @var $new
				 * @var $current
				 * @var $confirm
				 */
				extract($val);
				if($new and $current and $confirm) {
					$currentPass = get_user_data('password');
					//$current = hash_make($current);
					if(hash_check($current, $currentPass)) {
						if($new != $confirm) {
							$message = lang('new-password-not-match');
						} else {
							$password = hash_make($new);
							$status = update_user(array('password' => $password));
							if($status) {
								$success = lang('password-changed');
								$message = $success;
								$redirect_url = url_to_pager('account').'?action=password';
							}
						}
					} else {
						$message = lang('current-password-not-match');
					}
				} else {
					$message = lang('all-fields-required');
				}
			}
			$content = view('account/password', array('message' => $message, 'success' => $success));
		break;

		case 'notifications':
			$app->setTitle(lang('notification-settings'));
			if($val = input('val')) {
				fire_hook('notification.settings.save', $val);
				$status = save_privacy_settings($val);
				if($status) {
					$message = lang('notification-saved');
					$redirect_url = url_to_pager('account').'?action=notifications';
				}
			}
			$content = view('account/notifications');
		break;

		case 'privacy':
			$app->setTitle(lang('privacy-settings'));
			if($val = input('val')) {
				$status = save_privacy_settings($val);
				if($status) {
					$message = lang('privacy-saved');
					$redirect_url = url_to_pager('account').'?action=privacy';
				}
			}
			$content = view('account/privacy');
		break;

		case 'blocked':
                $app->setTitle(lang('blocked-members'));
			$content = view('account/block-members', array('users' => get_blocked_members()));
		break;

		case 'profile':
			$id = input('id');
			$category = get_custom_field_category($id);
			if($category) {
				$app->setTitle(lang($category['title']));
				$val = input('val');
				if($val) {
					CSRFProtection::validate();
					$field_rules = array();
					foreach(get_custom_fields('user', $id) as $field) {
						if($field['required']) {
							$field_rules[$field['title']] = 'required';
						}
					}
					if($field_rules) {
						$validator = validator(input('val.fields'), $field_rules);
					}
					if(validation_passes() && !$message) {
						$status = save_user_profile_details($val);
						if($status) {
							$message = lang('profile-saved');
							$redirect_url = url_to_pager('account').'?action=profile?id='.$id;
						}
					} else {
						$message = validation_first();
					}
				}
			} else {
				$redirect_url = url_to_pager('account');
			}
			$content = view('account/profile', array('slug' => $id, 'category' => $category, 'message' => $message));
		break;

		default:
			$content = fire_hook('account.settings', null, array($action));
		break;
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

	return $app->render(view('account/layout', array('content' => $content)));
}

function block_user_pager($app) {
	$userid = segment(2);
	block_user($userid);
	return go_to_user_home();
}

function unblock_user_pager() {
	$userid = segment(2);
	unblock_user($userid);
	return redirect_back();
}