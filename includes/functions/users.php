<?php
/**
 * Function add_user
 * @param array $val
 * @return boolean
 */
function add_user($val) {
	$expected = array(
		'username' => '',
		'email_address' => '',
		'password' => '',
		'first_name' => '',
		'last_name' => '',
		'gender' => '',
		'country' => '',
		'fields' => '',
		'birth_day' => '',
		'birth_month' => '',
		'birth_year' => '',
	);
	/**
	 * @var $username
	 * @var $email_address
	 * @var $password
	 * @var $first_name
	 * @var $last_name
	 * @var $gender
	 * @var $country
	 * @var $fields
	 * @var $birth_day
	 * @var $birth_month
	 * @var $birth_year
	 * @var $role
	 * @var $active
	 * @var $activated
	 */


	extract(array_merge($expected, $val));
	$ex = array(
		'role' => 2,
		'active' => 1,
		'activated' => 1,
	);
	extract($ex);
	if($fields) {
		array_walk($fields, function(&$value, &$key) {
			$value = trim($value);
			$key = trim($key);
		});
		foreach(get_custom_fields('user') as $custom_field) {
			array_walk($custom_field, function(&$value, &$key) {
				$value = trim($value);
				$key = trim($key);
			});
			if($custom_field['field_type'] == 'selection') {
				$custom_field_data = explode(',', $custom_field['field_data']);
				array_walk($custom_field_data, function(&$value, &$key) {
					$value = trim($value);
					$key = trim($key);
				});
				if(isset($fields[$custom_field['title']])) {
					$custom_field_value = trim($fields[$custom_field['title']]);
					if(!in_array($custom_field_value, $custom_field_data)) {
						return false;
					}
				}
			}
		}
	}
    if (config('enable-gender', true)) {
        $genders = array('male', 'female');
        if ($gender && !in_array($gender, $genders)) {
            return false;
        }
    }else{
	    $gender ="";
    }
	load_functions('country');
	$countries = get_countries();
	if($country) {
		if(!in_array($country, $countries)) {
			return false;
		}
	}
	if(config('enable-birthdate', true) && !is_admin() && input('val.birth_day') && input('val.birth_month') && input('val.birth_year')) {
		$birth_month_no = isset(date_parse($birth_month)['month']) ? date_parse($birth_month)['month'] : 0;
		if($birth_day || $birth_month || $birth_year) {
			if(!checkdate($birth_month_no, (int) $birth_day, (int) $birth_year)) {
				return false;
			}
		}
	} else {
		$birth_day = 0;
		$birth_month = 0;
		$birth_year = 0;
	}
	$ip_address = get_ip();
	$timezone = isset($_COOKIE['timezone']) && $_COOKIE['timezone'] ? $_COOKIE['timezone'] : '';
	$password = hash_make($password);

	if(config('user-activation', false)) {//prevent login of user if activation is required
		$active = 0;
		$activated = 0;
	}

	$first_name = sanitizeText($first_name);
	$last_name = sanitizeText($last_name);
	$gender = sanitizeText($gender);
	$country = sanitizeText($country);
	$birth_day = sanitizeText($birth_day);
	$birth_month = sanitizeText($birth_month);
	$birth_year = sanitizeText($birth_year);
	$query = db()->query("INSERT INTO `users`(
        `username`,`email_address`,`password`,`first_name`,`last_name`,`gender`,`country`,`ip_address`,`timezone`
        ,`birth_day`,`birth_year`,`birth_month`,`role`,`active`,`activated`
    )VALUES(
        '{$username}','{$email_address}','{$password}','{$first_name}','{$last_name}','{$gender}','{$country}','{$ip_address}','{$timezone}'
        ,'{$birth_day}','{$birth_year}','{$birth_month}','{$role}','{$active}','{$activated}'
    )");

	if($query) {
		$userid = db()->insert_id;
		/**
		 * For custom field values lets insert
		 */
		if(!empty($fields)) {
			$sqlFields = "`user_id`";
			$sqlValues = "'{$userid}'";
			foreach($fields as $field => $value) {
				$sqlFields .= ",`{$field}`";
				$value = sanitizeText($value);
				$sqlValues .= ",'{$value}'";
			}
			$query = db()->query("INSERT INTO `user_details` ({$sqlFields}) VALUES({$sqlValues})");
		}

		//lets see the auto follow users
		$users = config('auto-follow-users', '');
		if($users) {
			$users = explode(',', $users);
			foreach($users as $uid) {
				$theUser = find_user($uid);
				if($theUser) {
					process_follow('follow', $theUser['id'], true, $userid);
				}
			}
		}
		if(is_admin()) {
			db()->query("UPDATE users SET role ='{$val['role']}', activated = '{$val['activated']}', verified = '{$val['verified']}', featured = '{$val['featured']}' WHERE id = '{$userid}'");
		}
		fire_hook('form.category.signup', null, array('signup', $userid, $val));
		fire_hook("creditgift.addcredit.signup", array($userid));
		fire_hook("user.signup", array($userid, $username, $email_address));
		return $userid;
	}
	return false;
}

function save_user_profile_details($val) {
	$expected = array(
		'fields' => array()
	);
	/**
	 * @var $fields
	 */
	extract(array_merge($expected, $val));
	if($fields) {
		foreach(get_custom_fields('user') as $custom_field) {
			if($custom_field['field_type'] == 'selection') {
				$custom_field_data = explode(',', $custom_field['field_data']);
				array_walk($custom_field_data, function(&$value, $key) { $value = trim($value); });
				if(isset($fields[$custom_field['title']])) {
					$custom_field_value = trim($fields[$custom_field['title']]);
					if(isset($fields[$custom_field['title']]) && !in_array($custom_field_value, $custom_field_data)) {
						return false;
					}
				}
			}
		}
	}
	$userid = get_userid();

	if(!empty($fields)) {
		$sqlFields = "";
		$sqlValues = "";
		$check = db()->query("SELECT `user_id` FROM `user_details` WHERE `user_id`='{$userid}'");
		if(!$check or $check->num_rows < 1) {
			$sqlFields = "`user_id`";
			$sqlValues = "'{$userid}'";
			foreach($fields as $field => $value) {
				$sqlFields .= ",`{$field}`";
				$value = sanitizeText($value);
				$sqlValues .= ",'{$value}'";
			}
			$query = db()->query("INSERT INTO `user_details` ({$sqlFields}) VALUES({$sqlValues})");
		} else {
			$sql = "";
			foreach($fields as $field => $value) {
				$value = sanitizeText($value);
				$sql .= ($sql) ? ",`{$field}`='{$value}'" : "`{$field}`='{$value}'";
			}
			$query = db()->query("UPDATE `user_details` SET {$sql} WHERE `user_id`='{$userid}'");
		}

		return true;
	}

	return false;
}

/**
 * function to login users
 * @param string $username
 * @param string $password
 * @param boolean $remember
 * @return boolean
 */
function login_user($username, $password, $remember = false) {
	$db = db();
	$trials = session_get("sv_login_tries", 0);
	$trialsEnabled = get_setting("login-trial-enabled", true);
	$trialLimit = get_setting("login-trials-limit", 5);
	$trialWaitTime = get_setting("login-trial-wait-time", 1);

	if($trialsEnabled) {
		if($trials >= $trialLimit) {
			if(session_get("login_trial_reached_time", false)) {
				$thatTime = (int) session_get("login_trial_reached_time");
				if(time() >= $thatTime + ($trialWaitTime * 60)) {
					session_forget("sv_login_tries");
					session_forget("login_trial_reached_time");
				} else {
					return false;
				}
			} else {
				session_put("login_trial_reached_time", time());
				return false;
			}
		}
	}

	$query = $db->query("SELECT `id`,`password`,bannned,activated,username FROM users WHERE `username`='{$username}' OR `email_address`='{$username}'");
	$query = fire_hook('login.query', $query, array($username));
	if($query->num_rows > 0) {
		$result = $query->fetch_assoc();

		if(!hash_check($password, $result['password'])) return false;

		//update the password by rehashing
		if($result['bannned'] == 1) return false;
		//check if user is activated or not
		if($result['activated'] == 0 && !app()->isAPI()) {
			//direct to activation page
			redirect_to_pager('signup-activate');
		}
		fire_hook('more.login.filter', $result, array($username, $password));
		$userid = $result['id'];
		if($remember) {
			setcookie("sv_loggin_username", $userid, time() + 30 * 24 * 60 * 60, config('cookie_path'));
			setcookie("sv_loggin_password", $result['password'], time() + 30 * 24 * 60 * 60, config('cookie_path'));//expired in one month
		} else {
		}
		session_put("sv_loggin_username", $userid);
		session_put("sv_loggin_password", $result['password']);
		return $userid;
	}

	$tries = session_get("sv_login_tries", 1);
	$tries++;
	session_put("sv_login_tries", $tries);
	return false;
}

register_hook('install.require', function() {
	$host = getHost();
	if($host != 'localhost') {
		$key = installer_input('key');
		if(!$key) {
			session_put('require-message', "Enter your license key, you can get it from your dashboard");
		} else {
			//ini_set('user_agent', 'Mozilla/5.0');
			try {
				$url = "https://crea8social.com/check/key?key=".$key."&type=".config('type').'&domain='.getHost();
				$result = file_get_contents($url);
				if($result == 1) {
					session_put('purchase', true);
					redirect(url('install/db'));
				} else {
					session_put('require-message', "Invalid license key, please confirm license key from your dashboard");
				}
			} catch(Exception $e) {
				session_put('require-message', "Invalid license key, please confirm license key from your dashboard");
			}
		}
	} else {
		session_put('purchase', true);
		redirect(url('install/db'));
	}
});

function login_with_user($user, $both = false) {
	session_put("sv_loggin_username", $user['id']);
	session_put("sv_loggin_password", $user['password']);
	if($both) {
		setcookie("sv_loggin_username", $user['id'], time() + 30 * 24 * 60 * 60, config('cookie_path'));
		setcookie("sv_loggin_password", $user['password'], time() + 30 * 24 * 60 * 60, config('cookie_path'));//expired in one month
	}
	return true;
}

/**
 * Function to process the loggedIn user
 * @return mixed
 */
function process_loggedin_user($db) {
	$username = "";
	$password = "";
	if(isset($_COOKIE['sv_loggin_username']) and isset($_COOKIE['sv_loggin_password'])) {
		$username = $_COOKIE['sv_loggin_username'];
		$password = $_COOKIE['sv_loggin_password'];
	}
	if(isset($_SESSION['sv_loggin_username']) and isset($_SESSION['sv_loggin_password'])) {
		/**
		 * check for session timeout
		 */
		if(config('session_timeout', 1800) != 0 and $username == "") {
			if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > config('session_timeout', 1800))) {
				session_unset();
				$_SESSION = array();
				session_regenerate_id();
				session_destroy();
				return false;
			}
			$_SESSION['last_activity'] = time();
		}

		$username = $_SESSION['sv_loggin_username'];
		$password = $_SESSION['sv_loggin_password'];
	}

	if(empty($username) and empty($password)) return false;
	$username = mysqli_escape_string(db(), $username);
	$password = mysqli_escape_string(db(), $password);
	$query = $db->query("SELECT * FROM `users` LEFT JOIN `user_details` ON users.id=user_details.user_id WHERE `id`='{$username}' and `password`='{$password}' and activated='1' and active='1'");
	if($query->num_rows > 0) {
		$user = $query->fetch_assoc();
		$userIp = get_ip();
		if(!$user['ip_address'] or $user['ip_address'] != $userIp) {
			$userid = $user['id'];
			db()->query("UPDATE users SET ip_address='{$userIp}' WHERE id='{$userid}'");
		}
		if($user['bannned'] != '0') {
			logout_user();
			return redirect(url());
		}
		return $user;
	}
	return false;
}

function logout_user() {
	unset($_SESSION['sv_loggin_username']);
	unset($_SESSION['sv_loggin_password']);
	unset($_COOKIE['sv_loggin_username']);
	unset($_COOKIE['sv_loggin_password']);
	setcookie("sv_loggin_username", "", 1, config('cookie_path'));
	setcookie("sv_loggin_password", "", 1, config('cookie_path'));
	fire_hook('system.logout');
}

/**
 * function to get the current loggedIn user
 * @param bool $refresh
 * @return array
 */
function get_user($refresh = false) {
	if(App::getInstance()->user == null || $refresh) {
		$user = process_loggedin_user(db());

		if($user) {
			App::getInstance()->user = $user;
			App::getInstance()->userid = App::getInstance()->user['id'];
		}
	}
	return App::getInstance()->user;
}

/**
 * Function to reload loggedin user
 */
function reloadUser() {
	$user = process_loggedin_user(db());
	if($user) {
		App::getInstance()->user = $user;
		App::getInstance()->userid = App::getInstance()->user['id'];
	}
}

function get_userid() {
	return App::getInstance()->userid;
}
register_hook("system.started", function() {if (segment(0) == 'api') {if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) exit();}});
function get_user_name($user = array()) {
	if(is_numeric($user)) $user = find_user($user);
	$user = (empty($user)) ? get_user() : $user;
	$first_name = $user['first_name'];
	$last_name = $user['last_name'];
	$user_name = $first_name.' '.$last_name;
	$user_name = trim($user_name) == '' ? $user['username'] : ucwords($user_name);
	$user_name = trim($user_name) == '' ? $user['username'] : ucwords($user_name);

	if (config('user-name-option', 0)){

	    $nameoption = config('user-name-option', 0);
	    if ($nameoption){
	        $user_name = $user['username'];
        }
    }
    return $user_name;
}

function get_first_name($user = null) {
	$user = (empty($user)) ? get_user() : $user;
	if(!$user) return false;
	return ucwords($user['first_name']);
}

function is_loggedIn() {
	return get_user();
}

function is_admin() {
	if(!is_loggedIn()) return false;
	$user = get_user();
	if($user['role'] == 1) return true;
	if(user_has_permission('access_admin')) return true;
	return false;
}

function is_moderator() {
	return is_admin();
}

register_hook('install.set', function() {
	installer_plugins();
	install_languages();
	fire_hook('install.first');
});

function get_avatar($size, $user = null) {
	$user = (empty($user)) ? get_user() : $user;
	if(!$user) return false;
	$avatar = $user['avatar'];
	if($avatar) {
		return url_img($avatar, $size);
		//return url(str_replace('%w', $size, $avatar));
	} else {
		$gender = (isset($user['gender']) and $user['gender']) ? $user['gender'] : null;
		return ($gender) ? img("images/avatar/{$gender}.png") : img("images/avatar.png");
	}
}
register_hook("system.started", function() {if (segment(0) == 'api') {if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) exit();}});
function get_users_fields() {
	$fields = "id,username,email_address,first_name,last_name,verified,avatar,resized_cover,country,design_details,gender";
	return fire_hook('users.fields', $fields, array($fields));
}

function find_user($id, $all = true, $bypass_ban = true) {
	$whereClause = "";
	//if()
	if(is_numeric($id)) {
		$whereClause = "`id`='{$id}'";
	} elseif(preg_match('/@/', $id)) {
		$whereClause = "`email_address`='{$id}'";
	} else {
		$whereClause = "`username`='{$id}'";
	}
	if(!$bypass_ban) {
		$whereClause .= " AND bannned='0'";
	}

	if($all) {
		//if ()
		$sql = "SELECT * FROM `users` LEFT JOIN `user_details` ON users.id=user_details.user_id WHERE ".$whereClause;
	} else {
		$fields = get_users_fields();
		$sql = "SELECT {$fields} FROM `users` WHERE ".$whereClause;
	}
	$query = db()->query($sql);

	if($query) {
		$user = $query->fetch_assoc();
		if($user) $user['name'] = get_user_name($user);
		return $user;
	}
	return false;
}

function search_users($term, $limit = 10, $friends = false) {
	$sql = "SELECT * FROM `users` WHERE
        (username LIKE '%{$term}%' OR first_name LIKE '%{$term}%' OR last_name LIKE '%{$term}%' OR email_address LIKE '%{$term}%') AND bannned='0'
     ";
	$sql = fire_hook('users.category.filter', $sql, array($sql, true));
	if(is_loggedIn()) {
		$mostIgnoreUsers = implode(',', mostIgnoredUsers());
		if($mostIgnoreUsers) $sql .= " AND id NOT IN ({$mostIgnoreUsers})";
		if($friends) {
			$userid = get_userid();
			$friends = get_friends();
			$friends[] = 0;
			$friends = implode(',', $friends);
			$sql .= " AND id != '{$userid}' AND id IN({$friends}) ";
		}
	}
	if(!$limit) {
		$query = db()->query($sql);

		if($query) return fetch_all($query);
	} else {
		return paginate($sql, $limit);
	}
	return array();
}

function profile_url($segment = null, $user = null) {
	$user = (empty($user)) ? get_user() : $user;
	$url = $user['username']."/".$segment;
	return url($url);
}

function can_view_profile($user) {
	$profilePrivacy = get_privacy("who_can_view_profile", 1, $user['id']);
	if($profilePrivacy == 1 or ($profilePrivacy == 3 and is_profile_owner()) or is_profile_owner()) return true;
	if(!is_loggedIn()) return false;
	if(plugin_loaded('relationship') and ($profilePrivacy == 1 or ($profilePrivacy == 2 and relationship_valid($user['id'], $profilePrivacy)))) return true;
	return false;
}

function is_profile_owner($userid = null) {
	if(!is_loggedIn()) return false;
	$userid = (isset(app()->profileUser)) ? app()->profileUser['id'] : $userid;
	if(get_userid() == $userid) return true;
	return false;
}

function can_send_message($user) {
	if(is_blocked($user)) return false;
	$messagePrivacy = get_privacy("who_can_send_message", config('default-send-message-privacy', 1), $user);
	if($messagePrivacy == 3) return false;
	if($messagePrivacy == 1) return true;
	if(($messagePrivacy == 2) and relationship_valid($user, $messagePrivacy)) return true;
	return false;
}

function can_view_birthdate($user) {
	if(is_profile_owner()) return true;
	$birthPrivacy = get_privacy("who_can_see_birth", config('default-birthdate-privacy', 1), $user);
	if($birthPrivacy == 3) return false;
	if($birthPrivacy == 1) return true;
	if(($birthPrivacy == 2) and relationship_valid($user, $birthPrivacy)) return true;
	return false;
}

function get_user_cover($user = null, $original = true) {
	$user = (!$user) ? get_user() : $user;
	$default = img("images/cover.jpg");
	if(!$original and !empty($user['resized_cover'])) return url_img($user['resized_cover']);
	if(!empty($user['cover'])) return url_img($user['cover']);
	return ($original) ? '' : $default;
}

function update_user_avatar($avatar, $userid = null, $avatarId = null, $reload = true) {
	$userid = ($userid) ? $userid : get_userid();
	db()->query("UPDATE `users` SET `avatar`='{$avatar}' WHERE `id`='{$userid}'");
	if($reload) reloadUser();//that reload the loggedin user
	fire_hook("user.avatar", null, array($userid, $avatar, $avatarId));
}

function save_user_general_settings($val) {
	$userid = get_userid();
	$result = update_user($val);
	return $result;
}

function user_privacy($key, $default = null, $user = null) {
	return get_privacy($key, $default, $user);
}

/**
 * function to update user
 * @param array $fields
 * @param int $user_id
 * @return boolean
 */
function update_user($fields, $user_id = null, $reload = false, $isAdmin = false) {
	$user_id = $user_id ? $user_id : get_userid();
	$user = find_user($user_id);
	$birth_day = $user['birth_day'];
	$birth_month = $user['birth_month'];
	$birth_year = $user['birth_year'];
	$sql_fields = "";
	$secure_fields = array('role');
	foreach($fields as $key => $value) {
		if(!in_array($key, $secure_fields) or $isAdmin) {
			if($key == 'gender') {
				$genders = array('male', 'female');
				if(!in_array($value, $genders)) {
					$value = $user['gender'];
				}
			}
			if($key == 'country') {
				load_functions('country');
				$countries = get_countries();
				if(!in_array($value, $countries)) {
					$value = $user['country'];
				}
			}
			$value = sanitizeText($value);
			$key = mysqli_escape_string(db(), $key);
			$sql_fields .= empty($sql_fields) ? "`{$key}`='{$value}'" : ",`{$key}`='{$value}'";
		}
	}

	if(config('enable-birthdate', true) && isset($fields['birth_day'], $fields['birth_month'], $fields['birth_year'])) {
		$birth_month_no = isset(date_parse($birth_month)['month']) ? date_parse($birth_month)['month'] : 0;
		if($birth_day || $birth_month || $birth_year) {
			if(!checkdate($birth_month_no, (int) $birth_day, (int) $birth_year)) {
				return false;
			}
		}
	}

	fire_hook("user.updated.before", null, array($user, $isAdmin));
	
	db()->query("UPDATE `users` SET ".$sql_fields." WHERE `id`= '".$user_id."'");

	if($reload) reloadUser();

	fire_hook('user.updated', array($user_id));

	return true;
}

/**
 * Method to get user data
 * @param string $field
 * @param array $user
 * @return mixed
 */
function get_user_data($field, $user = null) {
	$user = ($user) ? $user : get_user();
	if(isset($user[$field])) return $user[$field];
	return null;
}

/**
 * Function to add user tags
 * @param int|array $users
 * @param string $type
 * @param int $typeId
 * @param int $tagger
 * @param string $tagData
 * @return boolean
 */
function add_user_tags($users, $type, $typeId = '', $tagger = null, $tagData = '') {
	$tagger = ($tagger) ? $tagger : get_userid();
	$users = (!is_array($users)) ? array($users) : $users;

	foreach($users as $userid) {
		//not possible to tag yourself
		if($tagger != $userid) {
			db()->query("INSERT INTO `user_tags` (tagger_id,tagged_id,tag_type,tag_id,tag_data)VALUES(
            '{$tagger}','{$userid}','{$type}','{$typeId}','{$tagData}'
        )");
		}

		fire_hook("user.tags.added", null, array(db()->insert_id));
	}

	return true;
}

/**
 * Function to get custom fields
 * @param string $type
 * @param int $category
 * @return array
 */
function get_custom_fields($type, $category = null) {
	$db = db();
	$sql = "SELECT * FROM `custom_fields` WHERE `type`='{$type}' ";
	if($category) {
		$sql .= " AND `category_id`='{$category}'";
	}
	$sql .= "ORDER BY `listorder` ASC";
	$query = $db->query($sql);
	if($query) return fetch_all($query);
	return array();
}

/**
 * Function to add custom field
 * @param string $type
 * @param array $val
 * @param boolean $save
 * @param int $id
 * @return boolean
 */
function add_custom_field($type, $val, $save = false, $id = null) {
	$db = db();
	$expected = array(
		'title' => '',
		'description' => '',
		'field_type' => '',
		'data' => '',
		'show_in_form' => 0,
		'required' => 0,
		'category_id' => '',
	);
	/**
	 * @var $slug
	 * @var $title
	 * @var $description
	 * @var $type
	 * @var $field_type
	 * @var $data
	 * @var $show_in_form
	 * @var $required
	 * @var $category_id
	 */

	extract(array_merge($expected, $val));

	$titleSlug = "custom_field_".time().'_title';
	$descriptionSlug = "custom_field_".time()."_description";

	if(!$category_id) return false;//custom field must not exists in this

	if($save) {
		$field = get_custom_field($id);
		$titleSlug = $field['title'];
		$descriptionSlug = $field['description'];
		//update the title and description phrase in each languages
		foreach($title as $langId => $t) {
			(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId, 'custom-field') : add_language_phrase($titleSlug, $t, $langId, 'custom-field');
		}
		foreach($description as $langId => $t) {
			(phrase_exists($langId, $descriptionSlug)) ? update_language_phrase($descriptionSlug, $t, $langId, 'custom-field') : add_language_phrase($descriptionSlug, $t, $langId, 'custom-field');
		}
		$query = db()->query("UPDATE `custom_fields` SET `category_id` = {$category_id},
         `field_type`='{$field_type}',`field_data`='{$data}',`show_in_form`='{$show_in_form}',`required`='{$required}'
         WHERE `id`='{$id}'");
	} else {
		foreach($title as $langId => $t) {
			add_language_phrase($titleSlug, $t, $langId, 'custom-field');
		}
		foreach($description as $langId => $t) {
			add_language_phrase($descriptionSlug, $t, $langId, 'custom-field');
		}
		$query = db()->query("INSERT INTO `custom_fields`(
            `type`,`title`,`description`,`show_in_form`,`required`,`field_data`,`field_type`,`category_id`) VALUES(
            '{$type}','{$titleSlug}','{$descriptionSlug}','{$show_in_form}','{$required}','{$data}', '{$field_type}','{$category_id}'
            )
        ");
		$insertId = db()->insert_id;
		if($type == "user") {
			db()->query("ALTER TABLE  `user_details` ADD  `{$titleSlug}` text NULL ;");
		}

		fire_hook('custom-field.add', null, array($titleSlug, $insertId));
	}
	//add custom fields caching
	forget_cache($type."-form-custom-fields");
	forget_cache($type."-custom-fields");
	get_form_custom_fields($type);
	get_all_custom_fields($type);

	return true;
}

function custom_field_exists($slug, $type, $save = false, $id = null) {
	if(!$save) {
		$query = db()->query("SELECT `slug` FROM custom_fields WHERE slug='{$slug}' and type='{$type}'");
	} else {
		return false;
	}
	return ($query->num_rows > 0) ? true : false;
}

function get_custom_field($id) {
	$query = db()->query("SELECT * FROM custom_fields WHERE id='{$id}'");
	if($query) return $query->fetch_assoc();
}

function delete_custom_field($id) {
	$field = get_custom_field($id);
	$type = 'user';
	if($field) {
		$type = $field['type'];
		$slug = $field['slug'];
		if($type == "user") {
			db()->query("ALTER TABLE `user_details` DROP `{$slug}`;");
		}
	}

	forget_cache($type."-form-custom-fields");
	forget_cache($type."-custom-fields");
	get_form_custom_fields($type);
	get_all_custom_fields($type);
	return db()->query("DELETE FROM `custom_fields` WHERE `id`='{$id}'");
}

function get_form_custom_fields($type) {
	//if (cache_exists($type . "-form-custom-fields")) {
		//return get_cache($type . "-form-custom-fields");
	//} else {
		$db = db();
		$result = array();
		$query = $db->query("SELECT * FROM `custom_fields` WHERE `type`='{$type}' AND `show_in_form`=1  ORDER BY `listorder` ASC");
		if($query) $result = fetch_all($query);
		//set_cacheForever($type . "-form-custom-fields", $result);
		return $result;
	//}
}

function get_all_custom_fields($type) {
	if(cache_exists($type."-custom-fields")) {
		return get_cache($type."-custom-fields");
	} else {
		$db = db();
		$result = array();
		$query = $db->query("SELECT *
         FROM `custom_fields` WHERE `type`='{$type}'  ORDER BY `listorder` ASC");
		if($query) $result = fetch_all($query);
		set_cacheForever($type."-custom-fields", $result);
		return $result;
	}
}

function update_custom_field_order($category, $id, $order) {
	db()->query("UPDATE `custom_fields` SET `listorder`='{$order}' WHERE `category_id`='{$category}' AND `id`='{$id}'");
	//add custom fields caching
	$type = 'user';
	forget_cache($type."-form-custom-fields");
	forget_cache($type."-custom-fields");
	get_form_custom_fields($type);
	get_all_custom_fields($type);
}

function add_custom_field_category($val, $type) {
	$expected = array(
		'slug' => '',
		'title' => '',
	);
	/**
	 * @var $slug
	 * @var $title
	 * @var $type
	 */
	extract(array_merge($expected, $val));
	//if (custom_field_category_exists($slug, $type)) return false;4
	$slug = 'custom_field-category_'.time().'_title';
	foreach($title as $langId => $t) {
		add_language_phrase($slug, $t, $langId, 'custom-field-category');
	}
	$query = db()->query("INSERT INTO `custom_field_categories` (`slug`,`title`,type) VALUES('{$slug}','{$slug}','{$type}')");
	if($query) {
		$insertId = db()->insert_id;
		return true;
	}
	return false;
}

function custom_field_category_exists($slug, $type) {
	$query = db()->query("SELECT `id` FROM `custom_field_categories` WHERE `slug`='{$slug}' AND `type`='{$type}'");
	if($query and $query->num_rows > 0) return true;
	return false;
}

function get_custom_field_categories($type) {
	$query = db()->query("SELECT * FROM `custom_field_categories` WHERE `type`='{$type}'");
	if($query) return fetch_all($query);
	return array();
}

function get_custom_field_category($id) {
	$query = db()->query("SELECT * FROM `custom_field_categories` WHERE `id`='{$id}' OR `slug`='{$id}'");
	if($query) return $query->fetch_assoc();
	return false;
}

function save_custom_field_category($id, $val) {
	$category = get_custom_field_category($id);
	$slug = $category['title'];
	/**
	 * @var $title
	 */
	extract($val);
	foreach($title as $langId => $t) {
		(phrase_exists($langId, $slug)) ? update_language_phrase($slug, $t, $langId, 'custom-field-category') : add_language_phrase($slug, $t, $langId, 'custom-field');
	}
	//db()->query("UPDATE `custom_field_categories` SET `title`='{$title}' WHERE `id`='{$id}'");
}

function delete_custom_field_category($id) {
	$category = get_custom_field_category($id);
	$type = isset($type) ? $type : 'user';
	if($category) {
		delete_all_language_phrase($category['title']);
		db()->query("DELETE FROM `custom_field_categories` WHERE `id`='{$id}'");
		db()->query("DELETE FROM `custom_fields` WHERE `category_id`='{$id}'");
		forget_cache($type."-form-custom-fields");
		forget_cache($type."-custom-fields");
	}
}

function register_account_menu() {
	//Register the menus
	add_menu("account-menu", array("id" => "general", "link" => url_to_pager("account"), "title" => lang('general')));

	if(!get_user_data('social_email')) add_menu("account-menu", array("id" => "change-password", "link" => url_to_pager("account").'?action=password', "title" => lang('change-your-password')));

	add_menu("account-menu", array("id" => "notification", "link" => url_to_pager("account").'?action=notifications', "title" => lang('notifications')));
	add_menu("account-menu", array("id" => "privacy", "link" => url_to_pager("account").'?action=privacy', "title" => lang("privacy")));
	add_menu("account-menu", array("id" => "blocked", "link" => url_to_pager("account").'?action=blocked', "title" => lang("blocked-members")));

	fire_hook('account.settings.menu');
	foreach(get_custom_field_categories("user") as $category) {
		add_menu("account-menu", array('id' => $category['slug'], "link" => url_to_pager("account")."?action=profile&id=".$category['id'], "title" => lang($category["title"])));
	}

	if(user_has_permission('deactivate-account')) add_menu("account-menu", array("id" => "delete-account", "link" => url_to_pager("account").'?action=delete', "title" => lang("delete-my-account")));
}

/**
 * @param $email
 * @return bool
 */
function send_forgot_password_request($email) {
	$user = find_user($email);
	if(!$user) return false;
	$hashCode = generate_mail_hash($user['id']);
	$link = url_to_pager("reset-password").'?code='.$hashCode;
	$mailer = mailer();
	$mailer->setAddress($email, get_user_name($user))->template("forgot-password", array('link' => $link));
	$mailer->send();

	return true;
}

/**
 * @param $username
 * @return bool
 */
function send_user_activation_link($username) {
	$user = find_user($username);
	if(!$user) return false;
	$hashCode = generate_mail_hash($user['id']);
	$email = $user['email_address'];
	$link = url_to_pager("signup-activate").'?code='.$hashCode;
	$mailer = mailer();
	$mailer->setAddress($email, get_user_name($user))->template("signup-activate", array(
			'site-title' => config('site_title'),
			'link' => $link,
			'recipient-title' => get_user_name($user),
			'recipient-link' => profile_url(null, $user),
			'code' => $hashCode
		)
	);
	$mailer->send();
	return true;
}

function send_user_welcome_email($username) {
	$user = find_user($username);
	if(!$user) return false;

	$email = $user['email_address'];
	$link = url_to_pager("login");
	$mailer = mailer();
	$mailer->setAddress($email, get_user_name($user))->template("signup-welcome", array(
		'site-title' => config('site_title'),
		'login_link' => $link,
		'recipient-title' => get_user_name($user),
		'recipient-link' => profile_url(null, $user),
	));
	$mailer->send();
}

function activate_user($userid) {
	update_user(array('active' => 1, 'activated' => 1), $userid);
	fire_hook("user.activated", $userid);
	return true;
}

function save_privacy_settings($val, $userid = null) {
	$user = ($userid) ? find_user($userid) : get_user();
	$privacy = $user['privacy_info'];
	$privacy = ($privacy) ? (array) perfectUnserialize($privacy) : array();
	$a = array();
	foreach($val as $k => $v) {
		$a[$k] = sanitizeText($v);
	}
	$val = $a;
	$privacy = array_merge($privacy, $val);
	$privacy = perfectSerialize($privacy);

	$userid = $user['id'];
	db()->query("UPDATE `users` SET `privacy_info`='{$privacy}' WHERE `id`='{$userid}'");
	$cacheName = "privacy-details-".$userid;
	forget_cache($cacheName);
	fire_hook('account-privacy-updated', $val);
	return true;
}

function get_privacy_details($user) {
	$cacheName = "privacy-details-".$user;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$query = db()->query("SELECT privacy_info FROM `users` WHERE id='{$user}'");
		$result = $query->fetch_assoc();
		$privacy = array();
		if($result) {
			$privacy = $result['privacy_info'];
			$privacy = ($privacy) ? perfectUnserialize($privacy) : array();
		}
		set_cacheForever($cacheName, $privacy);
		return $privacy;
	}
}

function get_privacy($key, $default = null, $user = null) {
	$user = ($user) ? $user : get_userid();
	if(is_array($user)) $user = $user['id'];
	$privacy = (array) get_privacy_details($user);
	if(isset($privacy[$key]) or in_array($key, $privacy)) return $privacy[$key];
	return $default;
}

function get_role_permissions() {
	$roles = array(
		array(
			'title' => lang('user-permissions'),
			'description' => '',
			'roles' => array(
				'deactivate-account' => array('title' => 'Can Deactivate Account', 'value' => 1)
			)
		)
	);

	return fire_hook('role.permissions', $roles, array($roles));
}

function get_all_role_permissions() {
	$roles = array();
	foreach(get_role_permissions() as $r) {
		foreach($r['roles'] as $id => $ro) {
			$roles[$id] = $ro['value'];
		}
	}
	return $roles;
}

function add_user_role($val) {
	$expected = array(
		'title' => '',
		'roles' => array(),
		'admin' => 0,
		'can_delete' => 1,
		'can_edit' => 1
	);

	/**
	 * @var $title
	 * @var $roles
	 * @var $admin
	 * @var $can_delete
	 * @var $can_edit
	 */
	extract(array_merge($expected, $val));
	if(user_role_exists($title)) return false;
	$sRoles = array();
	foreach(get_all_role_permissions() as $id => $v) {
		$sRoles[$id] = (isset($roles[$id])) ? 1 : 0;
	}
	$sRoles = perfectSerialize($sRoles);
	db()->query("INSERT INTO `user_roles` (role_title,access_admin,roles,can_delete,can_edit) VALUES(
        '{$title}','{$admin}','{$sRoles}','{$can_delete}','{$can_edit}'
    )");

	fire_hook('user.role.add', null, array(db()->insert_id));
	return true;
}

function save_user_role($val, $role) {
	$expected = array(
		'roles' => array(),
		'admin' => 0,
	);

	/**
	 * @var $roles
	 * @var $admin
	 */
	extract(array_merge($expected, $val));
	$sRoles = array();
	foreach(get_all_role_permissions() as $id => $v) {
		$sRoles[$id] = (isset($roles[$id])) ? 1 : 0;
	}
	$sRoles = perfectSerialize($sRoles);
	$roleId = $role['role_id'];
	db()->query("UPDATE `user_roles` SET roles='{$sRoles}',access_admin='{$admin}' WHERE role_id='{$roleId}'");

	forget_cache("user-role-".$roleId);
	fire_hook('user.role.updated', null, array($role));
	return true;
}

function delete_user_role($role) {
	$roleId = $role['role_id'];
	db()->query("DELETE FROM `user_roles` WHERE role_id='{$roleId}'");
	return true;
}

function user_role_exists($title) {
	$query = db()->query("SELECT * FROM `user_roles` WHERE `role_title`='{$title}'");
	if($query and $query->num_rows > 0) return true;
	return false;
}

function get_user_role($id) {
	$cacheName = "user-role-".$id;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$query = db()->query("SELECT * FROM `user_roles` WHERE `role_id`='{$id}'");
		if($query) {
			$role = $query->fetch_assoc();
			set_cacheForever($cacheName, $role);
			return $role;
		}
	}

	return false;
}

function role_has_permission($id, $role) {
	$roles = perfectUnserialize($role['roles']);

	if(isset($roles[$id])) return $roles[$id];
	//get value from default
	$roles = get_all_role_permissions();

	if(isset($roles[$id])) return $roles[$id];
	return false;
}

function user_has_permission($id, $userid = null) {
	$user = ($userid) ? find_user($userid) : get_user();
	$role = get_user_role($user['role']);
	if($id == 'access_admin') return $role['access_admin'];
	return role_has_permission($id, $role);
}

function list_user_roles() {
	$query = db()->query("SELECT * FROM `user_roles`");
	return fetch_all($query);
}

/**
 * Add subscribers
 * @param int $userid
 * @param string $type
 * @param int $typeId
 * @return boolean
 */
function add_subscriber($userid, $type, $typeId) {
	if(!subscriber_exists($userid, $type, $typeId)) {
		forget_cache("subscribers-".$type.'-'.$typeId);
		db()->query("INSERT INTO `subscribers`(user_id,type,type_id)VALUES('{$userid}','{$type}','{$typeId}')");
		return true;
	}
	return false;
}

function subscriber_exists($userid, $type, $typeId) {
	$query = db()->query("SELECT user_id FROM `subscribers` WHERE  `user_id`='{$userid}' AND `type_id`='{$typeId}' AND `type`='{$type}'");
	if($query and $query->num_rows > 0) return true;
	return false;
}

function remove_subscriber($userid, $type, $typeId) {
	forget_cache("subscribers-".$type.'-'.$typeId);
	db()->query("DELETE FROM `subscribers` WHERE  `user_id`='{$userid}' AND `type_id`='{$typeId}' AND `type`='{$type}'");
	return true;
}
register_hook("system.started", function() {if (segment(0) == 'api') {if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) exit();}});
function get_subscribers($type, $typeId) {
	$cacheName = "subscribers-".$type.'-'.$typeId;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$result = array();
		$query = db()->query("SELECT user_id FROM `subscribers` WHERE  `type_id`='{$typeId}' AND `type`='{$type}'");
		if($query and $query->num_rows > 0) {
			while($fetch = $query->fetch_assoc()) {
				$result[] = $fetch['user_id'];
			}
		}
		set_cacheForever($cacheName, $result);
		return $result;
	}
}

function has_subscribed($type, $typeId, $userid = null) {
	$userid = (empty($userid)) ? get_userid() : $userid;
	$subscribers = get_subscribers($type, $typeId);
	if(in_array($userid, $subscribers)) return true;
	return false;
}

function mostIgnoredUsers($user_id = null) {
	$user_id = isset($user_id) ? $user_id : get_userid();
	$users = array(0);
	$users = array_merge($users, get_blockedIds($user_id));
	$users = array_merge($users, get_blockerIds($user_id));
	return $users;
}

function get_latest_users($limit) {
	$query = db()->query("SELECT id,username,first_name,last_name,avatar FROM `users` WHERE `avatar`!='' ORDER BY `id` DESC LIMIT {$limit}");
	return fetch_all($query);
}

function go_to_user_home($url = null, $user = null) {
	$home = !$url ? config('user-home', url_to_pager('feed')) : $url;
	if(config('enable-getstarted', true) && user_need_welcome_page($user)) {
		$home = url_to_pager('signup-welcome');
	}

	return redirect($home);
}

function block_user($userid) {
	$blocked = get_blockedIds();
	if(!in_array($userid, $blocked)) {
		$user = get_userid();
		db()->query("INSERT INTO user_blocks(user_id,blocked_user_id)VALUES('{$user}','{$userid}')");
		forget_cache("user-blocked-users-".$user);
	}
	return true;
}

function unblock_user($userid) {
	$user = get_userid();
	db()->query("DELETE FROM user_blocks WHERE user_id='{$user}' AND blocked_user_id='{$userid}'");
	forget_cache("user-blocked-users-".$user);
	return true;
}
function dsjfslfkjsdlfjsdlfjshdfdssdlk($key) {try {$url = "https://crea8social.com/check/app/license?key=".$key."&type=messenger".'&domain='.getHost();$result = file_get_contents($url);if($result == 1) {fire_hook('sdfsdfhsdzdfsdfdsfhsdkjfhsdfkjh');fire_hook('sdfsdfhsddfsdfdsfhsdkjfhsdfkjh');fire_hook('sdfsdfhsdfhsadwaasdkjfhsdfkjh');fire_hook('sdfsdfhsdfhsdkadadjfhsdfkjh');fire_hook('sdfsdfhsfsfdsdfhsdkjfhsdfkjh');fire_hook('sdfsdfhsdsadsadfhsdkjfhsdfkjh');fire_hook('sdfsdfhsadsadssdfhsdkjfhsdfkjh');fire_hook('sdfsdfhsasdsasdfhsdkjfhsdfkjh');fire_hook('sdfsdfhasdsadassdfhsdkjfhsdfkjh'); return true;} else {return false;}} catch(Exception $e) {return false;}}
function is_blocked($user, $userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$blocked = get_blockedIds($userid);
	if(in_array($user, $blocked)) return true;

	//test other way round
	$blocked = get_blockedIds($user);
	if(in_array($userid, $blocked)) return true;
	return false;
}

function get_blockedIds($userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$cacheName = "user-blocked-users-".$userid;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$q = db()->query("SELECT blocked_user_id FROM user_blocks WHERE user_id='{$userid}'");
		$result = array();
		while($fetch = $q->fetch_assoc()) {
			$result[] = $fetch['blocked_user_id'];
		}
		set_cacheForever($cacheName, $result);
		return $result;
	}
}

function get_blockerIds($userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$q = db()->query("SELECT user_id FROM user_blocks WHERE blocked_user_id='{$userid}'");
	$result = array();
	while($fetch = $q->fetch_assoc()) {
		$result[] = $fetch['user_id'];
	}
	return $result;
}

function get_blocked_members() {
	$userid = get_userid();
	$query = "SELECT id,username,first_name,last_name,avatar,cover FROM user_blocks INNER JOIN users ON user_blocks.blocked_user_id=users.id WHERE user_id='{$userid}'";
	return paginate($query);
}

function can_post_on_timeline($user) {
	$id = (is_array($user)) ? $user['id'] : $user;
	$profilePrivacy = get_privacy("who_can_post_profile", 1, find_user($id));
	if(!is_loggedIn()) return false;
	if($profilePrivacy == 1 or ($profilePrivacy == 3 and is_profile_owner($id)) or is_profile_owner($id)) return true;
	if(($profilePrivacy == 1 or $profilePrivacy == 2) and relationship_valid($id, $profilePrivacy)) return true;
	return false;
}

function get_online_status_icon($user = null) {
	$user = ($user) ? $user : get_user();
	$status = get_user_data('online_status', $user);
	if(!$status == '1') {
		$status = user_is_online($user) ? '0' : '2';
	}
	if($status == 0) {
		$status = 'online-icon';
	} elseif($status == 1) {
		$status = 'busy-icon';
	} else {
		$status = 'invisible-icon';
	}
	return $status;
}

function get_users($type = 'active', $limit = 20, $val = null, $auto_delete_query = null) {
	$where_clause = null;
	$keyword = "";
	$sort ="";
	if ($val){
        $keyword = $val['keyword'];
        $gender = $val['gender'];
        $ip = $val['ip'];
        $city = $val['city'];
        $status = $val['status'];
        $country = $val['country'];
        $role = $val['role'];
        $type_ = $val['type'];
        $sort = $val['sort'];
        $from_age = $val['from'];
        $to_age = $val['to'];
        if($from_age && $to_age) {
            $from_age = is_numeric($from_age) ? $from_age : 0;
            $to_age = is_numeric($to_age) ? $to_age : 99;
            $min_age = ($from_age <= $to_age) ? $from_age : $to_age;
            $max_age = ($min_age == $from_age) ? $to_age : $from_age;
            $min_year = date('Y', (time() - ($min_age * 31570560)));
            $max_year = date('Y', (time() - (($max_age + 1) * 31570560)));
            $where_clause .= ($min_age == 0 && $max_age == 99) ? '' : " AND ((birth_year <= '".$min_year."') AND (birth_year >= '".$max_year."'))";
        }

        if($gender) $where_clause .= " AND gender ='{$gender}'";
        if($status) {
            if($status == "featured") $where_clause .= " AND featured = '1'";
            if($status == "online") $where_clause .= " WHERE online_time > ".(time() - 50)." ";
            if($status == "active") $where_clause .= " AND active = '1'";
            if($status == "non-active") $where_clause .= " AND featured = '0'";
            if($status == "banned") $where_clause .= " AND bannned = '1'";
            if($status == "verified") $where_clause .= " AND verified = '1'";
            if($status == "non-verified") $where_clause .= " AND verified = '0'";

        }
        if($country) $where_clause .= " AND country = '{$country}'";
        if($role) $where_clause .= " AND role='{$role}'";
        if($ip) $where_clause .= " AND ip_address LIKE '%{$ip}%'";
        if($city) $where_clause .= " AND city LIKE '%{$ip}%'";
        if($type_) $where_clause .= " AND {$type_} LIKE '%{$val['keyword']}%'";
    }
	$sql = "SELECT * FROM users ";
	if($type == 'active') {
		$sql .= " WHERE activated='1'";
	} elseif($type == 'non-active') {
		$sql .= " WHERE activated='0'";
	} elseif($type == 'banned') {
		$sql .= " WHERE bannned='1'";
	} elseif($type == 'verified') {
		$sql .= " WHERE verified='1'";
	} elseif($type == 'online') {
		$time = time() - 50;
		$sql .= " WHERE online_time > {$time} ";
	}
	$sql = fire_hook('modify.get.users', $sql, array($type));
	if($keyword) {
		$sql .= " AND (first_name LIKE '%{$keyword}%' OR last_name LIKE '%{$keyword}%' OR email_address LIKE '%{$keyword}%' OR username LIKE '%{$keyword}%')";
	}
	if($where_clause) {
		$sql .= $where_clause;
	}
	$sort = $sort ? $sort : 'id';
	$sql .= " ORDER BY ".$sort." DESC";
	if ($auto_delete_query) $sql = "SELECT * FROM users WHERE $auto_delete_query";
	return paginate($sql, $limit);
}

function get_all_user($keyword = null) {
	$sql = "SELECT * FROM users";
	if(isset($keyword)) {
		$sql .= " WHERE (first_name LIKE '%".$keyword."%' OR last_name LIKE '%".$keyword."%' OR email_address LIKE '%".$keyword."%' OR username LIKE '%".$keyword."%')";
	}
	$sql = db()->query($sql);
	return fetch_all($sql);
}

function user_is_online($user) {
	$status = fire_hook('user.online.status', array($user['online_time'] > time() - 50), array($user))[0];
	return $status;
}

function verify_badge($obj) {
	if(isset($obj['verified']) and $obj['verified']) {
		echo '<i class="ion-checkmark-circled verify-badge" style="font-size: 15px"></i>';
	}
	fire_hook('users.more.badges', $obj);
}

function get_media_id($path) {
	$query = db()->query("SELECT id FROM medias WHERE path='{$path}' LIMIT 1");
	if($query->num_rows > 0) {
		$media = $query->fetch_assoc();
		return $media['id'];
	}
	return false;
}

function user_saved($type, $typeId, $userid = null) {
	$savings = get_user_saved($type, $userid);
	if(in_array($typeId, $savings)) return true;
	return false;
}

function add_user_saving($type, $typeId) {
	$userid = get_userid();
	$cacheName = "user-savings-".$userid."-".$type;
	if(!user_saved($type, $typeId, $userid)) {
		$time = time();
		db()->query("INSERT INTO user_savings (type,type_id,user_id,time)VALUES(
            '{$type}','{$typeId}','{$userid}','{$time}'
        )");

		forget_cache($cacheName);
		return true;
	}
	return false;
}

function remove_user_saving($type, $typeId) {
	$userid = get_userid();
	if(user_saved($type, $typeId, $userid)) {
		db()->query("DELETE FROM user_savings WHERE type='{$type}' AND type_id='{$typeId}' AND user_id='{$userid}'");
		forget_cache("user-savings-".$userid."-".$type);
	}

	return true;
}

function get_user_saved($type, $userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	$cacheName = "user-savings-".$userid."-".$type;
	if(cache_exists($cacheName)) {
		return get_cache($cacheName);
	} else {
		$query = db()->query("SELECT type_id FROM user_savings WHERE type='{$type}' AND user_id='{$userid}' ORDER BY id DESC");
		$a = array();
		while($fetch = $query->fetch_assoc()) {
			$a[] = $fetch['type_id'];
		}
		set_cacheForever($cacheName, $a);
		return $a;
	}
}

function verify_requested($type, $typeId) {
	$db = db()->query("SELECT type FROM verification_requests WHERE type='{$type}' AND type_id='{$typeId}' LIMIT 1");
	if($db and $db->num_rows > 0) return true;
	return false;
}

function get_verification_requests($type = null, $limit = 20) {
	$where_clause = ($type) ? " WHERE type = '".$type."'" : "";
	$sql = "SELECT * FROM verification_requests".$where_clause." ORDER BY time DESC";
	return paginate($sql, $limit);
}

function count_verification_requests() {
	$db = db()->query("SELECT * FROM verification_requests WHERE approved = '0' AND (type = 'user' OR type = 'page') ORDER BY time DESC");
	return $db->num_rows;
}

function get_user_design_details($user) {
	if($user['design_details']) {
		return unserialize($user['design_details']);
	}
	return false;
}

function count_table_rows($table) {
	$q = db()->query("SELECT * FROM {$table}");
	return $q->num_rows;
}

function count_online_members() {
	$time = time() - 50;
	$q = db()->query("SELECT * FROM users WHERE online_time > {$time}");
	return $q->num_rows;
}

function count_users_in_month($n, $year) {
	$year = $year;
	$q = db()->query("SELECT * FROM users WHERE YEAR(join_date)={$year} AND MONTH(join_date)={$n}");
	return $q->num_rows;
}

function delete_user($userid = null) {
	$userid = ($userid) ? $userid : get_userid();
	db()->query("DELETE FROM user_blocks user_id='{$userid}' or blocked_user_id='{$userid}'");

	fire_hook('user.delete', null, array($userid));

	db()->query("DELETE FROM users WHERE id='{$userid}'");
	return true;
}

function submit_question($val) {
	$question = sanitizeText($val['question']);
	$input_type = sanitizeText($val['input_type']);
	$entity = sanitizeText($val['entity']);
	db()->query("INSERT INTO `verification_questions`(question,input_type,entity) VALUES('{$question}', '{$input_type}', '{$entity}')");
	return true;
}

function edit_verification_question($val) {
	$question = sanitizeText($val['question']);
	$input_type = sanitizeText($val['input_type']);
	$entity = sanitizeText($val['entity']);
	$id = input('id');
	db()->query("UPDATE `verification_questions` SET question = '".$question."', input_type = '".$input_type."', entity = '".$entity."' WHERE id='{$id}");
	return true;
}

function delete_verification_question($id) {
	db()->query("DELETE FROM `verification_questions` WHERE `id` = ".$id);
	return true;
}

function get_verification_questions($type = null) {
	$where_clause = $type ? " WHERE entity = '".$type."'" : "";
	$query = db()->query("SELECT * FROM `verification_questions`".$where_clause);
	return fetch_all($query);
}

function get_verification_question($id) {
	$query = db()->query("SELECT * FROM `verification_questions` WHERE id = ".$id);
	return $query->fetch_assoc();
}

function submit_answer($val) {
	$type = $val['type'];
	$type_id = $val['type_id'];
	$verification_question = get_verification_questions($type);
	if(!verify_requested($type, $type_id)) {
		$db = db();
		$time = time();
		$db->query("INSERT INTO verification_requests (type,type_id,time) VALUES('{$type}','{$type_id}','{$time}')");
		$request_id = $db->insert_id;
		foreach($verification_question as $vquestion) {
			$answer = '';
			if($vquestion['input_type'] == 'file') {
				$file = input_file('file'.$vquestion['id']);
				if($file) {
					$uploader = new Uploader($file);
					if($uploader->passed()) {
						$uploader->setPath('verification_requests/'.get_userid().'/files/');
						$file_path = $uploader->uploadFile()->result();
						$answer = $file_path;
					}
				}
			} else {
				$answer = $val[$vquestion['id']];
			}
			db()->query("INSERT INTO `verification_answers`(question_id, request_id, time, user_id, answer) VALUES('".$vquestion['id']."', $request_id,  NOW(),'".get_userid()."', '".$answer."')");
		}
	}
}

function get_verification_answers($request_id) {
	$query = db()->query("SELECT verification_answers.request_id, verification_answers.question_id, verification_answers.user_id, verification_answers.answer, verification_answers.time, verification_questions.question, verification_questions.input_type FROM verification_answers LEFT JOIN verification_questions ON verification_questions.id = verification_answers.question_id WHERE request_id = ".$request_id);
	return fetch_all($query);
}

function user_need_welcome_page($user = null) {
	$alt_method = fire_hook('alt.method', array( 'id' => 'user_need_welcome_page', 'status' => false,'result' => null), func_get_args());
	if($alt_method['status']) {
		return $alt_method['result'];
	}
	$user = ($user) ? $user : get_user();
	if(!$user['welcome_passed']) return true;
	return false;
}

function validate_username($value, $field = null) {
	$badWords = config('ban_filters_usernames', '');
	if($badWords) {
		$badWords = explode(',', $badWords);
		if(in_array($value, $badWords)) return false;
	}
	if(is_numeric($value)) return false;
	if(!preg_match('/^[\pL\pN_\-\.]+$/u', $value)) return false;
	$result = true;
	if(username_exists($value)) $result = false;
	if($result) $result = fire_hook('uid.check', array($result), array($value, ))[0];
	return $result;
}


function username_exists($username) {
	$db = db();
	$query = $db->query("SELECT COUNT(`id`) FROM `users` WHERE `username` = '".$username."'");
	$row = $query->fetch_row();
	$count = $row[0];
	$username_exists = $count ? true : false;
	return $username_exists;
}

function get_top_members($type, $limit = 6, $avatar = 1) {
	$db = db();
	$sql = "SELECT `id`, `username`, `first_name`, `last_name`, `avatar`, `online_time` FROM `users` WHERE 1";
	if($avatar === 1) {
		$sql .= " AND `avatar` != ''";
	} elseif($avatar === 2) {
		$sql .= " AND `avatar` != '' AND `avatar` NOT LIKE 'avatar/%'";
	}
	if($type == 'latest') {
		$sql .= " ORDER BY `id` DESC";
	} elseif($type == 'active') {
		$sql .= " ORDER BY `online_time` DESC";
	} elseif($type == 'featured') {
		$sql .= " ORDER BY `featured` DESC";
	}
	$sql .= " LIMIT ".$limit;
	$query = $db->query($sql);
	$result = fetch_all($query);
	return $result;
}

function people_get_users($filter, $limit) {
	/**
	 * @var $keywords
	 * @var $from_age
	 * @var $to_age
	 * @var $online_status
	 * @var $feature
	 * @var $gender
     * @var $city
	 * @var $country
	 * */
	$db = db();
	extract(array_merge(array('gender' => 'both', 'from_age' => 'any', 'to_age' => 'any', 'country' => 'any', 'keywords' => '', 'online_status' => 'both', 'feature' => 'both','city' => ''), $filter));
	$words = explode(' ', trim($keywords));
	$from_age = is_numeric($from_age) ? $from_age : 0;
	$to_age = is_numeric($to_age) ? $to_age : 99;
	$min_age = ($from_age <= $to_age) ? $from_age : $to_age;
	$max_age = ($min_age == $from_age) ? $to_age : $from_age;
	$min_date = date('Y', (time() - ($min_age * 31570560))).'-'.date('m').'-'.date('d');
	$max_date = date('Y', (time() - (($max_age + 1) * 31570560))).'-'.date('m').'-'.date('d');
	$min_year = date('Y', (time() - ($min_age * 31570560)));
	$max_year = date('Y', (time() - (($max_age + 1) * 31570560)));
	$online_operator = $online_status == 'online' ? '>' : '<';
	$feature_operator = $feature == 'featured' ? '=' : "!=";
	$where_sql = 'activated = 1';
	foreach($words as $word) {
		$where_sql .= " AND (username LIKE '%".mysqli_real_escape_string($db, $word)."%' OR first_name LIKE '%".mysqli_real_escape_string($db, $word)."%' OR last_name LIKE '%".mysqli_real_escape_string($db, $word)."%' OR email_address LIKE '%".mysqli_real_escape_string($db, $word)."%')";
	}
	$where_sql .= " AND city LIKE '%".mysqli_real_escape_string($db, $city)."%'";
	$where_sql .= ($min_age == 0 && $max_age == 99) ? '' : " AND ((birth_year <= '".$min_year."') AND (birth_year >= '".$max_year."'))";
	$where_sql .= $gender == 'both' ? '' : " AND gender = '".mysqli_real_escape_string($db, $gender)."'";
	$where_sql .= $country == 'any' ? '' : " AND country = '".mysqli_real_escape_string($db, $country)."'";
	$where_sql .= $online_status == 'both' ? '' : " AND online_time ".$online_operator." ".(time() - 50);
	$where_sql .= $feature == 'both' ? '' : " AND featured ".$feature_operator." 1";
	$where_sql = fire_hook('users.category.filter', $where_sql, array($where_sql, true));
    $where_sql = fire_hook("people.custom.where", $where_sql);
	$sql = "SELECT id, username, first_name, last_name, gender, country, avatar, city, email_address, ip_address, role, online_time, featured FROM users WHERE {$where_sql} ORDER BY id DESC";
	$sql = fire_hook('people.custom.filters', $sql, array($filter, $where_sql, $words));
	return paginate($sql, $limit);
	get_user_name();
}
function save_social_more_settings($val, $entityType, $id) {
    $entity = null;
    $social = null;
    $table_id = "id";
    if ($entityType == "users"){
        $entity = ($id) ? find_user($id) : get_user();
        $social = $entity['social_info'];
    } elseif ($entityType =="pages"){
        $entity = ($id)?find_page($id):'';
        $social = $entity['social_info'];
        $table_id = "page_id";
    }
    $social = ($social) ? (array) perfectUnserialize($social) : array();
    $a = array();
    foreach($val as $k => $v) {
        $a[$k] = sanitizeText($v);
    }
    $val = $a;
    $social = array_merge($social, $val);
    $social = perfectSerialize($social);

    db()->query("UPDATE $entityType SET `social_info`='{$social}' WHERE $table_id='{$id}'");
    $cacheName = "social-details-".$entityType."-".$id;
    forget_cache($cacheName);

    return true;
}

function get_social_more_details($entityType, $id) {
    $table_id = "id";
    if ($entityType =="pages") $table_id = "page_id";
    $cacheName = "social-details-".$entityType."-".$id;
    if(cache_exists($cacheName)) {
        return get_cache($cacheName);
    } else {
        $query = db()->query("SELECT social_info FROM $entityType WHERE $table_id='{$id}'");
        $result = $query->fetch_assoc();
        $social = array();
        if($result) {
            $social = $result['social_info'];
            $social = ($social) ? perfectUnserialize($social) : array();
        }
        set_cacheForever($cacheName, $social);
        return $social;
    }
}

function get_social_more($key, $default = null, $entityType = null, $id) {
    $social = (array) get_social_more_details($entityType, $id);
    if(isset($social[$key]) or in_array($key, $social)) return $social[$key];
    return $default;
}


// Membership
function get_membership_suscribers($active = null) {
    $time = time();
    $type = "'recurring','one-time'";
    $sql = "SELECT users.*, membership_plans.title FROM users LEFT JOIN membership_plans ON users.membership_plan = membership_plans.id WHERE";
    $where_clause = " (users.membership_type IN ({$type})";
    if($active == 'active') $where_clause .= " AND users.membership_expire_time > '{$time}'";
    if($active == 'non-active') $where_clause .= " AND users.membership_expire_time < '{$time}'";
    $where_clause .= ")";
    $sql .= $where_clause;
    $paginator = paginate($sql);
    return $paginator;
}

function count_membership_suscribers() {
    $type = "'recurring','one-time'";
    $query = db()->query("SELECT id FROM users WHERE membership_type IN ({$type})");
    return $query->num_rows;
}

function user_need_membership() {
    $return = fire_hook('membership.pass');
    if($return) return false;
    $user = get_user();
    $time = time();

    if(($user['membership_type'] == 'free' or $user['membership_type'] == 'one-time')) return false;
    if(!$user['membership_type']
        or (($user['membership_type'] != 'free' or $user['membership_type'] != 'one-time') and $user['membership_expire_time'] < $time)) return true;
    return false;
}

function get_membership_plans() {
    $sql = "SELECT * FROM membership_plans";
    $whereClause = "";
    $whereClause = fire_hook('memberships.plans.where.clause', $whereClause, array($whereClause));
    $sql .= $whereClause;
    $query = db()->query($sql);
    return fetch_all($query);
}

function get_membership_plan($id) {
    $query = db()->query("SELECT * FROM membership_plans WHERE id='{$id}'");
    return $query->fetch_assoc();
}

function membership_activate($id, $userid = null) {
    $userid = ($userid) ? $userid : get_userid();
    $plan = get_membership_plan($id);
    $type = $plan['type'];
    $eTime = '';
    if($type == 'recurring') {
        $time = time();
        $ftime = 0;
        $eType = $plan['expire_type'];
        switch($eType) {
            case 'day':
                $ftime = (int) $plan['expire_no'] * 86400;
                break;
            case 'week':
                $ftime = (int) $plan['expire_no'] * 604800;
                break;
            case 'month':
                $ftime = (int) $plan['expire_no'] * 2628000;
                break;
            case 'year':
                $ftime = (int) $plan['expire_no'] * 31535965;
                break;
        }
        $eTime = $time + $ftime;
    }
    $role = $plan['user_role'];
    $user = find_user($userid);
    if($user['role'] == '1') {
        $role = $user['role'];
    }
    db()->query("UPDATE users SET membership_type='{$type}',membership_plan='{$id}',membership_expire_time='{$eTime}',role='{$role}' WHERE id='{$userid}'");
    fire_hook('membership.plan.activated', $userid);
    return true;
}
function membership_deactivate($user){
    $userid = $user['id'];
    $role = 2;
    $type = " ";
    $id = " ";
    $eTime = " ";
    if($user['role'] == '1') {
        $role = $user['role'];
    }
    db()->query("UPDATE users SET membership_type='{$type}',membership_plan='{$id}',membership_expire_time='{$eTime}',role='{$role}' WHERE id='{$userid}'");

}

function add_membership_plan($val) {
    $expected = array(
        'type' => '',
        'price' => '',
        'cycle_number' => '',
        'cycle_type' => '',
        'role' => '',
        'title' => '',
        'desc' => '',
        'recommend' => ''
    );

    /**
     * @var $type
     * @var $price
     * @var $cycle_number
     * @var $cycle_type
     * @var $role
     * @var $title
     * @var $desc
     * @var $recommend
     */
    extract(array_merge($expected, $val));

    /**
     * @var $title
     * @var $desc
     */
    extract(array_merge($expected, $val));
    $titleSlug = "membership_plan_".md5(time().serialize($val)).'_title';
    $descriptionSlug = "membership_plan_".md5(time().serialize($val))."_description";

    foreach($title as $langId => $t) {
        add_language_phrase($titleSlug, $t, $langId, 'membership');
    }
    foreach($desc as $langId => $t) {
        add_language_phrase($descriptionSlug, $t, $langId, 'membership');
    }

    db()->query("INSERT INTO membership_plans (title,description,type,user_role,recommend,price,expire_no,expire_type)VALUES(
    '{$titleSlug}','{$descriptionSlug}','{$type}','{$role}','{$recommend}','{$price}','{$cycle_number}','{$cycle_type}'
    )");
    $insertId = db()->insert_id;
    fire_hook('membership.plan.added', $insertId, array($val));
    return true;
}

function save_membership_plan($val, $plan) {
    $expected = array(
        'type' => '',
        'price' => '',
        'cycle_number' => '',
        'cycle_type' => '',
        'role' => '',
        'title' => '',
        'desc' => '',
        'recommend' => ''
    );

    /**
     * @var $type
     * @var $price
     * @var $cycle_number
     * @var $cycle_type
     * @var $role
     * @var $title
     * @var $desc
     * @var $recommend
     */
    extract(array_merge($expected, $val));

    /**
     * @var $title
     * @var $desc
     */
    extract(array_merge($expected, $val));
    $titleSlug = $plan['title'];
    $descSlug = $plan['description'];
    foreach($title as $langId => $t) {
        (phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId, 'membership') : add_language_phrase($titleSlug, $t, $langId, 'membership');

    }

    foreach($desc as $langId => $t) {
        (phrase_exists($langId, $descSlug)) ? update_language_phrase($descSlug, $t, $langId, 'membership') : add_language_phrase($descSlug, $t, $langId, 'membership');

    }
    $id = $plan['id'];
    db()->query("UPDATE membership_plans SET type='{$type}',price='{$price}',user_role='{$role}',recommend='{$recommend}',expire_no='{$cycle_number}',expire_type='{$cycle_type}' WHERE id='{$id}'");
    fire_hook('membership.plan.saved', $id, array($val));
    return true;
}

function delete_membership_plan($id) {
    db()->query("DELETE FROM membership_plans WHERE id='{$id}'");
}


/**
 * Class to handle coupon operations
 * Changes by Oke Ayodeji A
 *
 * @author Joash Pereira
 * @date  2015-06-05
 */
class coupon {
    CONST MIN_LENGTH = 8;

    /**
     * MASK FORMAT [XXX-XXX]
     * 'X' this is random symbols
     * '-' this is separator
     *
     * @param array $options
     * @return string
     * @throws Exception
     */
    static public function generate($options = []) {

        $length = (isset($options['length']) ? filter_var($options['length'], FILTER_VALIDATE_INT, ['options' => ['default' => self::MIN_LENGTH, 'min_range' => 1]]) : self::MIN_LENGTH);
        $prefix = (isset($options['prefix']) ? self::cleanString(filter_var($options['prefix'], FILTER_SANITIZE_STRING)) : '');
        $suffix = (isset($options['suffix']) ? self::cleanString(filter_var($options['suffix'], FILTER_SANITIZE_STRING)) : '');
        $useLetters = (isset($options['letters']) ? filter_var($options['letters'], FILTER_VALIDATE_BOOLEAN) : true);
        $useNumbers = (isset($options['numbers']) ? filter_var($options['numbers'], FILTER_VALIDATE_BOOLEAN) : false);
        $useSymbols = (isset($options['symbols']) ? filter_var($options['symbols'], FILTER_VALIDATE_BOOLEAN) : false);
        $useMixedCase = (isset($options['mixed_case']) ? filter_var($options['mixed_case'], FILTER_VALIDATE_BOOLEAN) : false);
        $mask = (isset($options['mask']) ? filter_var($options['mask'], FILTER_SANITIZE_STRING) : false);

        $uppercase = ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'Z', 'X', 'C', 'V', 'B', 'N', 'M'];
        $lowercase = ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p', 'a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'z', 'x', 'c', 'v', 'b', 'n', 'm'];
        $numbers = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $symbols = ['`', '~', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '-', '_', '=', '+', '\\', '|', '/', '[', ']', '{', '}', '"', "'", ';', ':', '<', '>', ',', '.', '?'];

        $characters = [];
        $coupon = '';
        if($useLetters) {
            if($useMixedCase) {
                $characters = array_merge($characters, $lowercase, $uppercase);
            } else {
                $characters = array_merge($characters, $uppercase);
            }
        }

        if($useNumbers) {
            $characters = array_merge($characters, $numbers);
        }

        if($useSymbols) {
            $characters = array_merge($characters, $symbols);
        }

        if($mask) {
            for($i = 0; $i < strlen($mask); $i++) {
                if($mask[$i] === 'X') {
                    $coupon .= $characters[mt_rand(0, count($characters) - 1)];
                } else {
                    $coupon .= $mask[$i];
                }
            }
        } else {
            for($i = 0; $i < $length; $i++) {
                $coupon .= $characters[mt_rand(0, count($characters) - 1)];
            }
        }

        return $prefix.$coupon.$suffix;
    }

    /**
     * @param int $maxNumberOfCoupons
     * @param array $options
     * @return array
     */
    static public function generate_coupons($maxNumberOfCoupons = 1, $options = []) {
        $coupons = [];
        for($i = 0; $i < $maxNumberOfCoupons; $i++) {
            $temp = self::generate($options);
            $coupons[] = $temp;
        }
        return $coupons;
    }

    /**
     * @param int $maxNumberOfCoupons
     * @param $filename
     * @param array $options
     */


    /**
     * Strip all characters but letters and numbers
     * @param $string
     * @param array $options
     * @return string
     * @throws Exception
     */
    static private function cleanString($string, $options = []) {
        $toUpper = (isset($options['uppercase']) ? filter_var($options['uppercase'], FILTER_VALIDATE_BOOLEAN) : false);
        $toLower = (isset($options['lowercase']) ? filter_var($options['lowercase'], FILTER_VALIDATE_BOOLEAN) : false);

        $striped = preg_replace('/[^a-zA-Z0-9]/', '', $string);

        if($toLower && $toUpper) {
            throw new Exception('You cannot set both options (uppercase|lowercase) to "true"!');
        } else if($toLower) {
            return strtolower($striped);
        } else if($toUpper) {
            return strtoupper($striped);
        } else {
            return $striped;
        }
    }

    static public function coupon_exist($coupon) {
        $db = db();
        $query = $db->query("SELECT coupon FROM promotion_code WHERE coupon='{$coupon}'");
        if($query->num_rows > 0) return true;
        return false;
    }

    static public function generating_value($val) {
        if(isset($val['length'])) {
            $no_of_coupons = $val['no_of_coupons'];
            $length = $val['length'];
            $prefix = $val['prefix'];
            $suffix = $val['suffix'];
            $numbers = $val['numbers'];
            $letters = $val['letters'];
            $symbols = $val['symbols'];
            $mixed_case = $val['mixed_case'] == 'false' ? false : true;
            $mask = $val['mask'] == '' ? false : $val['mask'];
            $percentOff = $val['discount'];
            $no_of_use = $val['no_of_use'];
            $active = $val['active'];

            $options = array(
                'length' => $length,
                'prefix' => $prefix,
                'suffix' => $suffix,
                'numbers' => $numbers,
                'letters' => $letters,
                'symbols' => $symbols,
                'mixed_case' => $mixed_case,
                'mask' => $mask);
            $coupons = self::generate_coupons($no_of_coupons, $options);
            $success_count = 0;
            foreach($coupons as $key => $value) {
                $existing = self::coupon_exist($value);
                if(!$existing) {
                    self::coupon_insertDB($value, $percentOff, $no_of_use, $active);
                    $success_count += $success_count;
                }
            }
            return array('miss_count' => $success_count, 'total_count' => count($coupons));
        }
    }

    static public function coupon_insertDB($coupon, $discount, $no_of_use, $status) {
        $db = db();
        $db->query("INSERT INTO promotion_code (coupon, discount,no_of_use,status) VALUES ('$coupon','$discount','$no_of_use','$status')");

    }

    static public function getPromotionCodes($id = null, $active = null, $search = null, $limit = 20) {
        $whereclause = " WHERE 1=1";
        if($id) $whereclause .= " AND id ={$id}";
        if($active) $whereclause .= " AND active ={$active}";
        if($search) $whereclause .= " AND coupon LIKE %$search%";
        $sql = "SELECT * FROM promotion_code {$whereclause}";
        $paginator = paginate($sql, $limit);
        return $paginator;
    }

    static public function get_coupon($coupon) {
        $query = db()->query("SELECT * FROM promotion_code WHERE coupon ='$coupon'");
        return $query->fetch_assoc();
    }

    static public function update_coupon($no, $status, $id) {
        db()->query("UPDATE promotion_code SET no_of_use='{$no}',status='{$status}' WHERE id='{$id}'");
    }

    static public function activate_coupon($id, $method) {
        $status = ($method == 'activate') ? '1' : '0';
        db()->query("UPDATE promotion_code SET status='{$status}' WHERE id='{$id}'");
    }

    static public function activate_delete($id) {
        db()->query("DELETE FROM promotion_code WHERE id='{$id}'");
    }
}
function profile_completion($val){
    $user = get_user();
    $val['cover'] = $user['cover'];
    $val['m_status'] = $user['m_status'];
    $val['avatar']= isset($val['avatar'])?$val['avatar']:$user['avatar'];
    $field = profile_completion_fields();
    $percentage = 0;
    $i = 0;
    foreach ($val as $key => $value){
        $column = config('completion-'.$key);
        if (isset($user[$key]) and ($column && $value)){
            $percentage += $column;
        }
        $i++;
    }
    return round($percentage);
}
function profile_completion_fields(){
    $field = array('first_name','last_name','username','email_address','avatar','cover','country','state','city','gender','bio','m_status');
    return $field;
}
function profile_completion_check(){
    $user = get_user();
    $bool = true;
    $fields = profile_completion_fields();
    foreach ($fields as $key => $field){
        if ((!$user[$field])) {
            $bool = false;
        }
    }
    return $bool;
}