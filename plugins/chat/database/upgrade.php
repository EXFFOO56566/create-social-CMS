<?php
function chat_upgrade_database() {
	register_site_page("messages", array('title' => 'chat::messages', 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'messages', 'content', 'middle');
	});

	$db = db();
	try {
		$db->query("ALTER TABLE `conversation_messages` ADD `voice` TEXT NOT NULL AFTER `image`;");
	} catch(Exception $e) {}
	try {
		$db->query("ALTER TABLE `conversation_messages` ADD `gif` TEXT NOT NULL AFTER `image`;");
	} catch(Exception $e) {}

}