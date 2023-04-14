<?php
function notification_upgrade_database() {
	$db = db();

	try {
		$db->query("ALTER TABLE  `users` ADD  `fcm_token` VARCHAR(255) COLLATE utf8_unicode_ci NOT NULL");
	} catch(Exception $e) {
	}

	register_site_page("notifications", array('title' => 'notifications-page', 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'notifications', 'content', 'middle');
	});
}