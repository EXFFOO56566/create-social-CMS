<?php
function check_pager($app) {
	header('Content-Type: text/json');
	CSRFProtection::validate(false);
	$pushes = pusher()->result();
	$pushes = fire_hook('ajax.push.check.result', $pushes, array($pushes));
	return $pushes;
}