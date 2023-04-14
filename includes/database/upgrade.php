<?php
function core_upgrade_database() {
	$db = db();

	$db->query("ALTER TABLE `languages` ADD `dir` VARCHAR( 50 ) NOT NULL DEFAULT 'ltr' AFTER `active` ;");

	$db->query("ALTER TABLE `verification_requests` CHANGE `ignored` `approved` TINYINT( 1 ) NOT NULL DEFAULT '0';");

	$db->query("ALTER TABLE `feeds` ADD `feeling_data` TEXT NOT NULL AFTER `type_data` ;");

	$db->query("ALTER TABLE `feeds` ADD `is_poll` INT NOT NULL DEFAULT '0' AFTER `feeling_data` ;");

	$db->query("CREATE TABLE IF NOT EXISTS `mailing_list_subscriptions` (
	  `id` int(1) NOT NULL AUTO_INCREMENT,
	  `user_id` int(11) NOT NULL,
	  `hash` varchar(255) NOT NULL,
	  `status` tinyint(1) NOT NULL DEFAULT '1',
	  PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;");

	$db->query("CREATE TABLE IF NOT EXISTS `poll_answers` (
        `answer_id` int(11) NOT NULL AUTO_INCREMENT,
        `poll_id` int(11) NOT NULL,
        `answer_text` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        `voters` int(11) NOT NULL DEFAULT '0',
        PRIMARY KEY (`answer_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=20 ;");

	$db->query("CREATE TABLE IF NOT EXISTS `poll_results` (
      `user_id` int(11) NOT NULL,
      `poll_id` int(11) NOT NULL,
      `answer_id` int(11) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ");
	$db->query("ALTER TABLE  `feeds` ADD  `poll_voters` INT NOT NULL DEFAULT  '0' AFTER  `is_poll` ;");

	dump_upgrade_email_templates();

	$db->query("ALTER TABLE  `static_pages` ADD  `description` TEXT NOT NULL AFTER  `tags` ,
    ADD  `keywords` TEXT NOT NULL AFTER  `description` ,
    ADD  `page_type` VARCHAR( 255 ) NOT NULL DEFAULT  'auto' AFTER  `keywords` ,
    ADD  `column_type` VARCHAR( 255 ) NOT NULL AFTER  `page_type` ;");

	$db->query("ALTER TABLE  `blocks` ADD  `block_location` VARCHAR( 50 ) NOT NULL AFTER  `block_view` ;");

	$db->query("CREATE TABLE IF NOT EXISTS `menus` (
      `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `menu_location` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
      `link` text COLLATE utf8_unicode_ci NOT NULL,
      `open_new_tab` int(11) NOT NULL DEFAULT '0',
      `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'manual',
      `ajaxify` int(11) NOT NULL DEFAULT '1',
      `icon` text COLLATE utf8_unicode_ci NOT NULL,
      `menu_order` int(11) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

	$db->query("ALTER TABLE `users` ADD `featured` TINYINT( 1 ) UNSIGNED NOT NULL ;");

	$db->query("CREATE TABLE IF NOT EXISTS `verification_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `entity` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `input_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'text',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

	$db->query("CREATE TABLE IF NOT EXISTS `verification_answers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `question_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `answer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ;");

    try {
        $db->query("ALTER TABLE  `users` ADD  `social_info` varchar(1000) NOT NULL ");
    } catch(Exception $e) {
    }

    $db->query("CREATE TABLE IF NOT EXISTS`promotion_code` (
    `id` tinyint(4) NOT NULL AUTO_INCREMENT,
    `coupon` varchar(255) NOT NULL,
    `discount` int(10) NOT NULL,
    `no_of_use` varchar(10) NOT NULL,
    `status` int(11) NOT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=latin1;");
	dump_site_pages();
}

function dump_site_pages() {
	register_site_page("home", array('title' => 'landing', 'column_type' => TOP_NO_CONTAINER_ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'home', 'splash', 'top');
		//Widget::add(null,'home','top-members', 'middle');
		Menu::saveMenu('header-account-menu', 'saved', 'saved');

		//trick to delete the plugin folders
		//delete_file(path('themes/default/plugins/'));
		//delete_file(path('themes/default/plugins/'));
	});
	register_site_page("signup", array('title' => 'Signup', 'column_type' => TOP_NO_CONTAINER_ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'signup', 'content', 'middle');
	});
	register_site_page("login", array('title' => 'login', 'column_type' => TOP_NO_CONTAINER_ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'login', 'content', 'middle');
	});
	register_site_page("forgot-password", array('title' => 'forgot-password', 'column_type' => THREE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'forgot-password', 'content', 'middle');
	});
	register_site_page("reset-password", array('title' => 'reset-password', 'column_type' => THREE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'reset-password', 'content', 'middle');
	});
	register_site_page("signup-activate", array('title' => 'signup-activate', 'column_type' => THREE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'signup-activate', 'content', 'middle');
	});

	register_site_page("account", array('title' => 'account-page', 'column_type' => TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::add(null, 'account', 'content', 'middle');
		Widget::add(null, 'account', 'account-menu', 'left');

	});

	register_site_page("profile", array('title' => 'user-profile-page', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'profile', 'content', 'middle');
		Widget::add(null, 'profile', 'profile-info', 'right');
	});

	register_site_page("saved", array('title' => 'saved', 'column_type' => TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::add(null, 'saved', 'content', 'middle');
		Widget::add(null, 'saved', 'saved-menu', 'left');
	});

	register_site_page("feed", array('title' => 'feed', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'feed', 'content', 'middle');
        Widget::add(null, 'feed', 'user-card', 'right');
        Menu::saveMenu('main-menu', 'news-feed', 'feed', 'manual', true, 'ion-home');
	});

	register_site_page("view-post", array('title' => 'feed::view-feed', 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'view-post', 'content', 'middle');
		Widget::add(null, 'view-post', 'plugin::relationship|suggestions', 'right');
	});

	register_site_page('mailing-unsubscribe', array('title' => lang('unsubscribe'), 'column_type' => TOP_NO_CONTAINER_ONE_COLUMN_LAYOUT), function() {
		Widget::add(null, 'mailing-unsubscribe', 'content', 'middle');
	});

	register_site_page('people', array('title' => lang('people'), 'column_type' => TWO_COLUMN_LEFT_LAYOUT), function() {
		Widget::add(null, 'people', 'content', 'middle');
		Widget::add(null, 'people', 'peoplefilter', 'left');
		Menu::saveMenu('main-menu', 'people', 'people', 'manual', 1, 'ion-android-people');
	});

    register_site_page("membership-choose-plan", array('title' => 'membership-choose-plan', 'column_type' => ONE_COLUMN_LAYOUT), function() {
        Widget::add(null, 'membership-choose-plan', 'content', 'middle');
        Widget::add(null, 'profile', 'membership|membership', 'right');
    });
    register_site_page("membership-payment", array('title' => 'membership-payment', 'column_type' => ONE_COLUMN_LAYOUT), function() {
        Widget::add(null, 'membership-payment', 'content', 'middle');
    });

	db()->query("ALTER TABLE  `feeds` ADD  `status` INT NOT NULL DEFAULT  '1' AFTER  `can_share` ;");

}

function dump_upgrade_email_templates() {
	add_email_template("friend-request", array(
		'title' => 'Friend Request',
		'description' => 'This is friend request mail sent to users',
		'subject' => 'New Friend Request',
		'body_content' => '
            [header]

            You have a new friend request from [fullname] .

            Respond to request here <a href="[link]">[link]</a>

            [footer]
        ',
		'placeholders' => '[link],[fullname]'
	));

	add_email_template("comment-post", array(
		'title' => 'Comment on post',
		'description' => 'This is mail sent to post owner when someone make a comment on it',
		'subject' => 'New Comment On Your Post',
		'body_content' => '
            [header]

            You have a new comment from [fullname] on your post

            see post <a href="[link]">[link]</a>

            [footer]
        ',
		'placeholders' => '[link],[fullname]'
	));

	add_email_template("comment-photo", array(
		'title' => 'Comment on Photo',
		'description' => 'This is mail sent to photo owner when someone make a comment on it',
		'subject' => 'New Comment On Your Photo',
		'body_content' => '
            [header]

            You have a new comment from [fullname] on your photo

            see post <a href="[link]">[link]</a>

            [footer]
        ',
		'placeholders' => '[link],[fullname]'
	));

	add_email_template("post-on-wall", array(
		'title' => 'Posted on User Wall',
		'description' => 'This is mail sent to profile owner when someone posted on his/her wall',
		'subject' => '[fullname] posted on your wall',
		'body_content' => '
            [header]

           [fullname] posted on your wall , see here <a href="[link]">[link]</a>

            [footer]
        ',
		'placeholders' => '[link],[fullname]'
	));

	add_email_template("friend-acceptance", array(
		'title' => 'Friend request acceptance',
		'description' => 'This is mail sent for friend request acceptance',
		'subject' => '[fullname] accepted your friend request',
		'body_content' => '
            [header]

           [fullname] accepted your friend request, see profile here <a href="[link]">[link]</a>

            [footer]
        ',
		'placeholders' => '[link],[fullname]'
	));
}
 