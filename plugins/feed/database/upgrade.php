<?php
function feed_upgrade_database() {
	$db = db();
	try {
		$db->query("ALTER TABLE `feeds` ADD `background` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `feed_content`");
	} catch(Exception $e) {}
	try {
		$db->query("ALTER TABLE `feeds` ADD `version` INT NOT NULL DEFAULT '0';");
	} catch(Exception $e) {}
	try {
		$db->query("UPDATE `feed` SET privacy = 1, version = 1 WHERE version = 0 AND privacy = 5;");

	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `feeds` ADD `gif` TEXT NOT NULL AFTER `video`;");
	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `feeds` ADD `voice` TEXT NOT NULL AFTER `video`;");
	} catch(Exception $e) {}
}