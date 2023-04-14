<?php

function livestreaming_install_database() {
	$db = db();

	$db->query("CREATE TABLE IF NOT EXISTS `livestreams` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`broadcast_token` varchar(255) NULL,
		`title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`description` text COLLATE utf8_unicode_ci NOT NULL,
		`user_id` int(11) NOT NULL,
		`entity_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`entity_id` int(11) NOT NULL,
		`preview_path` text COLLATE utf8_unicode_ci NULL,
		`category_id` int(11) NOT NULL,
		`media_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`status` tinyint(1) NOT NULL DEFAULT '0',
		`file_path` text COLLATE utf8_unicode_ci NULL,
		`view_count` int(11) NOT NULL DEFAULT '0',
		`featured` int(11) NOT NULL DEFAULT '0',
		`privacy` tinyint(11) NOT NULL DEFAULT '1',
		`auto_posted` INT NOT NULL DEFAULT '0',
		`start_time` int(11) NOT NULL,
		`end_time` int(11) NULL,
		`updated_last` int(11) NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=17;");

	$db->query("CREATE TABLE IF NOT EXISTS `livestream_servers` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`address` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`port` int(11) NULL,
		`type` varchar(50) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7;");

	$db->query("CREATE TABLE IF NOT EXISTS `livestream_categories` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
		`parent_id` int(11) NOT NULL,
		`category_order` int(11) NOT NULL DEFAULT '0',
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7;");
	/** @var $title */

	if($db->query("SELECT COUNT(id) FROM livestream_categories")->fetch_row()[0] == 0) {
		$preloaded_categories = array('Entertainment', 'Sports');
		$i = 1;
		foreach($preloaded_categories as $preloaded_category) {
			foreach(get_all_languages() as $language) {
				$post_vars['title'][$language['language_id']] = $preloaded_category;
			}
			$expected = array('title' => '');
			extract(array_merge($expected, $post_vars));
			$titleSlug = 'livestreaming_category_'.md5(time().serialize($post_vars)).'_title';
			foreach($title as $langId => $t) {
				add_language_phrase($titleSlug, $t, $langId, 'livestreaming');
			}
			foreach($title as $langId => $t) {
				(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId) : add_language_phrase($titleSlug, $t, $langId, 'livestreaming');
			}
			$db->query("INSERT INTO livestream_categories(slug, title, category_order) VALUES('".trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', lang($titleSlug))), '-')."','".$titleSlug."', '".$i."')");
			$i++;
		}
	}

	$db->query("CREATE TABLE IF NOT EXISTS `livestream_histories` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`livestream_id` int(11) NOT NULL,
		`user_id` int(11) NOT NULL,
		`join_time` int(11) NOT NULL,
		`leave_time` int(11) NULL,
		`active_last_time` int(11) NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `livestreame_id` (`livestream_id`,`user_id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7;");

	$db->query("CREATE TABLE IF NOT EXISTS `comment_seen_by` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`comment_id` int(11) NOT NULL,
		`user_id` int(11) NOT NULL,
		`time` int(11) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `livestreame_id` (`comment_id`,`user_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7;");

	$db->query("CREATE TABLE IF NOT EXISTS `like_seen_by` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`like_id` int(11) NOT NULL,
		`user_id` int(11) NOT NULL,
		`time` int(11) NOT NULL,
		PRIMARY KEY (`id`),
		UNIQUE KEY `likey_id` (`like_id`,`user_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7 ;");

	$db->query("INSERT INTO `video_categories` (`id`, `title`, `slug`, `parent_id`, `category_order`) VALUES (NULL, 'Livestream', 'Livestream_839283758494', '0', '0');");
}