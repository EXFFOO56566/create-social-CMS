<?php
function ajax_page($app) {
	CSRFProtection::validate(false);
	$action = segment(2);
	$coord_id = input('coord_id');
	switch($action) {
		case 'search_people':
			$query = input('q');
			$people = facetag_people_search($query);
			$response = view('facetag::search_result', array('people' => $people, 'coord_id' => $coord_id));
		break;

		case 'get_tags':
			$photo_id = input('photo_id');
			$result = array();
			$result['phrases'] = array();
			$result['phrases']['tag-somebody'] = lang('facetag::tag-somebody');
			$result['can_tag_photo'] = facetag_can_tag_photo($photo_id);
			$result['tags'] = facetag_get_tags($photo_id);
			$response = json_encode($result);
		break;

		case 'set_coordinate':
			$photo_id = input('photo_id');
			$user_id = input('user_id');
			$result = facetag_set_coordinate($coord_id, $photo_id, $user_id);
			$response = json_encode($result);
		break;

		case 'remove_coordinate':
			$result = facetag_remove_coordinate($coord_id);
			$response = json_encode($result);
		break;

		default:
			$result = array();
			$response = json_encode($result);
		break;
	}
	return $response;
}