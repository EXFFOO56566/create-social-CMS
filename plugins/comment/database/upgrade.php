<?php
function comment_upgrade_database() {
	$db = db();
	try {
		$db->query("ALTER TABLE `comments` ADD `postownerId` INT(11) NOT NULL AFTER `comment_id` ;");
	} catch(Exception $e) {}
	try {
		$db->query("ALTER TABLE `comments` ADD `voice` TEXT NOT NULL AFTER `image`;");
	} catch(Exception $e) {}
	try {
		$db->query("ALTER TABLE `comments` ADD `gif` TEXT NOT NULL AFTER `image`;");
	} catch(Exception $e) {}
}