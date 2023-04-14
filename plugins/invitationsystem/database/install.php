<?php
function invitationsystem_install_database() {
	$db = db();
	$db->query("CREATE TABLE IF NOT EXISTS `invitationsystem_invitations` (
            `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
            `inviter_id` INT(11) NOT NULL,
            `invited_id` INT(11) NOT NULL,
            `invitation_code_id` INT(11) NOT NULL,
            `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci AUTO_INCREMENT = 1;"
	);
	$db->query("CREATE TABLE IF NOT EXISTS `invitationsystem_invitation_codes` (
            `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
            `code` varchar(16) NOT NULL,
            `expiry_time` datetime NOT NULL,
            `user_id` INT(11) unsigned NOT NULL,
            `time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci AUTO_INCREMENT = 1;"
	);
	Menu::saveMenu('header-account-menu', 'invitationsystem::invitations', 'account?action=invitations');
}