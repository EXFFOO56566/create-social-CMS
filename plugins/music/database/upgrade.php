<?php
function music_upgrade_database() {
	$db = db();
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
			/** @var $title */
			foreach($title as $langId => $t) {
				add_language_phrase($titleSlug, $t, $langId, 'music');
			}
			foreach($title as $langId => $t) {
				phrase_exists($langId, $titleSlug) ? update_language_phrase($titleSlug, $t, $langId) : add_language_phrase($titleSlug, $t, $langId, 'music');
			}
			$db->query("INSERT INTO music_categories(slug, title, parent_id, `order`) VALUES('".trim(strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', lang($titleSlug))), '-')."', '".$titleSlug."', 0, '".$i."')");
			$i++;
		}
	}

	register_site_page("musics", array('title' => 'music::musics-page', 'column_type' => TOP_TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('musics');
		Widget::add(null, 'musics', 'content', 'middle');
		Widget::add(null, 'musics', 'plugin::music|menu', 'top');
		Widget::add(null, 'musics', 'plugin::music|featured', 'right');
		Widget::add(null, 'musics', 'plugin::music|top', 'right');
		Widget::add(null, 'musics', 'plugin::music|latest', 'right');
		Widget::add(null, 'profile', 'plugin::music|profile-recent', 'right');
		Widget::remove('plugin::music|latest', 'feed');
		Widget::add(null, 'feed', 'plugin::music|latest', 'right');
		Menu::saveMenu('main-menu', 'music::musics', 'musics', 'manual', true, 'ion-music-note');
	}, '7.0');

	register_site_page("music-playlists", array('title' => 'music::playlists-page', 'column_type' => TOP_TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('music-playlists');
		Widget::add(null, 'music-playlists', 'menu', 'top');
		Widget::add(null, 'music-playlists', 'content', 'middle');
		Widget::add(null, 'music-playlists', 'plugin::music|playlistsmenu', 'right');
		Widget::add(null, 'music-playlists', 'plugin::music|featuredplaylists', 'right');
		Widget::add(null, 'music-playlists', 'plugin::music|topplaylists', 'right');
		Widget::add(null, 'music-playlists', 'plugin::music|latestplaylists', 'right');
	}, '7.0');

	register_site_page("music-create", array('title' => 'music::musics-create-page', 'column_type' => TOP_TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('music-create');
		Widget::add(null, 'music-create', 'plugin::music|menu', 'top');
		Widget::add(null, 'music-create', 'content', 'middle');
		Widget::add(null, 'music-create', 'plugin::music|featured', 'right');
		Widget::add(null, 'music-create', 'plugin::music|top', 'right');
		Widget::add(null, 'music-create', 'plugin::music|latest', 'right');
		Widget::add(null, 'profile', 'plugin::music|profile-recent', 'right');
	}, '7.0');

	register_site_page("music-playlist-create", array('title' => 'music::playlist-create-page', 'column_type' => TOP_TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('music-playlist-create');
		Widget::add(null, 'music-playlist-create', 'plugin::music|menu', 'top');
		Widget::add(null, 'music-playlist-create', 'content', 'middle');
		Widget::add(null, 'music-playlist-create', 'plugin::music|playlistsmenu', 'right');
		Widget::add(null, 'music-playlist-create', 'plugin::music|featuredplaylists', 'right');
		Widget::add(null, 'music-playlist-create', 'plugin::music|topplaylists', 'right');
		Widget::add(null, 'music-playlist-create', 'plugin::music|latestplaylists', 'right');
	}, '7.0');

	register_site_page("music-edit", array('title' => 'music::music-edit-page', 'column_type' => TOP_TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('music-edit');
		Widget::add(null, 'music-edit', 'plugin::music|menu', 'top');
		Widget::add(null, 'music-edit', 'content', 'middle');
		Widget::add(null, 'music-edit', 'plugin::music|featured', 'right');
		Widget::add(null, 'music-edit', 'plugin::music|top', 'right');
		Widget::add(null, 'music-edit', 'plugin::music|latest', 'right');
		Widget::add(null, 'profile', 'plugin::music|profile-recent', 'right');
	}, '7.0');

	register_site_page('music-playlist-edit', array('title' => 'music::playlist-edit-page', 'column_type' => TOP_TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('music-playlist-edit');
		Widget::add(null, 'music-playlist-edit', 'plugin::music|menu', 'top');
		Widget::add(null, 'music-playlist-edit', 'content', 'middle');
		Widget::add(null, 'music-playlist-edit', 'plugin::music|playlistsmenu', 'right');
		Widget::add(null, 'music-playlist-edit', 'plugin::music|featuredplaylists', 'right');
		Widget::add(null, 'music-playlist-edit', 'plugin::music|topplaylists', 'right');
		Widget::add(null, 'music-playlist-edit', 'plugin::music|latestplaylists', 'right');
	}, '7.0');

	register_site_page('music-page', array('title' => 'music::view-music-page', 'column_type' => TOP_TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('music-page');
		Widget::add(null, 'music-page', 'content', 'middle');
		Widget::add(null, 'music-page', 'plugin::music|menu', 'top');
		Widget::add(null, 'music-page', 'plugin::music|related', 'right');
		Widget::add(null, 'music-page', 'plugin::music|latest', 'right');
	}, '7.0');

	register_site_page('music-playlist-page', array('title' => 'music::view-playlist-page', 'column_type' => TOP_TWO_COLUMN_RIGHT_LAYOUT), function() {
		Widget::clear('music-playlist-page');
		Widget::add(null, 'music-playlist-page', 'content', 'middle');
		Widget::add(null, 'music-playlist-page', 'plugin::music|menu', 'top');
		Widget::add(null, 'music-playlist-page', 'plugin::music|playlistsmenu', 'right');
		Widget::add(null, 'music-playlist-page', 'plugin::music|relatedplaylists', 'right');
		Widget::add(null, 'music-playlist-page', 'plugin::music|latestplaylists', 'right');
	}, '7.0');
}