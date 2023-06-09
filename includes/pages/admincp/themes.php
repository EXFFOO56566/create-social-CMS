<?php
load_functions("themes");
function setting_pager($app) {
	$app->setTitle(lang('theme-settings'));

	get_menu("admin-menu", "appearance")->setActive();
	get_menu("admin-menu", "appearance")->findMenu("admin-theme-settings")->setActive();

	$val = input('val');
	$message = null;
	if($val) {
		CSRFProtection::validate();
		$saved = save_theme_settings($val);
		if($saved) {
			//redirect with success message
			return redirect_to_pager("admin-theme-settings", array(), admin_flash_message(lang("theme-settings-changed")));
		} else {

		}
	}

	return $app->render(view('themes/setting'));
}

function themes_pager($app) {
	$type = segment(2, 'frontend');
	$action = input("action", "list");
	$message = "";
	$app->setTitle(lang($type).'-'.lang('themes'));

	get_menu("admin-menu", "appearance")->setActive();
	get_menu("admin-menu", "appearance")->findMenu("admin-manage-themes")->setActive();

	if($action == 'activate') {
		$theme = input("theme");
		activate_theme($type, $theme);
		$message = "New Theme Selected";
	}

	return $app->render(view("themes/content", array('type' => $type)));
}

function customize_themes_pager($app) {
	$type = segment(3);
	$theme = segment(4);
	$app->setTitle(lang('customize-theme'));
	$themesPath = config("themes_dir").$type.'/'.$theme.'/';
	$optionFile = $themesPath.'/options.php';
	if(theme_not_exists($theme, $type) or !file_exists($optionFile)) {
		return redirect_to_pager('admin-themes', array('type' => $type));
	}

	$val = input("val");

	if($val) {
		CSRFProtection::validate();
		//we are saving the settings
		save_admin_settings($val);
	}

	$settings = include_once $optionFile;
	return $app->render(view("settings/content", array(
		'settings' => $settings['settings'],
		'title' => $settings['title'],
		'description' => $settings['description']
	)));

	return $app->render('');
}

function editor_pager($app) {
	$app->setTitle(lang('site-editor'));
	$type = input('type', 'themes');
	$page = input('page', 'home');
	get_menu("admin-menu", "cms")->setActive();
	return $app->render(view("themes/editor/index", array('type' => $type, 'page' => $page)));
}

function save_layout_pager($app) {
	CSRFProtection::validate(false);
	/**
	 * @var $page
	 * @var $column
	 */
	extract(input('val'));
	$top = input('top');
	$left = input('left');
	$middle = input('middle');
	$right = input('right');
	$bottom = input('bottom');
	$deleted = input('deleted');
	if($deleted) {
		foreach($deleted as $id) {
			db()->query("DELETE FROM blocks WHERE id='{$id}' AND page_id='{$page}'");
		}
	}

	/**
	 * Lets add the widgets to each location
	 */
	foreach(array('top', 'left', 'middle', 'right', 'bottom') as $position) {
		if($$position) {
			$ids = array();
			foreach($$position as $id => $detail) {
				$ids[] = $id;
				$settings = (isset($detail['settings'])) ? $detail['settings'] : '';
				Widget::add($id, $page, $detail['widget'], $position, $settings);
			};
			$i = 1;
			foreach($ids as $id) {
				db()->query("UPDATE blocks SET sort='{$i}' WHERE id='{$id}'");
				$i++;
			}
		}
	}
	forget_cache("page-widgets-".$page.'-top');
	forget_cache("page-widgets-".$page.'-left');
	forget_cache("page-widgets-".$page.'-middle');
	forget_cache("page-widgets-".$page.'-right');
	forget_cache("page-widgets-".$page.'-bottom');
	forget_cache("page-widgets-".$page.'-all');

	//lets update the column for this page
	db()->query("UPDATE static_pages SET column_type='{$column}' WHERE slug='{$page}'");
	forget_cache("site-pages");
	return true;
}

function load_widget_settings_pager($app) {
	CSRFProtection::validate(false);
	$widget = input('widget');
	$widget = Widget::get($widget);
	$oldSettings = input('settings');
	$old = ($oldSettings) ? perfectUnserialize($oldSettings) : array();
	if(isset($widget['settings']) and $widget['settings']) {
		$settings = $widget['settings'];
		return view('themes/editor/widget/settings', array('settings' => $settings, 'old' => $old));
	}
}

function layout_load_pager($app) {
	CSRFProtection::validate(false);
	echo view('themes/editor/layout', array('page' => input('page')));
}

function save_theme_settings_pager($app) {
	CSRFProtection::validate(false);
	$val = input('val', null, array('content'));

	if($val) {
		CSRFProtection::validate();
		//we are saving the settings
		save_admin_settings($val);
	}

	return true;
}

function save_widget_settings_pager($app) {
	CSRFProtection::validate(false);
	$val = input('val', null, array('content'));
	if(isset($val['content'])) {
		$val['content'] = stripslashes($val['content']);
		$val['content'] = preg_replace('/\/\/ \<\!\[CDATA\[(.*?)\/\/ \]\]\>/s', '$1', $val['content']);
	}
	return perfectSerialize($val);
}

function load_page_info_pager($app) {
	CSRFProtection::validate(false);
	$page = input('id');
	$pageInfo = Pager::getSitePage($page);
	return view("themes/editor/page/info", array('pageInfo' => $pageInfo));
}

function save_page_info_pager($app) {
	CSRFProtection::validate(false);
	$val = input('val', null, array('content'));
	if($val) {
		$expected = array(
			'title' => array(),
			'description' => '',
			'keywords' => '',
			'content' => ''
		);

		/**
		 * @var $title
		 * @var $description
		 * @var $keywords
		 * @var $slug
		 * @var $content
		 * @var $id
		 */
		extract(array_merge($expected, $val));
		$info = Pager::getSitePage($id);

		$old_slug = $info['slug'];
		$slug = isset($slug) ? unique_slugger($slug, 'site.page', $info['id']) : unique_slugger($info['slug'], 'site.page', $info['id']);

		if($info['page_type'] == 'auto') {
			$title_slug = $info['title'];
			$title = input('val.title', null, false);
			$title = is_array($title) ? $title : array('english' => $title);
			$english_title = $title['english'];
			foreach($title as $lang_id => $t) {
				if(!$t) $t = $english_title;
				phrase_exists($lang_id, $title_slug) ? update_language_phrase($title_slug, $t, $lang_id) : add_language_phrase($title_slug, $t, $lang_id, 'static-page');
			}
		}
		$content_slug = !trim($info['content']) || preg_match('/\s/', $info['content']) ? 'static_page_'.md5(time().serialize($val)).'_content' : $info['content'];
		$content = input('val.content', null, false);
		$content = is_array($content) ? $content : array('english' => $content);
		$english_content = $content['english'];
		$english_content = preg_replace('/\/\/ \<\!\[CDATA\[(.*?)\/\/ \]\]\>/s', '$1', $english_content);
		foreach($content as $lang_id => $t) {
			if(!$t) {
				$t = $english_content;
			}
			$t = preg_replace('/\/\/ \<\!\[CDATA\[(.*?)\/\/ \]\]\>/s', '$1', $t);
			phrase_exists($lang_id, $content_slug) ? update_language_phrase($content_slug, $t, $lang_id) : add_language_phrase($content_slug, $t, $lang_id, 'static-page');
		}
		db()->query("UPDATE static_pages SET slug = '".$slug."', content = '".$content_slug."', description = '".$description."', keywords = '".$keywords."' WHERE id = ".$info['id']);
		forget_cache('static_pages');
		forget_cache('site-pages');
		forget_cache('static_pages_footer');
		forget_cache('static_pages_explore');
		$page_info = Pager::getSitePage($slug);
		$page_info['old_slug'] = $old_slug;
		return json_encode($page_info);
	}
}

function add_page_info_pager($app) {
	CSRFProtection::validate(false);
	$val = input('val', null, false);
	$title = input('val.title', null, false);
	$content = input('val.content', null, false);
	$val['title'] = $title;
	$val['content'] = $content;
	if(input('val.slug')) {
		$val['slug'] = input('val.slug', null, false);
	}
	return Pager::addSitePage(null, $val);
}

function change_menu_location_pager($app) {
	CSRFProtection::validate(false);
	echo view("site_menu/builder/location", array('location' => input('location')));
}

function add_menu_pager($app) {
	CSRFProtection::validate(false);
	$title = input('title');
	$link = input('link');
	$icon = input('icon');
	$type = input('type');
	$ajax = input('ajax');
	$tab = input('tab');
	$id = input('id');
	$location = input('location');
	Menu::saveMenu($location, $title, $link, $type, $ajax, $icon, $tab, $id);
	return true;
}

function sort_menu_pager($app) {
	$location = input('location');
	$data = input("data");
	$i = 0;
	foreach($data as $ids) {
		list($id, $m) = explode('-', $ids);
		db()->query("UPDATE menus SET menu_order='{$i}' WHERE menu_location='{$location}' AND id='{$id}'");
		$i++;
	}
	forget_cache("site-menus-{$location}");
}

function add_link_menu_pager($app) {
	CSRFProtection::validate(false);
	$val = input('val');
	$expected = array(
		'title' => array(),
		'link' => '',
		'icon' => '',
		'newtab' => '',
		'type' => 'auto',
		'ajaxify' => 1,
		'location' => ''
	);
	/**
	 * @var $title
	 * @var $link
	 * @var $icon
	 * @var $newtab
	 * @var $ajaxify
	 * @var $type
	 * @var $location
	 */
	if(!isset($val['ajaxify'])) $val['ajaxify'] = 0;
	extract(array_merge($expected, $val));
	$title_slug = 'menu_'.md5(time().serialize($val)).'_title';
	$englishValue = $title['english'];
	foreach($title as $langId => $t) {
		if(!$t) $t = $englishValue;
		add_language_phrase($title_slug, $t, $langId, 'menu');
	}

	$id = time();
	Menu::saveMenu($location, $title_slug, $link, $type, $ajaxify, $icon, $newtab, $id);
	return json_encode(array(
		'id' => $id,
		'title' => get_phrase('english', $title_slug),
	));
}

function delete_menu_pager($app) {
	CSRFProtection::validate(false);
	$id = input('id');
	$location = input('location');
	db()->query("DELETE FROM menus WHERE id='{$id}'");
	forget_cache("site-menus-{$location}");
}

function edit_menu_pager($app) {
	CSRFProtection::validate(false);
	$id = input('id');
	$menu = Menu::findSaveMenu($id);
	if($menu) return view("site_menu/edit", array('menu' => $menu));
}

function save_menu_pager($app) {
	CSRFProtection::validate(false);
	$val = input('val');
	$expected = array(
		'title' => array(),
		'link' => '',
		'icon' => '',
		'newtab' => 0,
		'type' => 'auto',
		'ajaxify' => 1,
		'location' => '',
		'id' => ''
	);
	/**
	 * @var $title
	 * @var $link
	 * @var $icon
	 * @var $newtab
	 * @var $ajaxify
	 * @var $type
	 * @var $location
	 * @var $id
	 */
	if(!isset($val['ajaxify'])) $val['ajaxify'] = 0;
	if(!isset($val['open_new_tab'])) $val['open_new_tab'] = 0;

	extract(array_merge($expected, $val));
	$info = Menu::findSaveMenu($id);
	$slug = $info['title'];

	if($info['type'] == 'auto') {
		$englishValue = $title['english'];
		foreach($title as $langId => $t) {
			if(!$t) $t = $englishValue;
			(phrase_exists($langId, $slug)) ? update_language_phrase($slug, $t, $langId) : add_language_phrase($slug, $t, $langId, 'menu');
		}
	}

	db()->query("UPDATE menus SET link='{$link}',open_new_tab='{$newtab}',ajaxify='{$ajaxify}',icon='{$icon}' WHERE id='{$id}'");
	forget_cache("site-menus-{$location}");
	return json_encode(array(
		'id' => $id,
		'title' => get_phrase('english', $info['title']),
	));
}

function site_preview_pager($app) {
	$page = input('page');
	$theme = input('theme');
	$url = fire_hook('site-preview-'.$page, array('status' => 1, 'message' => 'No Preview Found for this page', 'url' => url_to_pager($page)));
	if($url['status'] == 0) {
		echo $url['message'];
	} else {
		$url = $url['url'];
	}
	$url = ($url) ? $url : url();
	$url .= "?theme=".$theme;
	return redirect($url);
}
function customize_script_pager($app){
    $val = input('val');
    if ($val){
        save_admin_settings($val);
    }
    return $app->render(view('themes/custom'));
}