<?php
function edit_listing_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$app->setTitle(lang("marketplace::edit-listing"));
	$categories = marketplace_get_categories();
	$val = input('val');
	$listing_id = input('id');
	if($listing_id) {
		$listing = marketplace_get_listing($listing_id);
		if($val) {
			CSRFProtection::validate();
			$image = $listing['image'];
			if(input_file('image')) {
				$uploader = new Uploader(input_file('image'));
				if($uploader->passed()) {
					$uploader->setPath('marketplace/listings/images/');
					$image = $uploader->resize()->result();
				} else {
					$message = $uploader->getError();
				}
			}
			$val['image'] = $image;
			if(!$message) {
				$saved = marketplace_execute_form($val);
				if($saved) {
					$status = 1;
					$message = lang('marketplace::listing-saved');
					$redirect_url = url_to_pager('marketplace-listing', array('slug' => $listing['slug']));
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
		}
		$listing = marketplace_get_listing($listing_id);
		return $app->render(view('marketplace::edit_listing', array('categories' => $categories, 'listing' => $listing, 'listing_id' => $listing_id, 'message' => $message)));
	}
}