<?php

include "pagers.php";

/**
 * Extend validation
 */
validation_extend("predefined", lang("validation-predefined-words"), function($value, $field) {
	$predefined = get_setting('predefined-words', '');
	$predefinedArray = explode(',', $predefined);

	if(in_array(strtolower($value), $predefinedArray)) return false;

	return true;
});

validation_extend('username', lang('validation-username'), 'validate_username');

validation_extend('usernameedit', lang('validation-username'), 'validate_username');

/**
 * Register hook
 */
register_hook("system.shutdown", function() {
	db()->close();
});

/**
 * Register hook for before render js files
 */
register_hook("before-render-js", function($html) {
	$baseUrl = url();
	$loadingImage = img("images/loading.gif");
	$isLoggedIn = (is_loggedIn()) ? 1 : 0;
	$user_id = is_loggedIn() ? get_userid() : 'undefined';
	$friends = is_loggedIn() ? json_encode(get_friends()) : json_encode(array());
	$trans_ago = addslashes(lang('ago'));
	$trans_from_now = addslashes(lang('from-now'));
	$trans_any_moment = addslashes(lang('any-moment'));
	$trans_less_than_minute = addslashes(lang('less-than-minute'));
	$trans_about_minute = addslashes(lang('about-minute'));
	$trans_about_minutes = addslashes(lang('about-minutes'));
	$trans_minutes = addslashes(strtolower(lang('minutes')));
	$trans_about_hour = addslashes(lang('about-hour'));
	$trans_about_hours = addslashes(lang('about-hours'));
	$trans_hours = addslashes(strtolower(lang('hours')));
	$trans_about = addslashes(strtolower(lang('about-i')));
	$trans_a_day = addslashes(lang('a-day'));
	$trans_days = addslashes(lang('days'));
	$trans_about_days = addslashes(lang('about-days'));
	$trans_about_month = addslashes(lang('about-month'));
	$trans_about_months = addslashes(lang('about-months'));
	$trans_months = addslashes(lang('months'));
	$trans_about_year = addslashes(lang('about-year'));
	$trans_about_years = addslashes(lang('about-years'));
	$trans_years = addslashes(lang('years'));
	$requestToken = CSRFProtection::getToken();
	$session_timezone = isset($_COOKIE['timezone']) && $_COOKIE['timezone'] ? $_COOKIE['timezone'] : '';
	$ajax_form_submit_error = addslashes(lang('ajax-form-submit-error'));
	load_functions('country');
	$tenorGifApi = config('tenor-gif-api-key');
	$lang_id = app()->lang;
	$world_languages = get_world_languages();
	foreach($world_languages as $key => $value) {
		if($value['name'] == $lang_id) {
			$locale = $key;
			break;
		}
	}
	$locale = isset($locale) ? $locale : $lang_id;
	$i18n = array(
		$locale => array(
			'months' => array(
				lang('january'),
				lang('february'),
				lang('march'),
				lang('april'),
				lang('may'),
				lang('june'),
				lang('july'),
				lang('august'),
				lang('september'),
				lang('october'),
				lang('november'),
				lang('december')
			),
			'dayOfWeek' => array(
				lang('wsun'),
				lang('wtue'),
				lang('wwed'),
				lang('wthu'),
				lang('wfri'),
				lang('wsat'),
			),
		)
	);
	$date_time_picker_options = json_encode(array(
		'i18n' => $i18n,
		'format' => 'Y-m-d H:i:s',
	));
	$date_picker_options = json_encode(array(
		'i18n' => $i18n,
		'timepicker' => false,
		'format' => 'Y-m-d H:i:s',
	));
	$time_picker_options = json_encode(array(
		'i18n' => $i18n,
		'datepicker' => false,
		'format' => 'Y-m-d H:i:s',
	));
	$html .= <<<EOT
<script>
			var baseUrl = '$baseUrl';
			var loggedIn = $isLoggedIn;
			var userID = $user_id;
			var friends = $friends;
			var requestToken = '$requestToken';
			var indicator = '<img src="$loadingImage" />';
			var sessionTimezone = '$session_timezone';
			var ajaxFormSubmitError = '$ajax_form_submit_error';
			var locale = '$locale';
			var dateTimePickerOptions = $date_time_picker_options;
			var datePickerOptions = $date_picker_options;
			var timePickerOptions = $time_picker_options;
		
			//time_ago translation
			var trans_ago = '$trans_ago';
			var trans_from_now = '$trans_from_now';
			var trans_any_moment = '$trans_any_moment';
			var trans_less_than_minute = '$trans_less_than_minute';
			var trans_about_minute = '$trans_about_minute';
			var trans_about_minutes = '$trans_about_minutes';
			var trans_minutes = '$trans_minutes';
			var trans_about_hour = '$trans_about_hour';
			var trans_about_hours = '$trans_about_hours';
			var trans_hours = '$trans_hours';
			var trans_about = '$trans_about';
			var trans_a_day = '$trans_a_day';
			var trans_days = '$trans_days';
			var trans_about_days = '$trans_about_days';
			var trans_about_month = '$trans_about_month';
			var trans_about_months = '$trans_about_months';
			var trans_months = '$trans_months';
			var trans_about_year = '$trans_about_year';
			var trans_about_years = '$trans_about_years';
			var trans_years = '$trans_years';
			var tenorGifApiKey = '$tenorGifApi';
		</script>
EOT;
	$html .= '<script>var ajaxInterval = '.config('ajax-polling-interval', 5000).';</script>';
	return $html;
});


/**
 * Register auth filter
 */
register_filter("auth", function() {

    if (!is_loggedIn()) {
        if (is_ajax()) {
            exit('login');
        }

        redirect(url_to_pager('login').'?redirect_to='.getFullUrl(true));
    }
    fire_hook("user.subscription.hook", null);
    fire_hook('user.loggedin', null);
    return true;
});

register_filter("user-auth", function() {
    if (!is_loggedIn()) {
        if (is_ajax()) {
            exit('login');
        }

        redirect(url_to_pager('login').'?redirect_to='.getFullUrl(true));
    }
    fire_hook("user.subscription.hook", null);
    fire_hook('user.loggedin', null);
    return true;

});


register_filter("profile", function() {
    $username = segment(0);
    if ($username == 'me' and is_loggedIn()) $username = get_userid();
    $user = find_user($username);
    Pager::setCurrentPage('profile');
    if (!$user) return false;
    if (is_blocked($user['id'])) return false;
    $app = app();
    $app->profileUser = $user;
    $app->setTitle(get_user_name($app->profileUser));
    $app->setLayout('profile/layout');
    $app->topMenu = lang('profile');
    $design = get_user_design_details($user);
    if (config('design-profile', true) and $design) app()->design = $design;
    $profile_filter_actions = fire_hook("profile.filter.actions", false, array($user));
    if(!$profile_filter_actions) {
        //register menu

        if (is_loggedIn()) get_menu("dashboard-main-menu", 'profile')->setActive(true);
        add_menu("user-profile", array('title' => lang('timeline'), 'link' => profile_url(null, $user), 'id' => 'timeline'));
        add_menu("user-profile", array('title' => lang('about'), 'link' => profile_url('about', $user), 'id' => 'about'));

        fire_hook("profile.started", null, array($user));
    }
    return true;
});


register_filter("admin-auth", function($app) {
//    return true;c


	if(!is_loggedIn()) return redirect_to_pager("admin-login");
	if(!is_admin()) return redirect_to_pager("admin-login");

	// admin_install_restrictions();

	get_menu("admin-menu", "admin-users")->addMenu(lang("members"), url_to_pager("admin-members-list"), "members");
	//get_menu("admin-menu", "admin-users")->addMenu(lang("add-member"), url_to_pager("admin-add-member"), "add-member");
	get_menu("admin-menu", "admin-users")->addMenu(lang("member-roles"), url_to_pager("admin-user-roles"), "user-roles");
	get_menu("admin-menu", "admin-users")->addMenu(lang("mailing-list"), url('admincp/mailing'), "mailing-list");
	get_menu("admin-menu", "admin-users")->addMenu(lang("verification-requests"), '#', "admin-verification");
	get_menu("admin-menu", "admin-users")->findMenu('admin-verification')->addMenu(lang("requests"), url('admincp/verify/requests'), "admin-verification-requests");
	get_menu("admin-menu", "admin-users")->findMenu('admin-verification')->addMenu(lang("questions"), url('admincp/verify/questions'), "admin-verification-questions");


	get_menu('admin-menu', 'settings')->addMenu(lang('custom-fields'), '#', 'admin-custom-field');
	//get_menu('admin-menu', 'admin-users')->findMenu('admin-custom-field')->addMenu(lang('users'), '#', 'users-custom-fields');
	get_menu('admin-menu', 'settings')->findMenu('admin-custom-field')//->findMenu('users-custom-fields')
	->addMenu(lang("user-categories"), url_to_pager("admin-custom-fields-category").'?type=user', "user-categories")
		//->addMenu(lang("add-user-category"), url_to_pager("admin-custom-fields-category").'?action=add&type=user', "add-user-category")
		->addMenu(lang("user-fields"), url_to_pager("admin-user-custom-fields").'?type=user', "user-fields")//->addMenu(lang("add-user-field"), url_to_pager("admin-user-custom-fields").'?action=add&type=user', "add-user-field")
	;


	$settingsMenu = get_menu("admin-menu", "settings");
	foreach(get_settings_menu() as $id => $title) {
		$settingsMenu->addMenu($title, url_to_pager("admin-settings")."?type=".$id);
	}

	//all plugin with settings file
	foreach(get_all_plugins() as $plugin => $info) {
		if(plugin_has_settings($plugin)) {
			$settingsMenu->addMenu($info['title'], url_to_pager("plugins-settings", array("plugin" => $plugin)), $plugin);
		}
	}

	//manage menus
	add_menu("admin-menu", array("id" => "posts", "title" => lang("manage-posts"), "link" => url('admincp/posts'), "icon" => "ion-android-chat"));
	//add_menu("admin-menu", array("id" => "photos", "title" => lang("manage-photos"), "link" => url('admincp/photos'), "icon" => "ion-ios-photos"));

	get_menu("admin-menu", "cms")->addMenu(lang("language-manager"), "#", "admin-language");
	get_menu("admin-menu", "cms")->findMenu("admin-language")->addMenu(lang("language-packs"), url_to_pager("admin-languages"));
	get_menu("admin-menu", "cms")->findMenu("admin-language")->addMenu(lang("phrase-manager"), url_to_pager("admin-languages-phrase"));
	//get_menu("admin-menu", "cms")->findMenu("admin-language")->addMenu(lang("add-phrase"), url_to_pager("admin-languages-phrase")."?action=add");
	get_menu("admin-menu", "cms")->findMenu("admin-language")->addMenu(lang("create-language-pack"), url_to_pager("admin-languages")."?action=create");
	get_menu("admin-menu", "cms")->findMenu("admin-language")->addMenu(lang("import-language-pack"), url_to_pager("admin-languages")."?action=import");
	get_menu("admin-menu", "cms")->findMenu("admin-language")->addMenu(lang("update-language-phrases"), url_to_pager("admin-languages-phrase")."?action=update");
	get_menu("admin-menu", "cms")->addMenu(lang("manage-posts"), url('admincp/posts'), "admin-posts");

	//country manager
	get_menu("admin-menu", "cms")->addMenu(lang("country-manager"), url_to_pager("admin-country-manager"), "admin-country-manager");

	//get_menu("admin-menu", "cms")->addMenu(lang("pages-manager"), "#", "pages-manager");


	//emails management
	get_menu("admin-menu", "settings")->addMenu(lang("emails-manager"), "#", "email-manager");
	get_menu("admin-menu", "settings")->findMenu('email-manager')->addMenu(lang("email-templates"), url_to_pager('admin-email-templates'), "admin-email-templates")
		->addMenu(lang("settings"), url_to_pager('admin-email-settings'), "admin-email-settings");
	/**
	 * Admin themes menu
	 */

	get_menu("admin-menu", "appearance")->addMenu(lang('themes'), url_to_pager('admin-themes', array('type' => 'frontend')), "admin-manage-themes");
    get_menu("admin-menu", "appearance")->addMenu(lang('custom-jc'), url('admincp/custom/script'), "admin-custom-script");
	get_menu("admin-menu", "appearance")->addMenu(lang("settings"), url_to_pager("admin-theme-settings"), 'admin-theme-settings');


	get_menu("admin-menu", "settings")->addMenu(lang("manage"), url_to_pager("manage-plugins"), "manage");
	//get_menu("admin-menu", "plugins")->addMenu(lang("core-plugins"), url_to_pager("manage-plugins").'?core=true', "core");


	//add_menu("admin-menu", array("id" => "annoucement", "title" => lang("annoucement"), "link" => url_to_pager("annoucement") ));
	//add_menu("admin-menu", array("id" => "Help &amp; Support Center", "title" => lang("help-support"), "link" => url_to_pager("support") ));

	//get_menu("admin-menu", "cms")->addMenu(lang("blocks-manager"), url_to_pager("admin-blocks"), "blocks");

	fire_hook("admin-started", null);
	//tools sub-menu

	get_menu("admin-menu", "tools")->addMenu(lang("update-system"), url_to_pager('admin-update'));
	get_menu('admin-menu', 'tools')->addMenu(lang('clear-temp-data'), url_to_pager('admin-temp-clear'));
	get_menu("admin-menu", "tools")->addMenu(lang("ban-filters"), "#", "admin-ban-filters");
	get_menu("admin-menu", "tools")->addMenu(lang("task-scheduler"), url('admincp/run/tasks'), "admin-task");

	get_menu("admin-menu", "tools")->findMenu("admin-ban-filters")->addMenu(lang("usernames"), url_to_pager("admin-ban-filters", array("type" => "usernames")));
	// get_menu("admin-menu", "tools")->findMenu("admin-ban-filters")->addMenu(lang("emails"), url_to_pager("admin-ban-filters", array("type" => "emails")));
	get_menu("admin-menu", "tools")->findMenu("admin-ban-filters")->addMenu(lang("ip-address"), url_to_pager("admin-ban-filters", array("type" => "ip")));
	//get_menu("admin-menu", "tools")->findMenu("admin-ban-filters")->addMenu(lang("display-names"), url_to_pager("admin-ban-filters", array("type" => "names")));
	get_menu("admin-menu", "tools")->findMenu("admin-ban-filters")->addMenu(lang("words"), url_to_pager("admin-ban-filters", array("type" => "words")));

	//add quick admincp dashboard quick links
	add_menu("admincp-quick-link", array('id' => 'general-settings', 'title' => lang('general-settings'), 'link' => url_to_pager("admin-settings")));
	add_menu("admincp-quick-link", array('id' => 'user-profile-fields', 'title' => lang('add-profile-fields'), 'link' => url_to_pager("admin-user-custom-fields").'?action=add&type=user'));
	add_menu("admincp-quick-link", array('id' => 'update-system', 'title' => lang('update-system'), 'link' => url_to_pager('admin-update')));
	add_menu("admincp-quick-link", array('id' => 'update-phrases', 'title' => lang('update-language-phrases'), 'link' => url_to_pager("admin-languages-phrase")."?action=update"));
	add_menu("admincp-quick-link", array('id' => 'manage-plugins', 'title' => lang('manage-plugins'), 'link' => url_to_pager("manage-plugins")));

	register_block_page('account', lang('user-account-settings'));
	register_block_page('saved', lang('user-saved-page'));
	return true;
});

register_filter("admin-login", function($app) {
	$app->setThemeType("backend");
	return true;
});

/**
 * Register blocks
 */
//register_block('account/profile-card', lang('user-profile-card'));
register_block('block/html', 'Html Block', null, array(
	'title' => array(
		'title' => lang('box-title'),
		'description' => lang('box-title-desc'),
		'type' => 'text',
		'value' => ''
	),

	'content' => array(
		'title' => lang('html-content'),
		'description' => lang('html-content-desc'),
		'type' => 'textarea',
		'value' => ''
	)
));

register_hook('system.started', function($app) {
	if(config('pusher-driver', 'ajax') == 'ajax') {
		setPusher(new AjaxPusher());
	}

	if(config('pusher-driver', 'ajax') == 'fcm') {
		setPusher(new FCMPusher());
		if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
            if (is_loggedIn() and config('enable-profile-completion', false) and config('enable-force-profile-completion', false)){
                $profile_check = profile_completion_check();
                $firstSegment = segment(0);
                $allowed_segments = array('account', 'membership', 'logout');
                $allowed_segments = fire_hook('profile.completion.segment.allowed', $allowed_segments);
                if($firstSegment and !in_array($firstSegment, $allowed_segments) and !is_admin() and !$profile_check) {
                    redirect(url("account"));
                }
            }
		}
	}

	if(is_loggedIn()) {
		update_user(array(
			'online_time' => time()
		));
	}
	//register_hook('admin-started', function() {admin_install_restrictions(); });
});

/**
 * Add menus for admin
 */

add_menu('admin-menu', array('id' => 'admin-statistic', 'title' => lang('dashboard'), 'icon' => 'ion-arrow-graph-up-right', 'link' => url_to_pager('admin-statistic')));
add_menu('admin-menu', array('id' => 'admin-users', 'title' => lang('admin-user-manager'), 'link' => '#', 'icon' => 'ion-android-people'));
add_menu('admin-menu', array('id' => 'settings', 'title' => lang('settings'), 'link' => '#', 'icon' => 'ion-android-settings'));
add_menu('admin-menu', array('id' => 'cms', 'title' => lang('site-manager'), 'link' => '#', 'icon' => 'ion-android-options'));
add_menu('admin-menu', array('id' => 'billings', 'title' => lang('billings'), 'link' => '#', 'icon' => 'ion-cash'));
if(plugin_loaded('ads')) {
	add_menu('admin-menu', array('id' => 'monetization', 'title' => lang('monetization'), 'link' => '#', 'icon' => 'ion-podium'));
}
add_menu('admin-menu', array('id' => 'appearance', 'title' => lang('appearance'), 'link' => '#', 'icon' => 'ion-android-color-palette'));
add_menu('admin-menu', array('id' => 'page-builder', 'title' => lang('page-builder'), 'link' => url_to_pager('admin-site-page-builder'), 'icon' => 'ion-flag'));
add_menu('admin-menu', array('id' => 'menu-builder', 'title' => lang('menu-builder'), 'link' => url_to_pager('admin-site-menu-builder'), 'icon' => 'ion-android-menu'));
add_menu('admin-menu', array('id' => 'plugins', 'title' => lang('manager-features'), 'link' => '#', 'icon' => 'ion-map'));
add_menu('admin-menu', array('id' => 'tools', 'title' => lang('maintenance'), 'link' => '#', 'icon' => 'ion-ios-settings'));

Pager::offMenu('admin-menu');

//menu locations
add_menu_location('main-menu', 'main-menu');
//add_menu_location('account-menu', 'account-menu');
add_menu_location('header-account-menu', 'account-menu');
add_menu_location('footer-menu', 'footer-menu');

//add available menus
add_available_menu('home', '', 'ion-home');
add_available_menu('profile', 'me', 'ion-ios-contact-outline');
add_available_menu('find-friends', 'suggestions', 'ion-android-person-add');
add_available_menu('saved', 'saved');

foreach(Pager::getStaticPages() as $page) {
	if(isset($page['title'])) add_available_menu(lang(strtolower($page['title'])), $page['slug']);

}

//hook to prevent profile preview
register_hook('site-preview-profile', function($result) {
	$result['url'] = url('me');
	return $result;
});

register_hook('site-notifications-settings', function() {
	$user_id = get_userid();
	$mailing_list_subscription = mailer()->mailingListSubscription($user_id);
	$view = view('email/subscribe', array('mailing_list_subscription' => $mailing_list_subscription));
	echo $view;
});

register_hook('notification.settings.save', function() {
	$user_id = get_userid();
	$value = input('mailing_list_subscription_status', 1);
	mailer()->mailingListStatusUpdate($user_id, $value);
});

register_hook('theme.settings.form', function() {
	$theme_path = get_active_frontend_theme_path();
	$theme_relative_path = str_replace(path(), '', $theme_path);
	$settings_view = $theme_path.'/html/admincp/settings.phtml';
	if(file_exists($settings_view)) {
		include $settings_view;
	}
});

register_hook('theme.settings.save', function($val) {
	$file_uploads = (array) input('file_upload_extend');
	foreach($file_uploads as $name) {
		$file = input_file($name);
		if($file) {
			$uploader = new Uploader($file, 'file', true);
			if($uploader->passed()) {
				$previous_file = config($name);
				if($previous_file) {
					delete_file(path($previous_file));
				}
				$uploader->setPath('themes/'.get_active_theme('frontend').'/files/')->uploadFile();
				$val[$name] = $uploader->result();
			} else {
				return false;
			}
		}
		unset($file);
	}
	return $val;
});

register_hook('uid.check', function($result, $value, $type = null, $type_id = null) {
	if(!$type || $type == 'site.page') {
		$static_page = Pager::getSitePage($value);
		if($static_page) {
			if(!$type_id || ($type_id && $type_id != $static_page['id'])) {
				$result[0] = false;
			}
		}
	}
	return $result;
});

register_hook('uid.check', function($result, $value, $type = null, $type_id = null) {
	if(!$type || $type == 'user.username') {
		$user = find_user($value);
		if($user) {
			if(!$type_id || ($type_id && $type_id != $user['username'])) {
				$result[0] = false;
			}
		}
	}
	return $result;
});

register_hook('privacy', function($privacy) {
	$privacy['1'] = array(
		'icon' => 'ion-android-globe',
		'title' => lang('public'),
		'type' => 'global'
	);
	return $privacy;
});

register_hook('privacy.sql', function($sql) {
	$sql .= "privacy = 1";
	return $sql;
});

register_hook('entity.info', function($item) {
	if($item['entity_type'] == 'user') {
		$user = find_user($item['entity_id']);
		$entity = array(
			'id' => $user['username'],
			'name' => get_user_name($user),
			'avatar' => get_avatar(200, $user)
		);
	} else {
		$entity = fire_hook('entity.data', array(false), array($item['entity_type'], $item['entity_id']));
	}
	return $entity;
});

register_hook('after-render-js', function($html) {
	if(app()->themeType == 'frontend') {
		if(config('enable-gdpr', 0) && !(isset($_COOKIE['gdpr-acknowledged']) && $_COOKIE['gdpr-acknowledged'])) {
			$html .= '<div id="gdpr"><div class="container">'.lang('gdpr-message').'</div><i class="ion-close close"></i></div>';
		}
	}
	return $html;
});

// Social connections
register_hook('user-profile-about', function($type, $app) {
    add_menu('user-profile-about', array('title' => lang('social-connections'), 'link' => profile_url('about?type=social-connections', $app->profileUser), 'id' => 'social-connections'));
});

// users about profile
register_hook('user-profile-about-content', function($type, $app) {
    if($type == 'social-connections') {
        get_menu("user-profile-about", "social-connections")->setActive();
        return view('profile/about/connections', array('user' => $app->profileUser));
    }
});

// account settings menu
register_hook('account.settings.menu', function() {
    add_menu("account-menu", array("id" => "social-connections", "link" => url_to_pager("account").'?action=social-connections', "title" => lang("social-connections")));
});

// account settings content
register_hook('account.settings', function($action) {
    if($action == 'social-connections') {
        app()->setTitle(lang('socialmore::social-connections'));
        $val = input('val');
        $message = null;
        if($val) {
            save_social_more_settings($val, 'users',get_userid());
        }
        return view('account/connection-form', array('message' => $message));
    }
});

//Auto delete
register_pager("admincp/auto/delete", array('use' => "admincp/temp@auto_delete_data_pager", 'filter' => 'admin-auth', 'as' => 'admin-auto-delete-data'));
get_menu('admin-menu', 'tools')->addMenu(lang("auto-delete-data"), url_to_pager('admin-auto-delete-data'), "admin-auto-delete-data");


// Support links
add_menu("support-quick-link", array('id' => 'crea8social', 'title' => lang('Crea8social'), 'link' => 'http://crea8social.com/'));
add_menu("support-quick-link", array('id' => 'client-area', 'title' => lang('Client Area'), 'link' => 'https://crea8social.com/dashboard'));
add_menu("support-quick-link", array('id' => 'store', 'title' => lang('Plugins & Themes'), 'link' => 'https://crea8social.com/store'));
add_menu("support-quick-link", array('id' => 'documentation', 'title' => lang('Documentation'), 'link' => 'http://docs.crea8social.com/'));
add_menu("support-quick-link", array('id' => 'change-log', 'title' => lang('Changelogs'), 'link' => 'https://crea8social.com/changelog'));

get_menu("admin-menu", "billings")->addMenu(lang('membership-plans'), url('admincp/membership/plans'), "plans");
//get_menu("admin-menu", "billings")->addMenu(lang('membership::invoices'), url('admincp/membership/invoices'), "invoices");
get_menu("admin-menu", "billings")->addMenu(lang('suscribers'), url('admincp/membership/suscribers'), "suscribers");
get_menu("admin-menu", "billings")->addMenu(lang('promotion-code'), '#', "promotion-coupon");
get_menu('admin-menu', 'billings')->findMenu('promotion-coupon')->addMenu(lang('generate-coupon'), url_to_pager('admincp-promotion-code'), 'promotion-code');
get_menu('admin-menu', 'billings')->findMenu('promotion-coupon')->addMenu(lang('view-coupon'), url_to_pager('admincp-promotion-list'), 'promotion-list');
