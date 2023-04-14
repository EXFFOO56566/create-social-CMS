<?php
function listing_pager($app) {
	$message = '';
	$slug = segment(2);
	$listing_id = $slug;
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		return add_comment($val);
	}
	if($listing_id) {
		if(marketplace_get_listing($listing_id)) {
			$listing = marketplace_get_listing($listing_id);
			$listing_id = $listing['id'];
		} else {
			return MyError::error404();
		}
		marketplace_view_listing($listing_id);
		$listing_images = marketplace_get_listing_images($listing_id);
		$app->setTitle($listing['title']);
		set_meta_tags(array('name' => get_setting("site_title", "Crea8social"), 'title' => get_setting("site_title", "Crea8social").' - '.$listing['title'], 'description' => $listing['description'], 'image' => $listing['image'] ? url_img($listing['image'], 200) : img('marketplace::images/no_image.jpg', 75), 'keywords' => $listing['tags']));
		return $app->render(view('marketplace::listing', array('listing' => $listing, 'listing_images' => $listing_images, 'message' => $message)));
	}
}