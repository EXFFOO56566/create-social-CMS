<?php
function group_install_database() {
	$db = db();

	$db->query("CREATE TABLE IF NOT EXISTS `groups` (
	  `group_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
	  `user_id` int(11) NOT NULL,
	  `group_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	  `group_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	  `group_description` text COLLATE utf8_unicode_ci NOT NULL,
	  `group_logo` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	  `group_cover` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	  `group_cover_resized` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
	  `moderators` text COLLATE utf8_unicode_ci NOT NULL,
	  `privacy` int(11) NOT NULL DEFAULT '1',
	  `who_can_post` int(11) NOT NULL DEFAULT '1',
	  `who_can_add_member` int(11) NOT NULL DEFAULT '1',
	  `group_created_time` int(11) NOT NULL,
	  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	  `featured` INT (1) not NULL ,
	  PRIMARY KEY (`group_id`),
	  KEY `user_id` (`user_id`,`group_name`,`group_title`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5;");

	$db->query("CREATE TABLE IF NOT EXISTS `group_categories` (
	    `id` int(11) NOT NULL AUTO_INCREMENT,
	    `title` varchar(255) NOT NULL,
	    `slug` varchar(255) NOT NULL,
	    `image` varchar(255) NOT NULL,
	    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1");


	/** @var $title */
	if($db->query("SELECT COUNT(id) FROM group_categories")->fetch_row()[0] == 0) {
		$preloaded_categories = array('Sport', 'Entertainment', 'Love');
		$images = array("");
		$images[0] = img("group::images/group-sport.jpg");
		$images[1] = img("group::images/group-entertainment.jpg");
		$images[2] = img("group::images/group-love.jpg");
		$i = 0;
		foreach($preloaded_categories as $preloaded_category) {
			foreach(get_all_languages() as $language) {
				$post_vars['title'][$language['language_id']] = $preloaded_category;
			}
			$expected = array('title' => '');
			extract(array_merge($expected, $post_vars));
			$titleSlug = 'group_category_'.md5(time().serialize($post_vars)).'_title';
			foreach($title as $langId => $t) {
				add_language_phrase($titleSlug, $t, $langId, 'group');
			}
			foreach($title as $langId => $t) {
				(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId, 'group') : add_language_phrase($titleSlug, $t, $langId, 'group');
			}
			$db->query("INSERT INTO group_categories(slug, title,image) VALUES('".trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', lang($titleSlug))), '-')."', '".$titleSlug."', '".$images[$i]."')");
			$i++;
		}
	}

	$db->query("CREATE TABLE IF NOT EXISTS `group_members` (
	  `member_id` int(11) NOT NULL,
	  `member_group_id` int(11) NOT NULL
	) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");


}
 