<?php

function unsubscribe_page($app) {
	$app->setTitle(lang('unsubscribe'));
	$hash = segment(2);
	$result = mailer()->mailingListUnsubscribe($hash);
	return view('email/unsubscribe', array('message' => $result['message']));
}