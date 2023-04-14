<?php
function ads_install_database() {
	$db = db();

	$db->query("CREATE TABLE IF NOT EXISTS `ads` (
      `ads_id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `link` text COLLATE utf8_unicode_ci NOT NULL,
      `display_link` text COLLATE utf8_unicode_ci NOT NULL,
      `page_id` int(11) NOT NULL,
      `post_id` int(11) NOT NULL,
      `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `image` text COLLATE utf8_unicode_ci,
      `video` text COLLATE utf8_unicode_ci,
      `ads_class` varchar(35) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'picture',
      `plan_type` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
      `plan_id` int(11) NOT NULL,
      `quantity` int(11) NOT NULL,
      `target_location` text COLLATE utf8_unicode_ci NOT NULL,
      `target_gender` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
      `target_age` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
      `impression_stats` int(11) NOT NULL DEFAULT '0',
      `clicks_stats` int(11) NOT NULL DEFAULT '0',
      `views` int(11) NOT NULL DEFAULT '0',
      `clicks` int(11) NOT NULL DEFAULT '0',
      `paid` int(11) NOT NULL DEFAULT '0',
      `status` int(11) NOT NULL DEFAULT '0',
      `time` int(11) NOT NULL,
      `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`ads_id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=24 ;");

	$db->query("CREATE TABLE IF NOT EXISTS `ads_plans` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `type` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
      `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `price` float NOT NULL,
      `quantity` int(11) NOT NULL,
      `ads_order` int(11) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5;");

	/** @var $name */
	/** @var $description */
	$i = 1;
	if($db->query("SELECT COUNT(id) FROM ads_plans")->fetch_row()[0] == 0) {
		$preloaded_plans = array(
			array(
				'name' => 'Per Click Basic',
				'type' => 1,
				'description' => 'Per Click Basic Plan',
				'price' => 20,
				'quantity' => 1000,
			),
			array(
				'name' => 'Per Impression Basic',
				'type' => 2,
				'description' => 'Per Impression Basic Plan',
				'price' => 10,
				'quantity' => 1000,
			)
		);
		foreach($preloaded_plans as $preloaded_plan) {
			$val = array();
			foreach(get_all_languages() as $language) {
				$val['name'][$language['language_id']] = $preloaded_plan['name'];
				$val['description'][$language['language_id']] = $preloaded_plan['description'];
			}

			$expected = array('name' => '', 'description' => '');
			extract(array_merge($expected, $val));

			$name_slug = 'ads_plan_'.md5(time().serialize($val)).'_name';
			foreach($name as $lang_id => $t) {
				add_language_phrase($name_slug, $t, $lang_id, 'ads');
			}
			foreach($name as $lang_id => $t) {
				(phrase_exists($lang_id, $name_slug)) ? update_language_phrase($name_slug, $t, $lang_id) : add_language_phrase($name_slug, $t, $lang_id, 'ads');
			}

			$description_slug = 'ads_plan_'.md5(time().serialize($val)).'_description';
			foreach($description as $lang_id => $t) {
				add_language_phrase($description_slug, $t, $lang_id, 'ads');
			}
			foreach($description as $lang_id => $t) {
				(phrase_exists($lang_id, $description_slug)) ? update_language_phrase($description_slug, $t, $lang_id) : add_language_phrase($description_slug, $t, $lang_id, 'ads');
			}

			$db->query("INSERT INTO ads_plans(name, type, description, price, quantity, ads_order) VALUES('".$name_slug."', ".$preloaded_plan['type'].", '".$description_slug."', ".$preloaded_plan['price'].", ".$preloaded_plan['quantity'].", ".$i.")");
			$i++;
		}
	}
}