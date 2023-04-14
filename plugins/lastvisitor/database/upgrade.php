<?php
function lastvisitor_upgrade_database() {
	$db = db();
	$db->query("CREATE TABLE IF NOT EXISTS `lastvisitor_profile_view` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `viewer_id`  varchar(100) NOT NULL,
            `viewed_id`  varchar(100) NOT NULL,
            `view_date`  varchar(100) NOT NULL,
            `gender`  varchar(100) NULL,
            `has_avatar`  varchar(100) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;"
	);
	lastvisitor_dump_site_pages();
}

function lastvisitor_dump_site_pages() {
	register_site_page('lastvisitor', array('title' => lang('poll::lastvisitor'), 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'lastvisitor', 'content', 'middle');
		Widget::add(null, 'lastvisitor', 'plugin::blog|latest', 'right');
		Widget::add(null, 'lastvisitor', 'plugin::music|latest', 'right');
		Widget::add(null, 'lastvisitor', 'plugin::video|latest', 'right');
	});
}