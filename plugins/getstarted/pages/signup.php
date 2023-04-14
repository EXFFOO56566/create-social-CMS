<?php
/**
 * Signup welcome steps pager
 */
function welcome_pager($app) {
	$step = input('step', 'info');
	$content = "";
	$message = null;
	session_put('welcome-page-visited', 'visited');
	//get_menu("dashboard-menu", 'user-welcome')->setActive();
	$app->onHeaderContent = false;
	$app->setTitle(lang('welcome'));
	fire_hook('collect.get.started', $step, array());
	switch($step) {
		case 'import':
			$content = view("getstarted::import", array('message' => $message));
		break;
		case 'add-people':
			$content = view("getstarted::add-people", array('message' => $message));
		break;
		case 'finish':
			update_user(array('welcome_passed' => 1), null, true);
			return go_to_user_home();
		break;
		default:
			$status = 0;
			$pass = 1;
			$message = '';
			$redirect_url = '';
			$val = input('val');
			$message = null;
/*			$avatar = get_user_data('avatar');
			if($avatar) {
				$path = explode('/', $avatar);
				if($path[1] != 'avatar' && ($path[2] != 'avatar')) {
					if(plugin_loaded('social')) {
						return redirect((url_to_pager('signup-welcome').'?step=import'));
					} else {
						return redirect((url_to_pager('signup-welcome').'?step=add-people'));
					}
				}
			}*/
			if($val) {
				CSRFProtection::validate();
				fire_hook("locationfilter.gs", null, array($val));
				if(input('avatar')) {
					$user_id = get_userid();
					$avatar = input('avatar');
					list($header, $data) = explode(',', $avatar);
					preg_match('/data\:image\/(.*?);base64/i', $header, $matches);
					$extension = $matches[1];
					if(!in_array($extension, array('jpg', 'png', 'gif', 'jpeg'))) {
						exit('Invalid Format '.$extension);
					}
					$data = str_replace(' ', '+', $data);
					$data = base64_decode($data);
					$dir = config('temp-dir', path('storage/tmp')).'/awesome_cropper';
					if(!is_dir($dir)) {
						mkdir($dir, 0777, true);
					}
					$path = $dir.'/avatar_'.get_userid().'_'.time().'.'.$extension;
					file_put_contents($path, $data);
					$uploader = new Uploader($path, 'image', false, true);
					$uploader->setPath($user_id.'/'.date('Y').'/photos/profile/');
					if($uploader->passed()) {
						$avatar = $uploader->resize()->toDB("profile-avatar", get_userid(), 1)->result();
						update_user_avatar($avatar, null, $uploader->insertedId, false);
					} else {
						$message = $uploader->getError();
					}
				}
				if(!$message) {
					$user_data = array();
					$email = input('val.email_address', null);
					if(isset($email)) {
						$user_data['email_address'] = $email;
						$user_id = get_userid();
						$user = get_user();
						if(!$user['email_address'] || $user['email_address'] == $user['social_email']) {
							validator(array('email_address' => $email), array('email_address' => 'email'));
							if(validation_passes()) {
								$check = db()->query("SELECT `id` FROM `users` WHERE `email_address` = '".$email."' AND id != '".$user_id."'");
								if($check->num_rows > 0) {
									$pass = false;
									$message = lang('email-address-is-in-use');
								}
							} else {
								$pass = false;
								$message = validation_first();
							}
						}
					}
					$bio = input('val.bio');
					if(isset($bio)) {
						$user_data['bio'] = $bio;
					}
					$gender = input('val.gender');
					if(isset($gender)) {
						$user_data['gender'] = $gender;
					}
					$birth_day = input('val.birth_day');
					if(isset($birth_day)) {
						$user_data['birth_day'] = $birth_day;
					}
					$birth_month = input('val.birth_month');
					if(isset($birth_month)) {
						$user_data['birth_month'] = $birth_month;
					}
					$birth_year = input('val.birth_year');
					if(isset($birth_year)) {
						$user_data['birth_year'] = $birth_year;
					}
					$city = input('val.city');
					if(isset($city)) {
						$user_data['city'] = $city;
					}
					$state = input('val.state');
					if(isset($state)) {
						$user_data['state'] = $state;
					}
					$country = input('val.country');
					if(isset($country)) {
						$user_data['country'] = $country;
					}
					if($pass) {
					    $user_data['completion'] = profile_completion($user_data);
						$status = update_user($user_data);
						$redirect_url = plugin_loaded('social') ? url_to_pager('signup-welcome').'?step=import' : url_to_pager('signup-welcome').'?step=add-people';
						if($status && input('val.fields')) {
							$field_rules = array();
							foreach(get_form_custom_fields('user') as $field) {
								if($field['required']) {
									$field_rules[$field['title']] = 'required';
								}
							}
							if($field_rules) {
								validator(input('val.fields'), $field_rules);
							}
							if(validation_passes() && !$message) {
								$status = save_user_profile_details($val);
								if($status) {
									$message = lang('info-saved');
								} else {
									$redirect_url = '';
								}
							} else {
								$message = validation_first();
							}
						}
					}
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
			$content = view("getstarted::info", array('message' => $message));
		break;
	}
//$content = $app->view("getstarted::welcome/layout");
	return $app->render($content);
}