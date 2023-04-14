<?php
function search_upgrade_database() {
	register_site_page("search", array('title' => 'search-page', 'column_type' => TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::add(null, 'search', 'content', 'middle');
		Widget::add(null, 'search', 'plugin::search|menu', 'left');
	});
}