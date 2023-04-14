<?php
function group_upgrade_database() {
	register_site_page('group-manage', array('title' => 'group::groups', 'column_type' => TOP_TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::clear('group-manage');
		Widget::add(null, 'group-manage', 'plugin::group|menu', 'top');
		Widget::add(null, 'group-manage', 'plugin::group|discover', 'top');
		Widget::add(null, 'group-manage', 'content', 'middle');
		Widget::add(null, 'group-manage', 'plugin::group|top', 'left');
		Widget::add(null, 'group-manage', 'plugin::group|featured', 'left');

		Menu::saveMenu('main-menu', 'group::groups', 'groups', 'manual', 1, 'ion-ios-people');
	}, '7.0');

	register_site_page('group-create', array('title' => 'group::group-create', 'column_type' => TOP_TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::clear('group-create');
		Widget::add(null, 'group-create', 'plugin::group|menu', 'top');
		Widget::add(null, 'group-create', 'content', 'middle');
		Widget::add(null, 'group-create', 'plugin::group|top', 'left');
		Widget::add(null, 'group-create', 'plugin::group|featured', 'left');
	}, '7.0');

	register_site_page('group-profile', array('title' => 'group::group-profile', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('group-profile');
		Widget::add(null, 'group-profile', 'content', 'middle');
		Widget::add(null, 'group-profile', 'plugin::group|info', 'right');
		Widget::add(null, 'group-profile', 'plugin::group|members', 'right');
	}, '7.0');

	register_site_page('group-category-profile', array('title' => 'group::group-category-category', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('group-category-profile');
		Widget::add(null, 'group-category-profile', 'content', 'middle');
		Widget::add(null, 'group-category-profile', 'plugin::group|top', 'right');
		Widget::add(null, 'group-category-profile', 'plugin::group|featured', 'right');
	}, '7.0');

	register_site_page('group-profile-photos', array('title' => 'group::group-profile-photos', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'group-profile-photos', 'content', 'middle');
		Widget::add(null, 'group-profile-photos', 'plugin::group|info', 'right');
		Widget::add(null, 'group-profile-photos', 'plugin::group|members', 'right');
	});

	register_site_page('group-profile-events', array('title' => 'group::group-profile-events', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'group-profile-events', 'content', 'middle');
		Widget::add(null, 'group-profile-events', 'plugin::group|info', 'right');
		Widget::add(null, 'group-profile-events', 'plugin::group|members', 'right');
	});

	register_site_page('group-profile-blogs', array('title' => 'group::group-profile-blogs', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'group-profile-blogs', 'content', 'middle');
		Widget::add(null, 'group-profile-blogs', 'plugin::group|info', 'right');
		Widget::add(null, 'group-profile-blogs', 'plugin::group|members', 'right');
	});

	register_site_page('group-profile-musics', array('title' => 'group::group-profile-musics', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'group-profile-musics', 'content', 'middle');
		Widget::add(null, 'group-profile-musics', 'plugin::group|info', 'right');
		Widget::add(null, 'group-profile-musics', 'plugin::group|members', 'right');
	});

	register_site_page('group-profile-videos', array('title' => 'group::group-profile-videos', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'group-profile-videos', 'content', 'middle');
		Widget::add(null, 'group-profile-videos', 'plugin::group|info', 'right');
		Widget::add(null, 'group-profile-videos', 'plugin::group|members', 'right');
	});

	register_site_page('group-profile-livestreams', array('title' => 'group::group-profile-livestreams', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'group-profile-livestreams', 'content', 'middle');
		Widget::add(null, 'group-profile-livestreams', 'plugin::group|info', 'right');
		Widget::add(null, 'group-profile-livestreams', 'plugin::group|members', 'right');
	});

	$db = db();
	$db->query("CREATE TABLE IF NOT EXISTS `group_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) NOT NULL,
        `slug` varchar(255) NOT NULL,
        `image` varchar(255) NOT NULL,
        PRIMARY KEY (`id`)
      ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1");
	try {
        $db->query("ALTER TABLE `feeds` ADD `a_group` TEXT NOT NULL AFTER `video`");
	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `groups` ADD `category_id` INT NOT NULL;");
	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `groups` ADD `featured` TINYINT NOT NULL;");
	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `groups` ADD `version` INT NOT NULL;");
	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `groups` ADD `recommend` INT(11)  NOT NULL AFTER `featured`");
	} catch(Exception $e) {
	}
	try {
		$db->query("UPDATE `groups` SET privacy = 1, version = 1 WHERE version = 0 AND privacy = 1;");
	} catch(Exception $e) {
	}
	try {
		$db->query("UPDATE `groups` SET privacy = 6, version = 1 WHERE version = 0 AND privacy = 2;");
	} catch(Exception $e) {
	}
	try {
		$db->query("UPDATE `groups` SET privacy = 1, version = 2 WHERE version = 1 AND privacy = 1;");
	} catch(Exception $e) {
	}
	try {
		$db->query("UPDATE `groups` SET privacy = 4, version = 2 WHERE version = 1 AND privacy = 6;");
	} catch(Exception $e) {
	}
	try {
		$db->query("UPDATE `feeds` SET entity_type = type, entity_type = type_id WHERE type = 'group'");
	} catch(Exception $e) {
	}
	try{
		if($db->query("SELECT COUNT(id) FROM group_categories")->fetch_row()[0] == 0) {
		$preloaded_categories = array('Sport', 'Entertainment', 'Love');
		$images = array("");
		$images[0] = img("group::images/group-sport.jpg");
		$images[1] = img("group::images/group-entertainment.jpg");
		$images[2] = img("group::images/group-love.jpg");
		$i = 0;
		foreach($preloaded_categories as $preloaded_category) {
			foreach(get_all_languages() as $language) {
				$post_vars['title'][$language['language_id']] = $preloaded_category;
			}
			$expected = array('title' => '');
			extract(array_merge($expected, $post_vars));
			$titleSlug = 'group_category_'.md5(time().serialize($post_vars)).'_title';
			foreach($title as $langId => $t) {
				add_language_phrase($titleSlug, $t, $langId, 'group');
			}
			foreach($title as $langId => $t) {
				(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId, 'group') : add_language_phrase($titleSlug, $t, $langId, 'group');
			}
			$db->query("INSERT INTO group_categories(slug, title,image) VALUES('".trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', lang($titleSlug))), '-')."', '".$titleSlug."', '".$images[$i]."')");
			$i++;
		}
	}
	} catch (Exception $e){}
}