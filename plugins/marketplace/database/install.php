<?php
function marketplace_install_database() {
	$db = db();

	$db->query("CREATE TABLE IF NOT EXISTS `marketplace_categories` (
        `id` TINYINT(2) unsigned NOT NULL AUTO_INCREMENT,
        `slug` VARCHAR(64) NOT NULL,
        `title` VARCHAR(64) NOT NULL,
        PRIMARY KEY (`id`)
    )
    ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1");

	if($db->query("SELECT COUNT(id) FROM marketplace_categories")->fetch_row()[0] == 0) {
		$preloaded_categories = array('Electronics', 'Property Sales, Rentals', 'Services', 'Auto, Cars, Trucks', 'Home, Office, Garden', 'Jobs, Employment', 'Accommodation, Travel', 'Dating, Friends');
		foreach($preloaded_categories as $preloaded_category) {
			foreach(get_all_languages() as $language) {
				$post_vars['title'][$language['language_id']] = $preloaded_category;
			}
			$expected = array('title' => '');
			extract(array_merge($expected, $post_vars));
			$title_slug = 'marketplace_category_'.md5(time().serialize($post_vars)).'_title';
			/** @var array $title */
			foreach($title as $lang_id => $t) {
				add_language_phrase($title_slug, $t, $lang_id, 'marketplace');
			}
			foreach($title as $lang_id => $t) {
				(phrase_exists($lang_id, $title_slug)) ? update_language_phrase($title_slug, $t, $lang_id) : add_language_phrase($title_slug, $t, $lang_id, 'marketplace');
			}
			$db->query("INSERT INTO marketplace_categories(slug, title) VALUES('".trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', lang($title_slug))), '-')."', '".$title_slug."')");
		}
	}

	$db->query("CREATE TABLE IF NOT EXISTS `marketplace_images` (
		`id` INT(11) NOT NULL AUTO_INCREMENT,
		`image` VARCHAR(256) NOT NULL,
		`listing_id` INT(11) NOT NULL,
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1");

	$db->query("CREATE TABLE IF NOT EXISTS `marketplace_listings` (
		`id` INT(11) NOT NULL AUTO_INCREMENT,
		`slug` VARCHAR(64) NOT NULL,
		`title` VARCHAR(64) NOT NULL,
		`description` text NOT NULL,
		`date` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		`category_id` TINYINT(2) NOT NULL,
		`user_id` INT(11) NOT NULL,
		`entity_type` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
		`entity_id` INT(11) NOT NULL,
		`privacy` TINYINT(1) NOT NULL,
		`tags` VARCHAR(64) NOT NULL,
		`image` VARCHAR(256) NOT NULL,
		`location` VARCHAR(128) NOT NULL,
		`contact` VARCHAR(256) NOT NULL,
		`link` VARCHAR(128) NOT NULL,
		`price` VARCHAR(32) NOT NULL DEFAULT '0.00',
		`featured` TINYINT(1) NOT NULL,
		`approved` TINYINT(1) NOT NULL,
		`active` TINYINT(1) NOT NULL DEFAULT '1',
		`nov` INT(11) unsigned NOT NULL,
		`last_viewed` TIMESTAMP NOT NULL DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1");
}