<?php
function memory_install_database() {
	$db = db();
	$db->query("CREATE TABLE IF NOT EXISTS `memories` (
     `id` int(11) NOT NULL AUTO_INCREMENT,
     `user_id` int(11) NOT NULL,
     `type` varchar(16) NOT NULL,
     `data` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
     `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     `cache` varchar(64) NOT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;");
}