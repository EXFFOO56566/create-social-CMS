<?php
function marketplace_pager($app) {
	$app->setTitle(lang("marketplace::marketplace"));
	$category_id = input('c');
	$featured = input('f');
	$user_id = input('u');
	$term = input('s');
	$min_price = input('p0');
	$max_price = input('p1');
	$location = input('l');
	$page = input('page');
	$categories = marketplace_get_categories();
	$category = $category_id ? lang(marketplace_get_category($category_id)['title']) : lang('marketplace::all-categories');
	$appends = $_GET;
	unset($appends['page']);
	$filters = array('category_id' => $category_id, 'term' => $term, 'user_id' => $user_id, 'featured' => $featured, 'min_price' => $min_price, 'max_price' => $max_price, 'location' => $location);
	$listings = marketplace_get_listings($filters, $page)->append($appends);
	$message = null;
	return $app->render(view('marketplace::marketplace', array('categories' => $categories, 'category' => $category, 'listings' => $listings, 'message' => $message)));
}