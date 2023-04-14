<?php

function facetag_upgrade_database() {
	register_site_page('facetag-ajax-page', array('title' => lang('facetag::face-tag'), 'column_type' => ONE_COLUMN_LAYOUT, function() {
		Widget::add(null, 'facetag-ajax-page', 'content', 'middle');
	}));
}