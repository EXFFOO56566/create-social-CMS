<?php
function getstarted_upgrade_database() {
	register_site_page("signup-welcome", array('title' => 'getstarted', 'column_type' => THREE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'signup-welcome', 'content', 'middle');
	});
}