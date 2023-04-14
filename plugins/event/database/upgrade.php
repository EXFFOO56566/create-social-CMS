<?php
function event_upgrade_database() {
	register_site_page('events', array('title' => 'event::events', 'column_type' => TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::clear('events');
		Widget::add(null, 'events', 'plugin::event|menu', 'left');
		Widget::add(null, 'events', 'content', 'middle');
		Widget::add(null, 'events', 'plugin::event|find-events', 'left');
		Widget::add(null, 'events', 'plugin::event|birthdays', 'left');
		Menu::saveMenu('main-menu', 'event::events', 'events', 'manual', true, 'ion-android-calendar', 0, null, false);
	}, '7.0');

	register_site_page('events-calender', array('title' => 'event::events', 'column_type' => TOP_THREE_COLUMN_LAYOUT), function() {
		Widget::clear('events-calender');
		Widget::add(null, 'events-calender', 'plugin::event|calender', 'top');
		Widget::add(null, 'events-calender', 'plugin::event|menu', 'left');
		Widget::add(null, 'events-calender', 'content', 'middle');
		Widget::add(null, 'events-calender', 'plugin::event|find-events', 'right');
		Widget::add(null, 'events-calender', 'plugin::event|birthdays', 'right');
	}, '7.0');

	register_site_page('event-profile', array('title' => 'event::events-profile-page', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('event-profile');
		Widget::add(null, 'event-profile', 'plugin::event|profile-stat', 'right');
		Widget::add(null, 'event-profile', 'plugin::event|profile-invite', 'right');
		Widget::add(null, 'event-profile', 'content', 'middle');
	}, '7.0');

	register_site_page('event-create', array('title' => 'event::event-create', 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::clear('event-create');
		Widget::add(null, 'event-create', 'content', 'middle');
	}, '7.0');

	register_site_page('event-subscribers', array('title' => 'event::event-subscribers', 'column_type' => TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::clear('event-subscribers');
		Widget::add(null, 'event-subscribers', 'plugin::event|menu', 'right');
		Widget::add(null, 'event-subscribers', 'content', 'middle');
	}, '7.0');

	$db = db();
	try {
		$db->query("ALTER TABLE `events` ADD `entity_type` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `user_id`");
	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `events` ADD `entity_id` INT(11) NOT NULL AFTER `entity_type`");
	} catch(Exception $e) {
	}
	$db->query("UPDATE `events` SET entity_type = 'user' WHERE entity_type = ''");
	$db->query("UPDATE `events` SET entity_id = user_id WHERE entity_id = 0");
	try {
		$db->query("ALTER TABLE `events` ADD `version` INT NOT NULL DEFAULT '0';");
	} catch(Exception $e) {
	}
	try {
		$db->query("UPDATE `events` SET privacy = 1, version = 1 WHERE version = 0 AND privacy = 0;");
	} catch(Exception $e) {
	}
	try {
		$db->query("UPDATE `events` SET privacy = 6, version = 1 WHERE version = 0 AND privacy = 1;");
	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `event_invites` ADD `interested` INT(1) NOT NULL ");
	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `event_categories` ADD `icon` VARCHAR (255) NOT NULL ");
	} catch(Exception $e) {
	}
	try {
        $db->query("ALTER TABLE `feeds` ADD `event` TEXT NOT NULL AFTER `video`");
	} catch(Exception $e) {
	}
}