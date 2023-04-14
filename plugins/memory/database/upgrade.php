<?php
function memory_upgrade_database() {
	$db = db();

	$db->query("ALTER TABLE `memories` CHANGE `type` `type` VARCHAR(32) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;");
	$db->query("UPDATE memories SET type = 'birthday_reminder' WHERE type = 'birthday_reminde'");

	register_site_page('on-this-day', array('title' => lang('memory::on-this-day'), 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'on-this-day', 'content', 'middle');
	});
}