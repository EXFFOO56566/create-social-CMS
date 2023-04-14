<?php
function create_listing_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$app->setTitle(lang("marketplace::create-listing"));
	$val = input('val');
	if($val) {
		CSRFProtection::validate();
		$image = '';
		if(input_file('image')) {
			$uploader = new Uploader(input_file('image'));
			if($uploader->passed()) {
				$uploader->setPath('marketplace/listings/images/');
				$image = $uploader->resize()->result();
			} else {
				$message = $uploader->getError();
			}
		}
		if(!$message) {
			$val['image'] = $image;
			$listing_id = marketplace_execute_form($val);
			if($listing_id) {
				$status = 1;
				$message = lang('marketplace::listing-add-success-message');
				$listing = marketplace_get_listing($listing_id);
				$redirect_url = url_to_pager('marketplace-listing', array('slug' => $listing['slug']));
				$redirect_url = fire_hook('marketplace.listing.redirect.url', $redirect_url, array($listing));
			}
		}
	}
	if(input('ajax')) {
		$result = array(
			'status' => (int) $status,
			'message' => (string) $message,
			'redirect_url' => (string) $redirect_url,
		);
		$response = json_encode($result);
		return $response;
	}
	if($redirect_url) {
		return redirect($redirect_url);
	}
	$categories = marketplace_get_categories();
	return $app->render(view('marketplace::create_listing', array('categories' => $categories, 'message' => $message)));
}
