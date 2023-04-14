<?php
function marketplace_upgrade_database() {

	$db = db();

	try {
		$db->query("ALTER TABLE `marketplace_listings` CHANGE `lister_id` `user_id` INT(11) NOT NULL;");
	} catch(Exception $e) {}
	try {
		$db->query("ALTER TABLE `marketplace_listings` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT, CHANGE `nov` `nov` INT(11) UNSIGNED NOT NULL, CHANGE `address` `location` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, ADD `entity_type` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `user_id`, ADD `entity_id` INT(11) NOT NULL AFTER `entity_type`, ADD `privacy` TINYINT(1) NOT NULL AFTER `entity_id`;");
	} catch(Exception $e) {}
	try {
		$db->query("ALTER TABLE `marketplace_listings` CHANGE `nov` `nov` INT(11) UNSIGNED NOT NULL, CHANGE `address` `location` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, ADD `entity_type` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `user_id`, ADD `entity_id` INT(11) NOT NULL AFTER `entity_type`, ADD `privacy` TINYINT(1) NOT NULL AFTER `entity_id`;");
	} catch(Exception $e) {}
	try {
		$db->query("ALTER TABLE `marketplace_listings` CHANGE `address` `location` VARCHAR(256) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL, ADD `entity_type` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `user_id`, ADD `entity_id` INT(11) NOT NULL AFTER `entity_type`, ADD `privacy` TINYINT(1) NOT NULL AFTER `entity_id`;");
	} catch(Exception $e) {}
	try {
		$db->query("ALTER TABLE `marketplace_listings` ADD `entity_type` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `user_id`, ADD `entity_id` INT(11) NOT NULL AFTER `entity_type`, ADD `privacy` TINYINT(1) NOT NULL AFTER `entity_id`;");
	} catch(Exception $e) {}
	try {
		$db->query("UPDATE `marketplace_listings` SET entity_type = 'user' WHERE entity_type = ''");
	} catch(Exception $e) {}
	try {
		$db->query("UPDATE `marketplace_listings` SET entity_id = user_id WHERE entity_id = 0");
	} catch(Exception $e) {}
	try {
		$db->query("UPDATE `marketplace_listings` SET `privacy` = 1 WHERE `privacy` = 0");
	} catch(Exception $e) {}

	try {
		$db->query("ALTER TABLE `feeds` ADD `marketplace` TEXT NOT NULL AFTER `video`");
	} catch(Exception $e) {
	}

	load_functions("marketplace::marketplace");

	register_site_page("marketplace", array('title' => lang('marketplace::marketplace'), 'column_type' => TOP_ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'marketplace', 'content', 'middle');
		Widget::add(null, 'marketplace', 'plugin::marketplace|menu', 'top');
		Menu::saveMenu('main-menu', 'marketplace::marketplace', 'marketplace', 'manual', 1, 'ion-android-cart');
	});

	register_site_page("marketplace-listing", array('title' => lang('marketplace::listing'), 'column_type' => TOP_TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::add(null, 'marketplace-listing', 'content', 'middle');
		Widget::add(null, 'marketplace-listing', 'plugin::marketplace|menu', 'top');
		Widget::add(null, 'marketplace-listing', 'plugin::marketplace|categories', 'left');
		Widget::add(null, 'marketplace-listing', 'plugin::marketplace|latest', 'left');
		Widget::add(null, 'marketplace-listing', 'plugin::marketplace|featured', 'left');
	});

	register_site_page("marketplace-create-listing", array('title' => lang('marketplace::create-listing'), 'column_type' => TOP_TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::add(null, 'marketplace-create-listing', 'content', 'middle');
		Widget::add(null, 'marketplace-create-listing', 'plugin::marketplace|menu', 'top');
		Widget::add(null, 'marketplace-create-listing', 'plugin::marketplace|categories', 'left');
		Widget::add(null, 'marketplace-create-listing', 'plugin::marketplace|latest', 'left');
		Widget::add(null, 'marketplace-create-listing', 'plugin::marketplace|featured', 'left');
	});

	register_site_page("marketplace-edit-listing", array('title' => lang('marketplace::edit-listing'), 'column_type' => TOP_TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::add(null, 'marketplace-edit-listing', 'content', 'middle');
		Widget::add(null, 'marketplace-edit-listing', 'plugin::marketplace|menu', 'top');
		Widget::add(null, 'marketplace-edit-listing', 'plugin::marketplace|categories', 'left');
		Widget::add(null, 'marketplace-edit-listing', 'plugin::marketplace|latest', 'left');
		Widget::add(null, 'marketplace-edit-listing', 'plugin::marketplace|featured', 'left');
	});

	register_site_page("marketplace-delete-listing", array('title' => lang('marketplace::delete-listing'), 'column_type' => TOP_TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::add(null, 'marketplace-delete-listing', 'content', 'middle');
		Widget::add(null, 'marketplace-delete-listing', 'plugin::marketplace|menu', 'top');
		Widget::add(null, 'marketplace-delete-listing', 'plugin::marketplace|categories', 'left');
	});

	register_site_page("marketplace-add-images", array('title' => lang('marketplace::add-listing-picture'), 'column_type' => TOP_TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::add(null, 'marketplace-add-images', 'content', 'middle');
		Widget::add(null, 'marketplace-add-images', 'plugin::marketplace|menu', 'top');
		Widget::add(null, 'marketplace-add-images', 'plugin::marketplace|categories', 'left');
	});

	register_site_page("marketplace-delete-image", array('title' => lang('marketplace::delete-listing-picture'), 'column_type' => TOP_TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::add(null, 'marketplace-delete-image', 'content', 'middle');
		Widget::add(null, 'marketplace-delete-image', 'plugin::marketplace|menu', 'top');
		Widget::add(null, 'marketplace-delete-image', 'plugin::marketplace|categories', 'left');
	});
}