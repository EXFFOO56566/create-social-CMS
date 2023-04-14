<?php
function blog_upgrade_database() {
	$db = db();

	try {
		$db->query("ALTER TABLE `blogs` ADD `entity_type` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `user_id`");
	} catch(Exception $e) {
	}

	try {
		$db->query("ALTER TABLE `blogs` ADD `entity_id` INT(11) NOT NULL AFTER `entity_type`");
	} catch(Exception $e) {
	}

	try {
		$db->query("ALTER TABLE `feeds` ADD `blog` TEXT NOT NULL AFTER `video`");
	} catch(Exception $e) {
	}

	$db->query("UPDATE `blogs` SET entity_type = 'user' WHERE entity_type = ''");
	$db->query("UPDATE `blogs` SET entity_id = user_id WHERE entity_id = 0");

	register_site_page('blogs', array('title' => 'blog::blogs', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'blogs', 'content', 'middle');
		Widget::add(null, 'blogs', 'plugin::blog|menu', 'right');
		Widget::add(null, 'feed', 'plugin::blog|latest', 'right');
		Widget::add(null, 'profile', 'plugin::blog|profile-recent', 'right');
		Menu::saveMenu('main-menu', 'blog::blogs', 'blogs', 'manual', true, 'ion-android-clipboard');
	});
	register_site_page('blog-add', array('title' => 'blog::blogs-add-page', 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'blog-add', 'content', 'middle');
	});
	register_site_page('blog-manage', array('title' => 'blog::manage-blogs', 'column_type' => ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'blog-manage', 'content', 'middle');
	});
	register_site_page('blog-page', array('title' => 'blog::blog-view-page', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'blog-page', 'content', 'middle');
		Widget::add(null, 'blog-page', 'plugin::blog|menu', 'right');
		Widget::add(null, 'blog-page', 'plugin::blog|related', 'right');
	});

	$db = db();
	try {
		$db->query("ALTER TABLE `blogs` ADD  `featured` TINYINT(1) NOT NULL DEFAULT  '0'");
	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `blogs` ADD `entity_type` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL AFTER `user_id`");
	} catch(Exception $e) {
	}
	try {
        $db->query("ALTER TABLE `blog_categories` ADD  `parent_id` TINYINT(50) NOT NULL DEFAULT  '0'");	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `blogs` ADD `entity_id` INT(11) NOT NULL AFTER `entity_type`");
	} catch(Exception $e) {
	}
	try{
			if($db->query("SELECT COUNT(id) FROM blog_categories")->fetch_row()[0] == 0) {
				$preloaded_categories = array('Entertainment', 'Tech', 'Politics', 'Food');
				$i = 1;
				foreach($preloaded_categories as $preloaded_category) {
					foreach(get_all_languages() as $language) {
						$post_vars['title'][$language['language_id']] = $preloaded_category;
					}
					$expected = array('title' => '');
					extract(array_merge($expected, $post_vars));
					$titleSlug = 'blog_category_'.md5(time().serialize($post_vars)).'_title';
					foreach($title as $langId => $t) {
						add_language_phrase($titleSlug, $t, $langId, 'blog');
					}
					foreach($title as $langId => $t) {
						(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId, 'blog') : add_language_phrase($titleSlug, $t, $langId, 'blog');
					}
					$db->query("INSERT INTO blog_categories(title, category_order) VALUES('".$titleSlug."', '".$i."')");
					$i++;
				}
			}
		} catch (Exception $e){}
}