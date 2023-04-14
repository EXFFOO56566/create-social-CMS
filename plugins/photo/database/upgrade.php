<?php
function photo_upgrade_database() {
	$db = db();
	try {
		$db->query("ALTER TABLE `medias` ADD `entity_type` VARCHAR(255) NOT NULL AFTER `ref_name`, ADD `entity_id` INT(11) NOT NULL AFTER `entity_type`, ADD `featured` TINYINT(1) NOT NULL;");
	} catch(Exception $e) {
	}
	try {
		$db->query("ALTER TABLE `medias` CHANGE `entity_type` `entity_type` VARCHAR(255) NOT NULL;");
	} catch(Exception $e) {
	}	try {
		$db->query("ALTER TABLE `users` ADD `album_private_exception` VARCHAR(255) NOT NULL;");
	} catch(Exception $e) {
	}

	photo_dump_site_pages();
}

function photo_dump_site_pages() {
	register_site_page("photo", array('title' => lang('photo-directory'), 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'photo', 'content', 'middle');
		Widget::add(null, 'photo', 'plugin::photo|menu', 'right');
		Widget::add(null, 'photo', 'plugin::photo|latest', 'right');
		Menu::saveMenu('main-menu', 'photo::photos', 'photos', 'manual', 1, 'ion-images');
	});
	register_site_page("photo-myphotos", array('title' => lang('my-photos'), 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'photo-myphotos', 'content', 'middle');
		Widget::add(null, 'photo-myphotos', 'plugin::photo|menu', 'right');
		Widget::add(null, 'photo-myphotos', 'plugin::photo|latest', 'right');
	});
	register_site_page("photo-albums", array('title' => lang('all-albums'), 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'photo-albums', 'content', 'middle');
		Widget::add(null, 'photo-albums', 'plugin::photo|menu', 'right');
		Widget::add(null, 'photo-albums', 'plugin::photo|latest', 'right');
	});
	register_site_page("photo-myalbums", array('title' => lang('my-albums'), 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'photo-myalbums', 'content', 'middle');
		Widget::add(null, 'photo-myalbums', 'plugin::photo|menu', 'right');
		Widget::add(null, 'photo-myalbums', 'plugin::photo|latest', 'right');
	});
	register_site_page("photo-create-album", array('title' => lang('photo::create-new-album'), 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'photo-create-album', 'content', 'middle');
		Widget::add(null, 'photo-create-album', 'plugin::photo|menu', 'right');
		Widget::add(null, 'photo-create-album', 'plugin::photo|latest', 'right');
	});
	register_site_page("photo-edit-album", array('title' => lang('photo::edit-album'), 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'photo-edit-album', 'content', 'middle');
		Widget::add(null, 'photo-edit-album', 'plugin::photo|menu', 'right');
		Widget::add(null, 'photo-edit-album', 'plugin::photo|latest', 'right');
	});
	register_site_page("photo-album-photos", array('title' => lang('photo::album-photos'), 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'photo-album-photos', 'content', 'middle');
		Widget::add(null, 'photo-album-photos', 'plugin::photo|menu', 'right');
		Widget::add(null, 'photo-album-photos', 'plugin::photo|latest', 'right');
	});
	register_site_page("photo-myalbum-photos", array('title' => lang('photo::my-album-photos'), 'column_type' => TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::add(null, 'photo-myalbum-photos', 'content', 'middle');
		Widget::add(null, 'photo-myalbum-photos', 'plugin::photo|menu', 'right');
		Widget::add(null, 'photo-myalbum-photos', 'plugin::photo|latest', 'right');
	});
}