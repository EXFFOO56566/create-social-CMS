<?php

function installer_db($val) {
	/**
	 * @var $host
	 * @var $username
	 * @var $name
	 * @var $password
	 */
	extract($val);

	if(empty($host) or !$username or !$name /*or !$password*/) return false;

	$configContent = file_get_contents(path('config-holder.php'));
	//replace the details
	$configContent = str_replace(array(
		'{localhost}', '{root}', '{dbname}', '{dbpassword}', '{installed}'
	), array($host, $username, $name, $password, '0'), $configContent);
	file_put_contents(path('config.php'), $configContent);

	try {
		app()->db = new mysqli(
			$host,
			$username,
			$password,
			$name
		);
	} catch(Exception $e) {
		return false;
	}

	//now install the database things
	//app()->loadFunctionFile("users");
	db()->query("SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'");
	require path("includes/database/install.php");
	require path("includes/database/upgrade.php");
	fire_hook('install.db', null, array($val));
	return true;
}

function installer_plugins() {
	$plugins = get_all_plugins();

	foreach($plugins as $key => $info) {
		if($key != 'membership' && $key != 'invitationsystem') plugin_activate($key);
	}
	return true;
}

function install_languages() {
	update_all_language_phrases();
}

function installer_save_info($val) {
	/**
	 * @var $title
	 * @var $email
	 * @var $username
	 * @var $password
	 * @var $confirm_password
	 * @var $fullname
	 */
	extract($val);
	if(!$title or !$email or !$username or !$password or !$confirm_password or $password != $confirm_password or !$fullname) return false;

	$name = explode(' ', $fullname);
	$firstName = $name[0];
	$lastName = (isset($name[1])) ? $name[1] : '';
	add_user(array(
		'username' => $username,
		'email_address' => $email,
		'password' => $password,
		'gender' => 'male',
		'role' => '1',
		'first_name' => $firstName,
		'last_name' => $lastName
	));
	db()->query("UPDATE users SET role='1' WHERE id='1'");
	save_admin_settings(array('site_title' => $title));

	$val = unserialize(session_get("database-details"));
	/**
	 * @var $host
	 * @var $username
	 * @var $name
	 * @var $password
	 */
	extract($val);

	if(empty($host) or !$username or !$name) return false;

	$configContent = file_get_contents(path('config-holder.php'));
	//replace the details
	$configContent = str_replace(array(
		'{localhost}', '{root}', '{dbname}', '{dbpassword}', '{installed}'
	), array($host, $username, $name, $password, 'true'), $configContent);
	file_put_contents(path('config.php'), $configContent);
	return true;
}

function installer_input($name, $default = null) {
	//if (!isset($_POST[$name]) and !isset($_GET[$name])) return $default;
	$index = "";
	if(preg_match("#\.#", $name)) list($name, $index) = explode(".", $name);

	$result = (isset($_GET[$name])) ? $_GET[$name] : $default;
	$result = (isset($_POST[$name])) ? $_POST[$name] : $result;

	if(is_array($result)) {
		if(empty($index)) {
			$nR = array();
			foreach($result as $k => $v) {
				if(is_array($v)) {
					$newResult = array();
					foreach($v as $n => $a) {
						$newResult[$n] = $a;
					}
					$nR[$k] = $newResult;
				} else {
					$nR[$k] = $v;
				}
			}
			$result = $nR;
		} else {
			if(!isset($result[$index])) return $default;
			if(is_array($result[$index])) {
				$newResult = array();
				foreach($result[$index] as $n => $v) {
					$newResult[$n] = $v;
				}
				$result = $newResult;
			} else {
				$result = $result[$index];
			}

		}
	} else {
		$result = $result;
	}

	return $result;
}
 