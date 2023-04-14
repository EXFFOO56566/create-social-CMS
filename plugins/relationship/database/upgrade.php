<?php
function relationship_upgrade_database() {
	$db = db();

	$db->query("CREATE TABLE IF NOT EXISTS `relation` (
		`id` int(11) NOT NULL AUTO_INCREMENT,
		`user_id` int(11) NOT NULL,
		`relation_type` varchar(255) NOT NULL,
		`with_id` int(11) NOT NULL,
		`status` int(2) NOT NULL,
		`privacy` int(2) NOT NULL,
		`date` varchar(50) NOT NULL,
		PRIMARY KEY(`id`)
	) ENGINE = InnoDB AUTO_INCREMENT = 4 DEFAULT CHARSET = latin1");

	$db->query("CREATE TABLE IF NOT EXISTS `relationship_family` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `relationship` varchar(255) NOT NULL,
        `gender` varchar(20) NOT NULL,
        `status` int(1) NOT NULL,
        `type` varchar(20) NOT NULL DEFAULT 'relation',
        PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1");


	try {
		$db->query("ALTER TABLE  `users` ADD  `m_status` varchar(255) NOT NULL DEFAULT  '1'");
	} catch(Exception $e) {
	}

	try {
		$db->query("ALTER TABLE `users` CHANGE `m_status` `m_status` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT ''");
	} catch(Exception $e) {
	}

	try {
		if($db->query("SELECT COUNT(id) FROM relationship_family WHERE type = 'relation'")->fetch_row()[0] == 0) {
			$db->query("UPDATE relation SET relation_type ='1'");
			$family_relationship_male = array('None', 'Pet (gender neutral)', 'Brother-in-law', 'Father-in-law', 'Son-in-law', 'Stepson', 'Brother', 'Father', 'Son', 'Uncle', 'Cousin (male)', 'Grandfather', 'Grandson', 'Nephew', 'Stepbrother', 'Stepfather');
			insert_relationship_seeder($family_relationship_male, 'male', 1, 'relation');
			$family_relationship_female = array('Pet (gender neutral)', 'Sister-in-law', 'Mother-in-law', 'Daughter-in-law', 'Stepdaughter', 'Sister', 'Mother', 'Daughter', 'Aunt', 'Cousin (female)', 'Grandmother', 'Granddaughter', 'Niece', 'Stepsister', 'Stepmother');
			insert_relationship_seeder($family_relationship_female, 'female', 1, 'relation');
		}
	} catch(Exception $e) {}

	try {
		if($db->query("SELECT COUNT(id) FROM relationship_family WHERE type = 'status'")->fetch_row()[0] == 0) {
			$personal_status = array('Single', 'Complicated', 'Engaged', 'Married', 'Divorced', 'Widow');
			insert_relationship_seeder($personal_status, 'status', 1, 'status');
		}
	} catch(Exception $e) {}

	if(plugin_loaded('relation')) {
		plugin_deactivate('relation');
		plugin_delete('relation');
	}

	register_site_page("friend-requests", array('title' => 'friend-requests-page', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'friend-requests', 'content', 'middle');

		Widget::add(null, 'profile', 'plugin::relationship|friends', 'right');
		Widget::add(null, 'profile', 'plugin::relationship|followers', 'right');
		Widget::add(null, 'profile', 'plugin::relationship|following', 'right');
		Menu::saveMenu('main-menu', 'find-friends', 'suggestions', 'manual', true, 'ion-android-person-add');
		Menu::saveMenu('main-menu', 'profile', 'me', 'manual', true, 'ion-ios-contact-outline');
	});

	register_site_page("suggestions", array('title' => 'people-you-may-know-page', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'suggestions', 'content', 'middle');
		Widget::add(null, 'feed', 'plugin::relationship|suggestions', 'right');
	});
}

function insert_relationship_seeder($content, $gender, $status, $type) {
	$i = 0;
	foreach($content as $preloaded_category) {
		foreach(get_all_languages() as $language) {
			$post_vars['title'][$language['language_id']] = $preloaded_category;
		}
		$expected = array('title' => '');
		extract(array_merge($expected, $post_vars));
		$titleSlug = 'relationship_family_'.md5(time().serialize($post_vars)).'_title';
		foreach($title as $langId => $t) {
			add_language_phrase($titleSlug, $t, $langId, 'relationship');
		}
		foreach($title as $langId => $t) {
			(phrase_exists($langId, $titleSlug)) ? update_language_phrase($titleSlug, $t, $langId) : add_language_phrase($titleSlug, $t, $langId, 'relationship');
		}
		db()->query("INSERT INTO relationship_family(relationship, gender,status,type) VALUES('$titleSlug', '$gender', '$status', '$type')");
		$i++;
	}
}