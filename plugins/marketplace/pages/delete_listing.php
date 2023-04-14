<?php
function delete_listing_pager($app) {
	$app->setTitle(lang("marketplace::delete-listing"));
	$message = null;
	$categories = marketplace_get_categories();
	$listing_id = input('id');
	$val = input('val');
	if($listing_id) {
		if($val) {
		    $val['id'] = $listing_id;
			CSRFProtection::validate();
			marketplace_execute_form($val);
			return redirect(url_to_pager('marketplace').'?u='.get_userid());
		}
		$listing = marketplace_get_listing($listing_id);
		return $app->render(view('marketplace::delete_listing', array('categories' => $categories, 'listing' => $listing, 'message' => $message)));
	}
}