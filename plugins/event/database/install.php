<?php
function event_install_database() {
	$db = db();

	$db->query("CREATE TABLE IF NOT EXISTS `events` (
		`event_id` int(11) NOT NULL AUTO_INCREMENT,
		`user_id` int(11) NOT NULL,
		`entity_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`entity_id` int(11) NOT NULL,
		`event_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`event_desc` text COLLATE utf8_unicode_ci NOT NULL,
		`category_id` int(11) NOT NULL,
		`privacy` int(11) NOT NULL DEFAULT '0',
		`event_cover` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`event_cover_resized` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`location` text COLLATE utf8_unicode_ci NOT NULL,
		`address` text COLLATE utf8_unicode_ci NOT NULL,
		`start_time` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`end_time` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`invites` text COLLATE utf8_unicode_ci NOT NULL,
		`time` int(11) NOT NULL,
		PRIMARY KEY (`event_id`),
		KEY `user_id` (`user_id`,`event_title`,`category_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5;");

	$db->query("CREATE TABLE IF NOT EXISTS `event_categories` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`title` varchar(255) NOT NULL,
		`category_order` int(11) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3;");

	/** @var $title */

	if($db->query("SELECT COUNT(id) FROM event_categories")->fetch_row()[0] == 0) {
		$preloaded_categories = array('Editorial', 'Blog post, Rentals');
		$i = 1;
		foreach($preloaded_categories as $preloaded_category) {
			foreach(get_all_languages() as $language) {
				$post_vars['title'][$language['language_id']] = $preloaded_category;
			}
			$expected = array('title' => '');
			extract(array_merge($expected, $post_vars));
			$titleSlug = 'event_category_'.md5(time().serialize($post_vars)).'_title';
			foreach($title as $langId => $t) {
				add_language_phrase($titleSlug, $t, $langId, 'event');
			}
			foreach($title as $langId => $t) {
				(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId, 'event') : add_language_phrase($titleSlug, $t, $langId, 'event');
			}
			$db->query("INSERT INTO event_categories(title, category_order) VALUES('".$titleSlug."', '".$i."')");
			$i++;
		}
	}

	$db->query("CREATE TABLE IF NOT EXISTS `event_invites` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`event_id` int(11) NOT NULL,
		`user_id` int(11) NOT NULL,
		`rsvp` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5;");
}