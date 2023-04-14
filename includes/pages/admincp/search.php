<?php
function load_pager($app) {
	$term = input('term');
	return view('search/load', array('term' => $term));
}