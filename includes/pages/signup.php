<?php
function signup_pager($app) {
	$val = input("val");
	/*--modification for dob--*/
	if(config('enable-birthdate', true)) {
		if(isset($val['dob_input']) && $val['dob_input'] != "") {
			$dob = explode('/', $val['dob_input']);
			$val['birth_day'] = $dob[1];
			$val['birth_month'] = strtolower($dob[0]);
			$val['birth_year'] = $dob[2];
		}
	}
	/*--modfifcation end--*/
	$message = null;
	load_functions('country');
	$app->setTitle(lang("join-network"));

	if(is_loggedIn()) redirect_to_pager('feed');
	extract(fire_hook("signup.form.check", array(), array('status' => true, 'message' => null)));
	if($val) {
		$val = fire_hook('signup.fields', $val);
		if(fire_hook('users.category.exist', false)) {
			$val['username'] = fire_hook('users.category.username', $val, array($val));
		}
		CSRFProtection::validate();
		fire_hook('signup.submitted', $val, array());
		$tos = input('val.tos');
		if($tos) {
			if(config('enable-captcha') == 2) {
				require_once path('includes/libraries/recaptcha/autoload.php');
				$recaptcha = new \ReCaptcha\ReCaptcha(config('recaptcha-secret'));
				$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);
			}
			if(!config("enable-captcha") or
				(config('enable-captcha') == 1 and strtolower(session_get("sv_captcha")) == strtolower(input('captcha')))
				or (config('enable-captcha') == 2 and $resp->isSuccess())) {
				$rules = array(
					'first_name' => 'required|predefined',
					'last_name' => 'required|predefined',
					'username' => 'required|predefined|alphanum|min:3|username',
					'email_address' => 'required|email|unique:users',
					'password' => 'required|min:6',
					'country' => 'required'
				);
				$rules = fire_hook('signup.rules', $rules);
				if(config('enable-birthdate', true) && input('val.birth_day') && input('val.birth_month') && input('val.birth_year')) {
					$rules['birth_day'] = 'required';
					$rules['birth_month'] = 'required';
					$rules['birth_year'] = 'required';
				}
				if(input('val.password') != input('val.cpassword')) {
					$message = 'Password do not match';
				}
				if (config('enable-gender', true)){
                    $gender = input('val.gender');
                    if(!($gender == 'male' || $gender == 'female' || empty($gender))) {
                        $message = 'Invalid Gender';
                    }
                }
				if(input('val.country') && !is_valid_country(input('val.country'))) {
					$message = 'Invalid Country';
				}
				/*--dob modification--*/
				if(config('enable-birthdate', true)) {
					if(isset($val['dob_input']) && $val['dob_input']) {
						if(!is_numeric($val['birth_day'])) {
							$message = 'Invalid Birth Day';
						}
						$months = config('months');
						if(!array_key_exists($val['birth_month'], $months)) {
							$message = 'Invalid Birth Month';
						}
						$currentYear = date('Y');
						$minAge = config("birthdate-min-age", 10);
						$maxYear = $currentYear - $minAge;
						if($val['birth_year'] > $maxYear) {
							$message = 'Year of birth exceeds the minimum allowed';
						}
					} else if(input('val.birth_day') && input('val.birth_month') && input('val.birth_year')) {
						if(!is_numeric(input('val.birth_day'))) {
							$message = 'Invalid Birth Day';
						}
						$months = config('months');
						if(!array_key_exists(input('val.birth_month'), $months)) {
							$message = 'Invalid Birth Month';
						}
						$currentYear = date('Y');
						$minAge = config("birthdate-min-age", 10);
						$maxYear = $currentYear - $minAge;
						if(input('val.birth_year') > $maxYear) {
							$message = 'Year of birth exceeds the minimum allowed';
						}
					}
				}
				/*--end dob modification--*/

				$fieldRules = array();
				if(input('val.fields')) {
					foreach(get_form_custom_fields('user') as $field) {
						if($field['required']) {
							$fieldRules[$field['title']] = 'required';
						}
					}
				}

				validator(strip_input_slash($val), $rules);
				if($fieldRules) validator(input('val.fields'), $fieldRules);

				if(validation_passes() && !$message) {
					$added = add_user($val);
					if($added) {
						if(isset($val['avatar']) && $val['avatar']) {
							$user_id = $added;
							$avatar = $val['avatar'];
							list($header, $data) = explode(',', $avatar);
							preg_match('/data\:image\/(.*?);base64/i', $header, $matches);
							if(isset($matches[1])) {
								$extension = $matches[1];
								if(!in_array($extension, array('jpg', 'png', 'gif', 'jpeg'))) {
									exit('Invalid Format');
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
									$avatar = $uploader->resize()->toDB("profile-avatar", $user_id, 1)->result();
									update_user_avatar($avatar, $user_id, $uploader->insertedId, false);
								} else {
									$message = $uploader->getError();
								}
							}
						}
						fire_hook("signup.completed", null, array($added, $val));
						fire_hook("user.type.signup.completed", $added);
						if(config('user-activation', false)) {
							//direct
							send_user_welcome_email($val['username']);
							send_user_activation_link($val['username']);
							redirect_to_pager('signup-activate');
						}
						send_user_welcome_email($val['username']);
						$user = find_user($added);
						session_put("sv_loggin_username", $user['id']);
						session_put("sv_loggin_password", $user['password']);
						return go_to_user_home(null, $user);
					}
				} else {
					$message = $message ? $message : validation_first();
				}

			} else {
				$message = lang('invalid-captcha-code');
			}
		} else {
			$message = lang('tos-error');
		}
	}
	$_GET['message'] = $message;
	fire_hook('signup.get.parameters', null, array());
    $signup_page = "signup/content";
    $signup_page = fire_hook("custom.signup.page",$signup_page);
    return $app->render(view($signup_page, array('message' => $message)));
}

function signup_activate_pager($app) {
	if(is_loggedIn()) redirect_to_pager('feed');
	$app->setTitle(lang('activate-email'));

	$hash = input('code');
	if($hash) {
		$verifyHash = mail_hash_valid($hash, true);
		if($verifyHash) {
			activate_user($verifyHash);
			$user = find_user($verifyHash);
			login_with_user($user);
			delete_mail_hash($hash);

			return go_to_user_home();
		}
	}
	$message = "";
	$email = input('email');
	if($email) {
		$send = send_user_activation_link($email);
		if(!$send) {
			$message = lang('failed-to-send-activation-link');
		}
	}
	return $app->render(view('signup/activate', array('message' => $message)));
}

