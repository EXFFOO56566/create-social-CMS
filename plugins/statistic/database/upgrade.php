<?php
function statistic_upgrade_database() {
	register_site_page('statistic-ajax', array('title' => lang('statistic::statistic'), 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'feed', 'plugin::statistic|userstat', 'right');
		Widget::add(null, 'feed', 'plugin::statistic|sitestat', 'right');
	});
}