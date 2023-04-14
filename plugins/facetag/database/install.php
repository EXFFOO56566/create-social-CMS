<?php

function facetag_install_database() {
	$db = db();
	$db->query("CREATE TABLE IF NOT EXISTS `facetags` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `tagged_user_id` int(11) NOT NULL,
      `photo_id` int(11) NOT NULL,
      `coord_id` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");
}