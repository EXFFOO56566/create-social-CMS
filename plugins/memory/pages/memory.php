<?php

function onthisday_pager($app) {
	$app->setTitle(lang('memory::on-this-day'));
	$memories = memory_get_memories(null, null, false, false);
	return $app->render(view('memory::onthisday', array('memories' => $memories)));
}

function feed_share_pager($app) {
	CSRFProtection::validate(false);
	$result = array(
		'status' => 0,
		'message' => ''
	);
	$id = input('id');
	$share = memory_share_feed($id);
	if($share) {
		$result['status'] = 1;
		$result['message'] = lang('memory::feed-share-success');
	}
	$response = json_encode($result);
	return $response;
}