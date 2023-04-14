<?php
function music_install_database() {
	$db = db();

	$db->query("CREATE TABLE IF NOT EXISTS `musics` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `artist` text COLLATE utf8_unicode_ci NOT NULL,
        `album` text COLLATE utf8_unicode_ci NOT NULL,
        `user_id` int(11) NOT NULL,
        `entity_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `entity_id` int(11) NOT NULL,
        `cover_art` text COLLATE utf8_unicode_ci NOT NULL,
        `category_id` int(11) NOT NULL,
        `source` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `code` text COLLATE utf8_unicode_ci NOT NULL,
        `status` tinyint(1) NOT NULL,
        `file_path` text COLLATE utf8_unicode_ci NOT NULL,
        `play_count` int(11) NOT NULL DEFAULT '0',
        `featured` tinyint(1) NOT NULL DEFAULT '0',
        `privacy` tinyint(1) NOT NULL DEFAULT '1',
        `auto_posted` tinyint(1) NOT NULL DEFAULT  '0',
        `time` int(11) NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

	$db->query("CREATE TABLE IF NOT EXISTS `music_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `parent_id` int(11) NOT NULL,
        `order` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

	/** @var $title */
	if($db->query("SELECT COUNT(id) FROM music_categories")->fetch_row()[0] == 0) {
		$preloaded_categories = array('Pop', 'RnB', 'Hip Hop', 'Rap', 'Reggae', 'Jazz', 'Country', 'Rock', 'Classic');
		$i = 1;
		foreach($preloaded_categories as $preloaded_category) {
			$post_vars = array();
			foreach(get_all_languages() as $language) {
				$post_vars['title'][$language['language_id']] = $preloaded_category;
			}
			$expected = array('title' => '');
			extract(array_merge($expected, $post_vars));
			$titleSlug = 'music_category_'.md5(time().serialize($post_vars)).'_title';
			foreach($title as $langId => $t) {
				add_language_phrase($titleSlug, $t, $langId, 'music');
			}
			foreach($title as $langId => $t) {
				phrase_exists($langId, $titleSlug) ? update_language_phrase($titleSlug, $t, $langId) : add_language_phrase($titleSlug, $t, $langId, 'music');
			}
			$db->query("INSERT INTO music_categories(slug, title, parent_id, order) VALUES('".trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', lang($titleSlug))), '-')."', '".$titleSlug."', 0, '".$i."')");
			$i++;
		}
	}

	$db->query("CREATE TABLE IF NOT EXISTS `music_playlists` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `description` text COLLATE utf8_unicode_ci NOT NULL,
        `user_id` int(11) NOT NULL,
        `entity_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
        `entity_id` int(11) NOT NULL,
        `musics` text COLLATE utf8_unicode_ci NOT NULL,
        `play_count` int(11) NOT NULL DEFAULT '0',
        `privacy` tinyint(1) NOT NULL DEFAULT '1',
        `featured` tinyint(1) NOT NULL DEFAULT '0',
        `status` tinyint(1) NOT NULL,
        `time` int(11) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `entity_id` (`entity_id`),
        KEY `entity_type` (`entity_type`),
        KEY `privacy` (`privacy`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

	try {
		$db->query("ALTER TABLE `feeds` ADD `music` TEXT AFTER `photos` ;");
	} catch(Exception $e) {
		$error = $e;
	}
}