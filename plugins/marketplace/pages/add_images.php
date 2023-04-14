<?php
function add_images_pager($app) {
	$status = 0;
	$message = '';
	$redirect_url = '';

	$app->setTitle(lang("marketplace::add-images"));
	$message = null;
	$listing_id = input('id');
	$listing = marketplace_get_listing($listing_id);
	$val = input('val');
	$marketplace_images = marketplace_get_listing_images($listing_id);
	$num_marketplace_images = count($marketplace_images);
	if($val) {
		$images = array();
		$images_max = config('max-num-marketplace-photos', 5);
		if(input_file('images')) {
			if($images_max >= ($num_marketplace_images) + count($images)) {
				$uploader = new Uploader(null, 'image', input_file('images'));
				if($uploader->passed()) {
					foreach(input_file('images') as $image) {
						$uploader = new Uploader($image);
						if($uploader->passed()) {
							$uploader->setPath('marketplace/listings/images/');
							$image = $uploader->resize()->result();
							$images[] = $image;
						} else {
							$message = $uploader->getError();
							break;
						}
					}
				} else {
					$message = $uploader->getError();
				}
			} else {
				$message = lang('marketplace::images-max-error', array('max' => $images_max));
			}
		}
		if(!$message) {
			$val['images'] = $images;
			$add = marketplace_execute_form($val);
			if($add) {
				$status = 1;
				$redirect_url = url_to_pager('marketplace-listing', array('slug' => $listing['slug']));
			} else {
				$message = '';
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
	}

	if($redirect_url) {
		return redirect($redirect_url);
	}

	return $app->render(view('marketplace::add_images', array('marketplace_id' => $listing_id, 'marketplace_images' => $marketplace_images, 'num_marketplace_images' => $num_marketplace_images, 'message' => $message)));
}