<?php
function activity_upgrade_database() {
	register_site_page("activities", array('title' => 'activity::activities', 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'activities', 'content', 'middle');
		Menu::saveMenu('header-account-menu', 'activity::activity-log', 'activities');
		Widget::add(null, 'activities', 'plugin::relationship|suggestions', 'right');
	});
}