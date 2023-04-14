<?php
function ads_upgrade_database() {
	$db = db();

	$db->query("CREATE TABLE IF NOT EXISTS `ads_service`(
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`title` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
		`code` TEXT COLLATE utf8_unicode_ci NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

	$db->query("CREATE TABLE IF NOT EXISTS `ads_service_positions`(
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`service_id` INT(11) NOT NULL,
		`position` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

	try {
		db()->query("ALTER TABLE `ads_plans` ADD `banner_width` INT(11) UNSIGNED NOT NULL AFTER `price`, ADD `banner_height` INT(11) UNSIGNED NOT NULL AFTER `banner_width`;");
	} catch(Exception $e) {}

	try {
		db()->query("ALTER TABLE `ads` ADD `video` text COLLATE utf8_unicode_ci, ADD `ads_class` varchar(35) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'picture' ;");
	} catch(Exception $e) {}

	register_site_page('ads-manage', array('title' => lang('ads::manage-ads'), 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'ads-manage', 'content', 'middle');
		Menu::saveMenu('header-account-menu', 'ads::create-ads', 'ads/create');
		Menu::saveMenu('header-account-menu', 'ads::manage-ads', 'ads');
		Widget::add(null, 'feed', 'plugin::ads|ads', 'right');
	});

	register_site_page('ads-create', array('title' => lang('ads::ads-create'), 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'ads-create', 'content', 'middle');
	});

	register_site_page('ads-edit', array('title' => lang('ads::ads-edit'), 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'ads-edit', 'content', 'middle');
	});

	try {
		db()->query("ALTER TABLE `ads` ADD `ads_class` varchar(255) UNSIGNED NOT NULL ;");
	} catch(Exception $e) {}
}