<?php
function video_install_database() {
	$db = db();
	$db->query("CREATE TABLE IF NOT EXISTS `videos` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `description` text COLLATE utf8_unicode_ci NOT NULL,
      `user_id` int(11) NOT NULL,
      `entity_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `entity_id` int(11) NOT NULL,
      `photo_path` text COLLATE utf8_unicode_ci NOT NULL,
      `thumbnail` text COLLATE utf8_unicode_ci NOT NULL,
      `category_id` int(11) NOT NULL,
      `source` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `code` text COLLATE utf8_unicode_ci NOT NULL,
      `status` int(11) NOT NULL,
      `file_path` text COLLATE utf8_unicode_ci NOT NULL,
      `view_count` int(11) NOT NULL DEFAULT '0',
      `featured` int(11) NOT NULL DEFAULT '0',
      `privacy` int(11) NOT NULL DEFAULT '1',
      `time` int(11) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=17;");

	$db->query("CREATE TABLE IF NOT EXISTS `video_categories` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `parent_id` int(11) NOT NULL,
        `category_order` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=7;");

	/** @var $title */
	if($db->query("SELECT COUNT(id) FROM video_categories")->fetch_row()[0] == 0) {
		$preloaded_categories = array('Documentary', 'Music Video', 'Comedy', 'TV');
		$i = 1;
		foreach($preloaded_categories as $preloaded_category) {
			foreach(get_all_languages() as $language) {
				$post_vars['title'][$language['language_id']] = $preloaded_category;
			}
			$expected = array('title' => '');
			extract(array_merge($expected, $post_vars));
			$titleSlug = 'video_category_'.md5(time().serialize($post_vars)).'_title';
			foreach($title as $langId => $t) {
				add_language_phrase($titleSlug, $t, $langId, 'video');
			}
			foreach($title as $langId => $t) {
				(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId, 'video') : add_language_phrase($titleSlug, $t, $langId, 'video');
			}
			$db->query("INSERT INTO video_categories(slug, title, category_order) VALUES('".trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', lang($titleSlug))), '-')."','".$titleSlug."', '".$i."')");
			$i++;
		}
	}
}