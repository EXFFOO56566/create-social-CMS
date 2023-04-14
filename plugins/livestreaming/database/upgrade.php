<?php

function livestreaming_upgrade_database() {
	$db = db();
	$db->query("UPDATE livestreams SET privacy = 1 WHERE privacy = 0");

	register_site_page('livestreams', array('title' => 'livestreaming::livestreams-page', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'livestreams', 'content', 'middle');
		Widget::add(null, 'livestreams', 'plugin::livestreaming|menu', 'right');
		Widget::add(null, 'livestreams', 'plugin::livestreaming|ongoing', 'right');
		Widget::add(null, 'livestreams', 'plugin::livestreaming|featured', 'right');
		Widget::add(null, 'livestreams', 'plugin::livestreaming|top', 'right');
		Widget::add(null, 'profile', 'plugin::livestreaming|profile-recent', 'right');
		Widget::add(null, 'feed', 'plugin::livestreaming|ongoing', 'right');
		Menu::saveMenu('main-menu', 'livestreaming::livestreams', 'livestreams', 'manual', true, 'ion-radio-waves');
	});
	register_site_page('livestream-start', array('title' => 'livestreaming::livestreams-start-page', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'livestream-start', 'content', 'middle');
		Widget::add(null, 'livestream-start', 'plugin::livestreaming|menu', 'right');
		Widget::add(null, 'livestream-page', 'plugin::livestreaming|ongoing', 'right');
	});
	register_site_page('livestream-edit', array('title' => 'livestreaming::livestreams-edit-page', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'livestream-edit', 'content', 'middle');
		Widget::add(null, 'livestream-edit', 'plugin::livestreaming|menu', 'right');
		Widget::add(null, 'livestream-page', 'plugin::livestreaming|ongoing', 'right');
	});
	register_site_page('livestream-page', array('title' => 'livestreaming::view-livestream-page', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'livestream-page', 'content', 'middle');
		Widget::add(null, 'livestream-page', 'plugin::livestreaming|menu', 'right');
		Widget::add(null, 'livestream-page', 'plugin::livestreaming|ongoing', 'right');
		Widget::add(null, 'livestream-page', 'plugin::livestreaming|featured', 'right');
	});
}
