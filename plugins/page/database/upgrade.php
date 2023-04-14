<?php
function page_upgrade_database() {
	$db = db();
	$db->query("ALTER TABLE `feeds` ADD `page` TEXT NOT NULL AFTER `video`");
	$db->query("CREATE TABLE IF NOT EXISTS `page_invites` (
      `page_id` int(11) NOT NULL,
      `inviter_id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

	register_site_page('pages', array('title' => 'page::pages', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'pages', 'content', 'middle');
		Widget::add(null, 'pages', 'plugin::page|menu', 'right');
		Widget::add(null, 'pages', 'plugin::page|featured', 'right');
		Widget::add(null, 'feed', 'plugin::page|latest', 'right');
		Widget::add(null, 'profile', 'plugin::page|user-profile-likes', 'right');
		Menu::saveMenu('main-menu', 'page::pages', 'pages', 'manual', true, 'ion-card');
	});

	register_site_page('page-create', array('title' => 'page::page-create', 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'page-create', 'content', 'middle');
	});

	register_site_page('page-manage', array('title' => 'page::page-manage', 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'page-manage', 'content', 'middle');
	});

	register_site_page('page-roles', array('title' => 'page::page-roles', 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'page-roles', 'content', 'middle');
	});

	register_site_page('page-manage-fields', array('title' => 'page::page-manage-fields', 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'page-manage-fields', 'content', 'middle');
	});

	register_site_page('page-profile', array('title' => 'page::page-profile', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'page-profile', 'content', 'middle');
		Widget::add(null, 'page-profile', 'plugin::page|info', 'right');
		Widget::add(null, 'page-profile', 'plugin::page|profile-invite', 'right');
		Widget::add(null, 'page-profile', 'plugin::page|profile-photo', 'right');
	});

	register_site_page('page-profile-about', array('title' => 'page::page-profile-about', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'page-profile-about', 'content', 'middle');
		Widget::add(null, 'page-profile-about', 'plugin::page|info', 'right');
		Widget::add(null, 'page-profile-about', 'plugin::page|profile-invite', 'right');
		Widget::add(null, 'page-profile-about', 'plugin::page|profile-photo', 'right');
	});

	register_site_page('page-profile-photos', array('title' => 'page::page-profile-photos', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'page-profile-photos', 'content', 'middle');
		Widget::add(null, 'page-profile-photos', 'plugin::page|info', 'right');
		Widget::add(null, 'page-profile-photos', 'plugin::page|profile-invite', 'right');
		Widget::add(null, 'page-profile-photos', 'plugin::page|profile-photo', 'right');
	});

	register_site_page('page-profile-events', array('title' => 'page::page-profile-events', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'page-profile-events', 'content', 'middle');
		Widget::add(null, 'page-profile-events', 'plugin::page|info', 'right');
		Widget::add(null, 'page-profile-events', 'plugin::page|profile-invite', 'right');
		Widget::add(null, 'page-profile-events', 'plugin::page|profile-photo', 'right');
	});

	register_site_page('page-profile-blogs', array('title' => 'page::page-profile-blogs', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'page-profile-blogs', 'content', 'middle');
		Widget::add(null, 'page-profile-blogs', 'plugin::page|info', 'right');
		Widget::add(null, 'page-profile-blogs', 'plugin::page|profile-invite', 'right');
		Widget::add(null, 'page-profile-blogs', 'plugin::page|profile-photo', 'right');
	});

	register_site_page('page-profile-musics', array('title' => 'page::page-profile-musics', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'page-profile-musics', 'content', 'middle');
		Widget::add(null, 'page-profile-musics', 'plugin::page|info', 'right');
		Widget::add(null, 'page-profile-musics', 'plugin::page|profile-invite', 'right');
		Widget::add(null, 'page-profile-musics', 'plugin::page|profile-photo', 'right');
	});

	register_site_page('page-profile-videos', array('title' => 'page::page-profile-videos', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'page-profile-videos', 'content', 'middle');
		Widget::add(null, 'page-profile-videos', 'plugin::page|info', 'right');
		Widget::add(null, 'page-profile-videos', 'plugin::page|profile-invite', 'right');
		Widget::add(null, 'page-profile-videos', 'plugin::page|profile-photo', 'right');
	});

	register_site_page('page-profile-livestreams', array('title' => 'page::page-profile-livestreams', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'page-profile-livestreams', 'content', 'middle');
		Widget::add(null, 'page-profile-livestreams', 'plugin::page|info', 'right');
		Widget::add(null, 'page-profile-livestreams', 'plugin::page|profile-invite', 'right');
		Widget::add(null, 'page-profile-livestreams', 'plugin::page|profile-photo', 'right');
	});

	db()->query("ALTER TABLE  `pages` ADD  `featured` INT NOT NULL DEFAULT  '0' AFTER  `page_category_id` ;");
	try {
		$db->query("ALTER TABLE  `pages` ADD  `social_info` varchar(1000) NOT NULL ");
		$db->query("ALTER TABLE `pages` ADD `company` VARCHAR(255) NOT NULL AFTER `social_info`, ADD `telephone` VARCHAR(100) NOT NULL AFTER `company`, ADD `location` VARCHAR(255) NOT NULL AFTER `telephone`, ADD `website` VARCHAR(255) NOT NULL AFTER `location`, ADD `target_type` INT(2) NOT NULL AFTER `website`, ADD `target_call` VARCHAR(255) NOT NULL AFTER `target_type`, ADD `visitor_editor` INT(2) NOT NULL DEFAULT '0' AFTER `target_call`;");
	} catch(Exception $e) {
	}
}
 