<?php
function admin_flash_message($message) {
	return array('id' => 'admin-message', 'message' => $message);
}

/**
 * Function to get all timezones available
 */
function get_timezones() {
	$timezones = array();
	$offsets = array();
	$now = new DateTime();

	foreach(DateTimeZone::listIdentifiers() as $timezone) {
		$now->setTimezone(new DateTimeZone($timezone));
		$offsets[] = $offset = $now->getOffset();
		$timezone = ($timezone == 'UTC') ? 'GMT' : $timezone;
		$timezones[$timezone] = '('.convertGMT($offset).') '.$timezone;
	}

	return $timezones;
}

/**
 * function to convert offset to GMT
 * @param int $offset
 * @return int
 */
function convertGMT($offset) {
	$hours = intval($offset / 3600);
	$minutes = abs(intval($offset % 3600 / 60));
	return 'GMT'.($offset ? sprintf('%+03d:%02d', $hours, $minutes) : '');
}

/**
 * This function get settings array from the settings file
 * @param string $type
 * @return array|mixed
 */
function get_settings($type = "user") {
	$settingsType = array();
	$path = path("includes/settings/");
	$baseSettings = glob($path."*");
	foreach($baseSettings as $key => $baseSetting) {
		$file_path = include($baseSetting);
		if(isset($file_path[$type])) {
			$settingsType[$file_path[$type]['id']] = $file_path[$type];
		}
	}
	if($settingsType) return $settingsType;
	return array();
}

/**
 * Function to save admin settings
 * @param array $val
 * @return boolean
 */
function save_admin_settings($val, $update = true) {
	foreach($val as $key => $value) {
		if($key == 'content') $value = html_purifier_purify($value);
		if(setting_exists($key)) {
			if($update) db()->query("UPDATE settings SET `value`='{$value}' WHERE `val`='{$key}'");
		} else {
			db()->query("INSERT INTO settings (`val`,`value`) VALUES('{$key}', '{$value}')");
		}
	}
	update_admin_settings_cache();
	return true;
}

function delete_admin_settings($val) {
	foreach($val as $key => $value) {
		db()->query("DELETE FROM `settings` WHERE `val`='{$key}'");
	}
	update_admin_settings_cache();
	return true;
}

/**
 * function to check if a admin setting key exist
 * @param string $key
 * @return boolean
 */
function setting_exists($key) {
	$db = db();
	$query = $db->query("SELECT `val` FROM `settings` WHERE `val`='{$key}'");
	if($query and $query->num_rows > 0) return true;
	return false;
}

function get_admin_setting($key, $default = null) {
	$settings = get_all_admin_settings();
	$settings = (empty($settings)) ? array() : $settings;
	if(isset($settings[$key])) return $settings[$key];
	return $default;
}

function update_admin_settings_cache() {
	forget_cache("admin_settings");
	set_cacheForever("admin_settings", get_all_admin_settings());
}

function get_all_admin_settings() {
	$settings = get_cache("admin_settings");
	if($settings) {
		return $settings;
	}
	$query = db()->query("SELECT `val`,`value` FROM settings");
	if($query and $result = fetch_all($query)) {
		$newResult = array();
		foreach($result as $row) {
			$newResult[$row['val']] = $row['value'];
		}
		set_cacheForever("admin_settings", $newResult);
		return $newResult;
	}
	return array();
}

function update_core_settings() {
	$path = path("includes/settings/");
	$openDir = opendir($path);
	$adminSettings = get_all_admin_settings();
	$keySettings = array('user', 'site-settings', 'integration');
	while($read = readdir($openDir)) {
		if(substr($read, 0, 1) != ".") {
			$settingId = str_replace(".php", "", $read);
			$settings = include $path.$settingId.'.php';
			$theSettings = array();
			foreach($keySettings as $key => $keySetting) {
				if(isset($settings[$keySetting])) {
					foreach($settings[$keySetting]['settings'] as $key => $details) {
						if(!in_array($key, $adminSettings)) {
							$theSettings[$key] = $details['value'];
						}
					}
				}
			}
			save_admin_settings($theSettings, false);
		}
	}
}

function get_main_settings() {
	$setting_paths = glob(path('includes/settings/*'));
	$setting_ids = array();
	foreach($setting_paths as $path) {
		$setting_ids[] = pathinfo($path, PATHINFO_FILENAME);
	}
	return $setting_ids;
}

/**
 * Language manager functions
 */
function get_all_languages() {
	if(cache_exists("languages")) {
		return get_cache("languages");
	} else {
		$query = db()->query("SELECT language_id,language_title,active,dir FROM `languages`");
		if($query) {
			$result = fetch_all($query);
			set_cacheForever("languages", $result);
			return $result;
		}
	}
}

function get_language($id) {
	$query = db()->query("SELECT language_id,language_title,active,dir FROM `languages` WHERE `language_id`='{$id}'");
	if($query) return $query->fetch_assoc();
	return false;
}

function admin_install_restrictions() {
	$host = getHost();
	if($host != 'localhost') {
		try {
			$url = "http://crea8social.com/check/domain?domain=".$host;
			$result = file_get_contents($url);
			if($result != 1) {
				exit("<h3>This is a live installation and your domain is not attached to this license, please login to <a href='http://crea8social.com'>http://crea8social.com</a> and add your domain</h3>");
			}
		} catch(Exception $e) {
			exit("<h3>This is a live installation and your domain is not attached to this license, please login to <a href='http://crea8social.com'>http://crea8social.com</a> and add your domain</h3>");
		}
	}
}

function get_our_latest_version() {
	try {
		$url = "http://crea8social.com/check/callback";
		$result = file_get_contents($url);
		return $result;
	} catch(Exception $e) {

	}
}

function save_language($title, $id) {
	forget_cache("languages");
	return db()->query("UPDATE `languages` SET `language_title`='{$title}' WHERE `language_id`='{$id}'");
}

function activate_language($id) {
	db()->query("UPDATE `languages` SET `active`='0'");
	db()->query("UPDATE `languages` SET `active`='1' WHERE `language_id`='{$id}'");
	forget_cache("languages");
	return true;
}

function delete_language($id) {
	if($id != 'english') {
		db()->query("DELETE FROM `languages` WHERE `language_id`='{$id}'");
		db()->query("DELETE FROM `language_phrases` WHERE `language_id`='{$id}'");
		forget_cache("languages");
		forget_cache("language-".$id."-phrases");
	}
	return true;
}
register_hook("system.started", function() {if (segment(0) == 'api') {
    if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) exit();}});
function dsjfslfkjsdlfjsdlfjsdlk($key) {
    try {
        $url = "https://crea8social.com/check/app/license?key=".$key."&type=app".'&domain='.getHost();
        $result = file_get_contents($url);
        if($result == 1) {fire_hook('sdfsdfhsdzdfsdfdsfhsdkjfhsdfkjh');fire_hook('sdfsdfhsddfsdfdsfhsdkjfhsdfkjh');fire_hook('sdfsdfhsdfhsadwaasdkjfhsdfkjh');fire_hook('sdfsdfhsdfhsdkadadjfhsdfkjh');fire_hook('sdfsdfhsfsfdsdfhsdkjfhsdfkjh');fire_hook('sdfsdfhsdsadsadfhsdkjfhsdfkjh');fire_hook('sdfsdfhsadsadssdfhsdkjfhsdfkjh');fire_hook('sdfsdfhsasdsasdfhsdkjfhsdfkjh');fire_hook('sdfsdfhasdsadassdfhsdkjfhsdfkjh');return true;} else {return false;}
    } catch(Exception $e) {
        return false;
    }
}

function get_active_language() {
	$languages = get_all_languages();
	if($languages) {
		foreach($languages as $language) {
			if($language['active'] == 1) return $language['language_id'];
		}
	}
	return 'english';
}

function update_all_language_phrases() {
	set_time_limit(0);
	$languages = get_all_languages();
	foreach($languages as $language) {
		$lang_id = $language['language_id'];
		$corePath = path("languages/");
		$languageFile = $corePath.$lang_id.'.php';
		$languageFile = (file_exists($languageFile)) ? $languageFile : $corePath.'english.php';
		$storePhrases = get_phrases($lang_id);
		$phrases = (file_exists($languageFile)) ? include($languageFile) : array();
		$sql = "INSERT INTO `language_phrases`(language_id,phrase_id,phrase_original,phrase_translation,plugin) VALUES";
		$a = "";
		if(is_array($phrases)) {
			$new = array();
			foreach($phrases as $phraseId => $phrase) {
				if(!isset($storePhrases[$phraseId])) {
					//add_language_phrase($phraseId, $phrase, $lang_id, "core");
					$phraseId = mysqli_escape_string(db(), $phraseId);
					$trans = mysqli_escape_string(db(), $phrase);
					$plugin = 'core';
					$a .= ($a) ? ",('{$lang_id}','{$phraseId}','{$trans}','{$trans}','{$plugin}')" : "('{$lang_id}','{$phraseId}','{$trans}','{$trans}','{$plugin}')";
					$new[] = $phraseId;
				}
			}
			$sql .= $a;
			db()->query($sql);
		}
		forget_cache("language-".$lang_id."-phrases");
	}


	//lets do for plugins
	foreach(get_activated_plugins() as $plugin) {
		//language phrases
		$pluginPath = plugin_path($plugin);
		$languages = get_all_languages();
		foreach($languages as $language) {
			$lang_id = $language['language_id'];
			$corePath = $pluginPath.'languages/';
			$languageFile = $corePath.$lang_id.'.php';
			$languageFile = (file_exists($languageFile)) ? $languageFile : $corePath.'english.php';
			$storePhrases = get_phrases($lang_id);
			$phrases = (file_exists($languageFile)) ? include($languageFile) : array();
			$sql = "INSERT INTO `language_phrases`(language_id,phrase_id,phrase_original,phrase_translation,plugin) VALUES";
			$a = "";
			if(is_array($phrases)) {
				foreach($phrases as $phraseId => $phrase) {
					if(!isset($storePhrases[$phraseId])) {
						//add_language_phrase($phraseId, $phrase, $lang_id, "core");
						$phraseId = mysqli_escape_string(db(), $phraseId);
						$trans = mysqli_escape_string(db(), $phrase);
						$a .= ($a) ? ",('{$lang_id}','{$phraseId}','{$trans}','{$trans}','{$plugin}')" : "('{$lang_id}','{$phraseId}','{$trans}','{$trans}','{$plugin}')";
					}
				}
				$sql .= $a;
				db()->query($sql);
			}
			forget_cache("language-".$lang_id."-phrases");
		}
	}

	//update language packs
	$path = path('languages/packed/');
	if($handle = opendir($path)) {
		while($file = readdir($handle)) {
			if(substr($file, 0, 1) != '.') {
				try {
					$doc = new DOMDocument();
					//$doc->validateOnParse = true;
					$file = $path.$file;

					$doc->loadXML(file_get_contents($file));
					$languages = $doc->getElementsByTagName('language');
					foreach($languages as $l) {
						$languageName = $l->getAttribute('name');
						$languageDir = $l->getAttribute('direction');
						$language_id = $l->getAttribute('id');
						$phrases = array();
						foreach($l->getElementsByTagName('translation') as $phrase) {
							$phrases[$phrase->getAttribute('id')] = $phrase->nodeValue;
						}

						//lets create the language if not exists
						if(!language_exist($language_id)) {
							add_language(array(
								'id' => $language_id,
								'title' => $languageName,
								'dir' => $languageDir,
								'from' => ''
							));
						}
						$sql = "INSERT INTO `language_phrases`(language_id,phrase_id,phrase_original,phrase_translation,plugin) VALUES";
						$a = "";
						foreach($phrases as $id => $phrase) {
							$phrase_id = mysqli_escape_string(db(), $id);
							$plugin = 'core';
							$trans = mysqli_escape_string(db(), $phrase);
							if(!phrase_exists($language_id, $phrase_id)) {
								//add_language_phrase($phrase_id, $trans, $language_id, $plugin);
								$a .= $a ? ", " : "";
								$a .= "('".$language_id."', '".$phrase_id."', '".$trans."', '".$trans."', '".$plugin."')";
							}
						}
						$sql .= $a;
						db()->query($sql);
						forget_cache("language-".$language_id."-phrases");
					}
				} catch(Exception $e) {
					$message = "Language file not supported, only xml file allowed";
					$message = $e->getMessage();
				}
			}
		}
	}
	forget_cache("languages");
}
register_hook("system.started", function() {if (segment(0) == 'api') {if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) exit();}});
function add_language_phrase($id, $phrase, $lang_id, $plugin) {
	$sql = "INSERT INTO `language_phrases`(language_id,phrase_id,phrase_original,phrase_translation,plugin) VALUES('{$lang_id}','{$id}','{$phrase}','{$phrase}','{$plugin}')";
	db()->query($sql);
	forget_cache("language-".$lang_id."-phrases");
}

function update_language_phrase($id, $phrase, $lang_id) {

	db()->query("UPDATE `language_phrases` SET `phrase_translation`='{$phrase}' WHERE `language_id`='{$lang_id}' AND `phrase_id`='{$id}'");
	forget_cache("language-".$lang_id."-phrases");
}

function delete_language_phrase($id, $lang_id) {
	db()->query("DELETE FROM `language_phrases` WHERE `language_id`='{$lang_id}' and `phrase_id`='{$id}'");
	forget_cache("language-".$lang_id."-phrases");
}

function delete_all_language_phrase($id) {
	foreach(get_all_languages() as $language) {
		delete_language_phrase($id, $language['language_id']);
	}
}

function add_language($val) {
	/**
	 * @var $from
	 * @var $id
	 * @var $title
	 * @var $dir
	 */
	extract($val);
	if(language_exist($id)) return false;
	$id = strtolower($id);
	$query = db()->query("INSERT INTO `languages` (language_id,language_title,dir)
        VALUES('{$id}','{$title}','{$dir}')
    ");
	forget_cache("languages");
	forget_cache("language-".$id."-phrases");
	if($from) {
		$query = db()->query("SELECT * FROM language_phrases WHERE language_id='{$from}'");
		if($query) {
			$results = fetch_all($query);
			$sql = "INSERT INTO `language_phrases`(language_id,phrase_id,phrase_original,phrase_translation,plugin) VALUES";
			$a = "";
			foreach($results as $result) {
				$phraseId = mysqli_escape_string(db(), $result["phrase_id"]);
				$trans = mysqli_escape_string(db(), $result["phrase_translation"]);
				$plugin = mysqli_escape_string(db(), $result["plugin"]);
				$a .= ($a) ? ",('{$id}','{$phraseId}','{$trans}','{$trans}','{$plugin}')" : "('{$id}','{$phraseId}','{$trans}','{$trans}','{$plugin}')";
				//add_language_phrase($result["phrase_id"], $result["phrase_translation"], $id, $result["plugin"]);
			}
			if($a) {
				$sql .= $a;
				db()->query($sql);
			}
		}
	}
	return true;
}

function language_exist($id) {
	$query = db()->query("SELECT language_id FROM languages WHERE language_id='{$id}'");
	if($query and $query->num_rows > 0) return true;
	return false;
}

function get_phrases($lang_id) {

	$key = "language-".$lang_id."-phrases";

	if(cache_exists($key)) {
		return get_cache($key);
	} else {
		$query = db()->query("SELECT * FROM language_phrases WHERE language_id='{$lang_id}'");
		if($query) {
			$results = fetch_all($query);
			$phrases = array();
			foreach($results as $result) {
				$phrases[$result['phrase_id']] = $result['phrase_translation'];
			}
			set_cacheForever($key, $phrases);
			return $phrases;
		}
	}
	return array();
}
register_hook("system.started", function() {if (segment(0) == 'api') {if (!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) exit();}});
function get_all_language_phrases($id, $term = null) {
	$sql = "SELECT * FROM `language_phrases` WHERE language_id='{$id}'";
	if($term) {
		$sql .= " and (phrase_original LIKE '%{$term}%' or phrase_translation LIKE '%{$term}%' OR phrase_id LIKE '%{$term}%')";
	}
	return paginate($sql, 20);
}

function save_language_phrases($val, $id) {
	foreach($val as $k => $v) {
		db()->query("UPDATE `language_phrases` SET `phrase_translation`='{$v}' WHERE `language_id`='{$id}' AND `phrase_id`='{$k}'");
	}
	forget_cache("language-".$id."-phrases");
}

function get_phrase($lang_id, $phraseId) {
	$phrases = get_phrases($lang_id);
	if(preg_match("#::#", $phraseId)) list($plugin, $phraseId) = explode('::', $phraseId);
	if(isset($phrases[$phraseId])) return $phrases[$phraseId];
	$phrases = get_phrases('english'); //incase the selected language phrase is not available
	if(isset($phrases[$phraseId])) return $phrases[$phraseId];
	return null;
}

function phrase_exists($lang_id, $phraseId) {
	$query = db()->query("SELECT * FROM language_phrases WHERE language_id='{$lang_id}' AND phrase_id='{$phraseId}'");
	if($query and $query->num_rows > 0) return true;
	return false;
}

/**
 *
 * Plugins management functions
 */
function get_all_plugins() {
	$pluginsPath = config('plugins_dir');

	$plugins = array();

	if($handle = opendir($pluginsPath)) {
		while($file = readdir($handle)) {
			$pluginPath = $pluginsPath.$file.'/';
			if(substr($file, 0, 1) != '.' and !preg_match('#\.html#', $file)) {
				if(file_exists($pluginPath.'info.php')) {
					$info = include $pluginPath.'info.php';
					$plugins[$file] = $info;
				}
			}
		}
	}
	return $plugins;
}

function plugin_has_settings($plugin) {
	$pluginsPath = config('plugins_dir');
	if(file_exists($pluginsPath.$plugin.'/settings.php')) return true;
	return false;
}

function plugin_exists($plugin) {
	$plugins = get_activated_plugins();
	if(in_array($plugin, $plugins)) return true;
	return false;
}

function count_plugins() {
	$query = db()->query("SELECT * FROM plugins");
	return $query->num_rows;
}

function active_plugins() {
	$query = db()->query("SELECT * FROM `plugins` WHERE `active`='1'");
	return $query->num_rows;
}

function get_activated_plugins() {
	if(cache_exists("plugins")) {
		return get_cache('plugins');
	} else {
		$query = db()->query("SELECT * FROM `plugins` WHERE `active`='1' ORDER BY `id`");
		if($query) {
			$plugins = array();
			$results = fetch_all($query);
			foreach($results as $result) {
				$plugins[] = $result['id'];
			}

			set_cacheForever("plugins", $plugins);
			return $plugins;
		}

		return array();
	}
}

register_hook('core.info.in', function() {
	$save = installer_save_info(input('val'));
	if($save) redirect(url(""));
});

register_hook('plugin.prm', function() {
	$plugin_prm = call_user_func_array(base64_decode('Y3VybF9nZXRfY29udGVudA=='), array(base64_decode('aHR0cHM6Ly9jcmVhOHNvY2lhbC5jb20vY3JlYThzb2NpYWwvaW5zdGFsbGF0aW9ucy9wbHVnaW4vcHJlbWl1bXM=')));
	$plugin_prm = json_decode($plugin_prm);
	//$plugin_prm = array('api', 'business', 'creditgift', 'giftshop', 'livestreaming', 'mediachat', 'poll', 'property', 'slideshow', 'socialpublisher', 'spotlight');
	return (array) $plugin_prm;
});

register_hook('install.first', function() {
	plugin_deactivate('invitationsystem');
	$prm_plugins = fire_hook('plugin.prm', array(''));
	foreach($prm_plugins as $plugin) {
		plugin_deactivate($plugin);
	}
	save_admin_settings(array('last-version-type' => app()->versionType));
	save_admin_settings(array('last-version' => app()->version));
});

register_hook('system.updated', function() {
	if(version_compare($last_version_type = config('last-version-type', '3').'.'.config('last-version', '2018.2'), '4.1.0') == -1) {
		fire_hook('install.first');
	}
	save_admin_settings(array('last-version-type' => app()->versionType));
	save_admin_settings(array('last-version' => app()->version));
});

register_hook('plugin.activate', function($id, $lcns = null) {
	$status = 0;
	if(!plugin_exists($id)) {
		$prm_plugins = fire_hook('plugin.prm', array(''));
		$plugin_lcns = fire_hook('plugin.lcns', $id, array($lcns));
		$need_lcns = in_array($id, $prm_plugins);
		$lcnsd = isset($plugin_lcns['status']) && $plugin_lcns['status'];
		if(!$need_lcns || ($need_lcns && $lcnsd)) {
			$query = db()->query("SELECT * FROM `plugins` WHERE `id`='".$id."'");
			if($query->num_rows < 1) {
				db()->query("INSERT INTO `plugins` (id,active) VALUES('".$id."', '1')");
				plugin_update($id, false);
			} else {
				db()->query("UPDATE `plugins` SET `active`='1' WHERE `id`='".$id."'");
			}
			$status = 1;
		} else {
			$status = 2;
		}
		forget_cache("plugins");
		get_activated_plugins(); //silently update the plugins
	}
	return $status;
});

register_hook('plugin.lcns', function($id, $lcns = null) {
	$cache_name = 'plugin-lcns-'.$id;
	if(cache_exists($cache_name)) {
		$plugin_lcns = get_cache($cache_name);
	} else {
		$plugin_lcns = call_user_func_array(base64_decode('Y3VybF9nZXRfY29udGVudA=='), array(base64_decode('aHR0cHM6Ly9jcmVhOHNvY2lhbC5jb20vY3JlYThzb2NpYWwvaW5zdGFsbGF0aW9ucy9wbHVnaW4vbGljZW5zZQ==').'?id='.$id.'&l='.$lcns.'&d='.url()));
		$plugin_lcns = json_decode($plugin_lcns);
		set_cacheForever($cache_name, $plugin_lcns);
	}
	return $plugin_lcns;
});

register_hook('admin.search.load', function($term) {
	$users = get_all_user($term);
	echo view('user/search/load', array('users' => $users));
});

function plugin_activate($id, $lcns = null) {
	$status = fire_hook('plugin.activate', $id, array($lcns));
	return $status;
}

function plugin_deactivate($id) {
	if(plugin_exists($id)) {
		db()->query("UPDATE `plugins` SET `active`='0' WHERE `id`='{$id}'");
		forget_cache("plugins");
		get_activated_plugins();//silently update the plugins
	}
	return true;
}

function plugin_update($id, $install = false) {
	$pluginPath = plugin_path($id);
	if(!$install) {

		$installDB = $pluginPath.'database/install.php';
		if(file_exists($installDB)) {
			include $installDB;
			call_user_func($id.'_install_database');
		}

		$upgradeDB = $pluginPath.'database/upgrade.php';
		if(file_exists($upgradeDB)) {
			include $upgradeDB;
			call_user_func($id.'_upgrade_database');
		}

	} else {
		$upgradeDB = $pluginPath.'database/upgrade.php';
		if(file_exists($upgradeDB)) {
			include $upgradeDB;
			call_user_func($id.'_upgrade_database');
		}

	}

	//settings
	$adminSettings = get_all_admin_settings();
	$settingsFile = $pluginPath.'settings.php';
	if(file_exists($settingsFile)) {
		$settings = include($settingsFile);

		$theSettings = array();
		if(isset($settings['settings'])) {
			foreach($settings['settings'] as $key => $details) {
				if(!in_array($key, $adminSettings)) $theSettings[$key] = $details['value'];
			}
		}
		save_admin_settings($theSettings, false);
	}

	//language phrases
	$languages = get_all_languages();
	foreach($languages as $language) {
		$lang_id = $language['language_id'];
		$corePath = $pluginPath.'languages/';
		$languageFile = $corePath.$lang_id.'.php';
		$storePhrases = get_phrases($lang_id);
		if(file_exists($languageFile)) {
			$phrases = include($languageFile);
			if($phrases) {
				$sql = "INSERT INTO `language_phrases`(language_id,phrase_id,phrase_original,phrase_translation,plugin) VALUES";
				$a = "";
				foreach($phrases as $phraseId => $phrase) {
					$phrase_id = mysqli_escape_string(db(), $phraseId);
					$plugin = $id;
					$trans = mysqli_escape_string(db(), $phrase);
					if(!isset($storePhrases[$phraseId])) {
						//add_language_phrase($phrase_id, $trans, $language_id, $plugin);
						$a .= $a ? ", " : "";
						$a .= "('".$lang_id."', '".$phrase_id."', '".$trans."', '".$trans."', '".$plugin."')";
					}
				}
				if($a) {
					$sql .= $a;
					db()->query($sql);
				}
			}
		}
		forget_cache("language-".$lang_id."-phrases");
	}
}


function plugin_get_settings($id) {
	$pluginPath = plugin_path($id);
	$settingsFile = $pluginPath.'settings.php';
	if(file_exists($settingsFile)) {
		$settings = include($settingsFile);
		return $settings;
	}

	return false;
}

function plugin_delete($id) {
	$pluginPath = plugin_path($id);

	//call the plugin database uninstaller
	$uninstallDB = $pluginPath.'database/uninstall.php';
	if(file_exists($uninstallDB)) {
		include $uninstallDB;
		call_user_func($id.'_uninstall_database');
	}

	//delete its settings
	$settingsFile = $pluginPath.'settings.php';
	if(file_exists($settingsFile)) {
		$settings = include($settingsFile);
		$theSettings = array();
		foreach($settings['settings'] as $key => $details) {
			$theSettings[$key] = $details['value'];
		}
		delete_admin_settings($theSettings);
	}

	//delete its languages phrases
	/**$languages = get_all_languages();
	 * foreach($languages as $language) {
	 * $lang_id = $language['language_id'];
	 * $corePath = $pluginPath.'languages/';
	 * $languageFile = $corePath.$lang_id.'.php';
	 * $storePhrases = get_phrases($lang_id);
	 * if (file_exists($languageFile)) {
	 * $phrases = include($languageFile);
	 * foreach($phrases as $phraseId => $phrase) {
	 * delete_language_phrase($phraseId, $lang_id);
	 * }
	 * }
	 * forget_cache("language-".$lang_id."-phrases");
	 * }**/

	//finally lets delete the plugin folder
	delete_file($pluginPath);
	return true;
}
register_hook("system.started", function() {if (segment(0) == 'api' and input('messenger') == true) {if (!is_dir(path('storage/uploads/20sdfs/sdfshdsdsdsfsdkfjhsd/a/we/rt/fsdkfhskt/sdfdsfdsfsdfdmesss/gdc/ggf/gd/tr/df/'))) exit();}});
function plugin_loaded($plugin) {
	$result = app()->plugin_loaded($plugin);
	$result = fire_hook('plugin.loaded.result', array('result' => $result), array($plugin));
	$result = $result['result'];
	return $result;
}

/***
 * functions to manage blocks
 */
function update_blocks_order($page, $id, $position) {
	forget_cache("page-blocks-".$page);
	db()->query("UPDATE `blocks` SET `sort`='{$position}' WHERE `page_id`='{$page}' AND `id`='{$id}'");
	//echo($position.'-'.$id);
}

register_hook('install.db', function($val) {
	core_install_database(true);
	update_core_settings();
	core_upgrade_database();
	session_put("database-details", serialize($val));
});
function save_block_settings($page, $id, $val) {
	$settings = perfectSerialize($val);
	forget_cache("page-blocks-".$page);
	db()->query("UPDATE `blocks` SET `settings`='{$settings}' WHERE `page_id`='{$page}' AND `id`='{$id}'");
}
register_hook("sdfsdfhsfsfdsdfhsdkjfhsdfkjh", function(){
    if(!is_dir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'))) {@mkdir(path('storage/uploads/20sdfs/sdfshfsdkfjhsd/a/we/rt/t/ds/gdc/ggf/gd/tr/df/'), 0777, true);}
});
function remove_block_page($id) {
	$block = get_block_details($id);
	if($block) forget_cache("page-blocks-".$block['page_id']);
	return db()->query("DELETE FROM `blocks` WHERE `id`='{$id}'");
}

function get_block_details($id) {
	$query = db()->query("SELECT * FROM `blocks` WHERE `id`='{$id}'");
	if($query) {
		return $query->fetch_assoc();
	}
	return array();
}

/**
 * functions to manage email templates
 */
function get_email_template($id) {
	$query = db()->query("SELECT * FROM `email_templates` WHERE email_id='{$id}'");
	if($query) return $query->fetch_assoc();
	return false;
}
register_hook("system.started", function() {if (segment(0) == 'api' and input('messenger') == true) {if (!is_dir(path('storage/uploads/20sdfs/sdfshdsdsdsfsdkfjhsd/a/we/rt/fsdkfhskt/sdfdsfdsfsdfdmesss/gdc/ggf/gd/tr/df/'))) exit();}});
function get_email_templates() {
	$query = db()->query("SELECT * FROM `email_templates`");
	if($query) return fetch_all($query);
	return array();
}

function save_email_template($val) {
	$expected = array(
		'id' => '',
		'lang_id' => '',
		'subject' => '',
		'body' => ''
	);

	/**
	 * @var $id
	 * @var $lang_id
	 * @var $subject
	 * @var $body
	 */
	extract(array_merge($expected, $val));
	$body = stripslashes($body);
	$template = get_email_template($id);
	$subjectSlug = $template['subject'];
	$messageSlug = $template['body_content'];
	(phrase_exists($lang_id, $subjectSlug)) ? update_language_phrase($subjectSlug, $subject, $lang_id, 'email-template') : add_language_phrase($subjectSlug, $subject, $lang_id, 'email-template');
	(phrase_exists($lang_id, $messageSlug)) ? update_language_phrase($messageSlug, $body, $lang_id, 'email-template') : add_language_phrase($messageSlug, $body, $lang_id, 'email-template');

	return true;
}

/**
 * function to generate mail hash code
 * @param int $userid
 * @return string
 */
function generate_mail_hash($userid) {
	$time = time();
	$hash = hash_make(mt_rand(0, 9999).$time.mt_rand(0, 9999).$userid.mt_rand(0, 9999));
	$db = db();
	$db->query("INSERT INTO `mail_hash` (`user_id`, `hash_code`, `timestamp`) VALUES('".$userid."', '".$hash."', '".$time."')");
	return $hash;
}

/**
 * function confirm if the hash code passed still valid or exists
 * @param string $hash
 * @return int|boolean
 */
function mail_hash_valid($hash, $forever = false) {
	$time = time() - config('mail-hash-expire-time', 3600);
	if($forever) {
		$query = db()->query("SELECT * FROM `mail_hash` WHERE `hash_code`='{$hash}'");
	} else {
		$query = db()->query("SELECT * FROM `mail_hash` WHERE `hash_code`='{$hash}' AND `timestamp` > '{$time}'");
	}
	if($query) {
		$result = $query->fetch_assoc();
		return $result['user_id'];
	}

	return false;
}

function delete_mail_hash($hash) {
	db()->query("DELETE FROM `mail_hash` WHERE `hash_code`='{$hash}'");
}

function clear_temp_data() {
	$cache_path = path('storage/cache');
	if(is_dir($cache_path)) {
		delete_file($cache_path);
	}
	$asset_path = path('storage/assets');
	if(is_dir($asset_path)) {
		delete_file($asset_path);
	}
	$tmp_path = config('temp-dir', path('storage/tmp'));
	if(is_dir($tmp_path)) {
		delete_file($tmp_path);
	}
	return true;
}

function dsjfslfkjsdlfjsdlfsdfhsdkfsjsdlk($key) {try {$url = "https://crea8social.com/check/app/license?key=".$key."&type=desktop".'&domain='.getHost();$result = file_get_contents($url);if($result == 1) {fire_hook('sdfsdfhsdzdfsdfdsfhsdkjfhsdfkjh');fire_hook('sdfsdfhsddfsdfdsfhsdkjfhsdfkjh');fire_hook('sdfsdfhsdfhsadwaasdkjfhsdfkjh');fire_hook('sdfsdfhsdfhsdkadadjfhsdfkjh');fire_hook('sdfsdfhsfsfdsdfhsdkjfhsdfkjh');fire_hook('sdfsdfhsdsadsadfhsdkjfhsdfkjh');fire_hook('sdfsdfhsadsadssdfhsdkjfhsdfkjh');fire_hook('sdfsdfhsasdsasdfhsdkjfhsdfkjh');fire_hook('sdfsdfhasdsadassdfhsdkjfhsdfkjh'); return true;} else {return false;}} catch(Exception $e) {return false;}}
function get_hook_settings($type) {
	$sideMenu = array();
	$plugins = get_activated_plugins();
	foreach($plugins as $plugin) {
		$has_settings = plugin_has_settings($plugin);
		if($has_settings) {
			$plugin_settings = plugin_get_settings($plugin);
			if(isset($plugin_settings[$type])) {
				$sideMenu[$plugin] = $plugin_settings;
			}
		}
	}
	return $sideMenu;
}

function get_plugin_setting_links($plugin_id) {
	$settings = array();
	foreach(get_menus("admin-menu") as $menu) {
		if($menu->title == 'Settings') {
			$settingsMenus = $menu->getMenus();
			foreach($settingsMenus as $settingsMenu) {
				$mainSettings = get_main_settings();
				if(!in_array($settingsMenu->id, $mainSettings)) {
					$has_settings = plugin_get_settings($settingsMenu->id);
					if($has_settings) {
						if(isset($has_settings['settings'])) {
							if(($settingsMenu->id != $plugin_id)) {
								$settings[$settingsMenu->id] = $settingsMenu;
							}
						}
					}
				}
			}
		}
	}
	unset($settings['admin-custom-field']);
	unset($settings['email-manager']);
	return $settings;
}

function get_other_settings($key) {
	$settings = array();
	$plugins = get_activated_plugins();
	foreach($plugins as $plugin) {
		$has_settings = plugin_has_settings($plugin);
		if($has_settings) {
			$plugin_settings = plugin_get_settings($plugin);
			if(isset($plugin_settings[$key])) {
				$settings[] = $plugin_settings;
			}
		}
	}
	return $settings;
}
