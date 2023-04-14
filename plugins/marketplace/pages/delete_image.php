<?php
function delete_image_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$app->setTitle(lang("marketplace::delete-image"));
	$val = input('val');
	$image_id = input('id');
	$listing_id = input('listing_id');
	$listing = marketplace_get_listing($listing_id);
	$image = marketplace_get_listing_image($image_id);
	if($val) {
		CSRFProtection::validate();
		$message = '';
		$deleted = marketplace_execute_form($val);
		if($deleted) {
			$status = 1;
			$redirect_url = url_to_pager('marketplace-listing', array('slug' => $listing['slug']));
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
	}
	if($redirect_url) {
		return redirect($redirect_url);
	}
	return $app->render(view('marketplace::delete_image', array('image' => $image, 'message' => $message)));
}