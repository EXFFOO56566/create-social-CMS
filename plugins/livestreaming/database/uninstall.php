<?php
function livestreaming_uninstall_dabatase() {
	$db = db();

	$db->query("DELETE FROM `plugins` WHERE `plugins`.`id` = 'livestreams'");

	$db->query("DROP TABLE `livestreams`");

	$db->query("DROP TABLE `livestream_servers`");

	$db->query("DROP TABLE `livestream_categories`");
}