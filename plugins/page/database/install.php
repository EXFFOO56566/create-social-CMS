<?php
function page_install_database() {
	$db = db();

	$db->query("CREATE TABLE IF NOT EXISTS `pages` (
  `page_id` int(11) NOT NULL AUTO_INCREMENT,
  `page_user_id` int(11) NOT NULL,
  `page_title` varchar(255) NOT NULL,
  `page_desc` text NOT NULL,
  `page_url` varchar(255) NOT NULL,
  `verified` int(11) NOT NULL DEFAULT '0',
  `page_logo` text NOT NULL,
  `page_cover` text NOT NULL,
  `page_cover_resized` varchar(255) NOT NULL,
  `design_details` text NOT NULL,
  `page_category_id` int(11) NOT NULL,
  `page_created_at` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`page_id`),
  KEY `page_user_id` (`page_user_id`,`page_title`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=8 ;
");

	$db->query("CREATE TABLE IF NOT EXISTS `page_categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_title` varchar(255) NOT NULL,
  `category_desc` varchar(255) NOT NULL,
  `category_order` int(11) NOT NULL DEFAULT '0',
  `category_created_at` int(11) NOT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;
");
	/** @var $title */
	if($db->query("SELECT COUNT(category_id) FROM page_categories")->fetch_row()[0] == 0) {
		$preloaded_categories = array('Entertainment', 'Sports');
		$i = 1;
		foreach($preloaded_categories as $preloaded_category) {
			foreach(get_all_languages() as $language) {
				$post_vars['title'][$language['language_id']] = $preloaded_category;
			}
			$expected = array('title' => '');
			extract(array_merge($expected, $post_vars));
			$titleSlug = 'page_category_'.md5(time().serialize($post_vars)).'_title';
			foreach($title as $langId => $t) {
				add_language_phrase($titleSlug, $t, $langId, 'page');
			}
			foreach($title as $langId => $t) {
				(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId, 'page') : add_language_phrase($titleSlug, $t, $langId, 'page');
			}
			$db->query("INSERT INTO page_categories(category_title, category_order) VALUES('".$titleSlug."', '".$i."')");
			$i++;
		}
	}

	$db->query("CREATE TABLE IF NOT EXISTS `page_details` (
      `id` int(11) NOT NULL,
      UNIQUE KEY `id` (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ");

	$db->query("CREATE TABLE IF NOT EXISTS `page_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_user_id` int(11) NOT NULL,
  `role_page_id` int(11) NOT NULL,
  `page_role` int(11) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=11 ;");
}
 