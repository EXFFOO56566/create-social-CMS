<?php
load_functions("notification::notification");

function notification_load_pager($app) {
	CSRFProtection::validate(false);
	pusher()->reset('notification');
	delete_old_notifications();
	return view("notification::display", array("notifications" => get_notifications(10)->results()));
}

function notification_mark_pager() {
	CSRFProtection::validate(false);
	mark_notification_read(input('id'), input('type'));
}

function notification_delete_pager() {
	CSRFProtection::validate(false);
	return delete_notification(input('id'));
}

function notifications_pager($app) {
	pusher()->reset('notification');
	$app->setTitle(lang('notification::notifications'));
	delete_old_notifications();
	return $app->render(view("notification::lists", array('notifications' => get_notifications())));
}

function preload_pager($app) {
	CSRFProtection::validate(false);
	$ids = input('ids');
	return view("notification::display", array("notifications" => preload_notifications($ids)));
}

function service_worker_pager($app) {
	header('Content-Type: application/javascript');
	$base_url = url();
	$logged_in = is_loggedIn() ? 1 : 0;
	$request_token = CSRFProtection::getToken();
	$f = file_get_contents(asset_link('notification::js/firebase-app.js'));
	$fd = file_get_contents(asset_link('notification::js/firebase-database.js'));
	$fm = file_get_contents(asset_link('notification::js/firebase-messaging.js'));
	$sw = file_get_contents(asset_link('notification::/js/nsw.js'));
	$push_driver = config('pusher-driver');
	$firebase_api_key = config('firebase-api-key-legacy');
	$firebase_auth_domain = config('firebase-auth-domain');
	$firebase_database_url = config('firebase-database-url');
	$firebase_project_id = config('firebase-project-id');
	$firebase_storage_bucket = config('firebase-storage-bucket');
	$firebase_messaging_sender_id = config('firebase-messaging-sender-id');
	$firebase_public_vapid_key = config('firebase-public-vapid-key');
	$ajax_interval = config('ajax-polling-interval', 5000);
	$enable_push_notification = config('enable-push-notification', 1);
	$js = <<<EOT
let baseUrl = '$base_url';
let loggedIn = $logged_in;
let requestToken = '$request_token';
let pushDriver = '$push_driver';
let ajaxInterval = $ajax_interval;
let firebaseAPIKey = '$firebase_api_key';
let firebaseAuthDomain = '$firebase_auth_domain';
let firebaseDatabaseURL = '$firebase_database_url';
let firebaseProjectId = '$firebase_project_id';
let firebaseStorageBucket = '$firebase_storage_bucket';
let firebaseMessagingSenderId = '$firebase_messaging_sender_id';
let firebasePublicVapidKey = '$firebase_public_vapid_key';
let enablePushNotification = $enable_push_notification;
$f
$fd
$fm
$sw
EOT;
	return $js;
}

function fcm_token_update($app) {
	CSRFProtection::validate(false);
	$result = array(
		'status' => 0,
		'message' => ''
	);
	$user_id = get_userid();
	$token = input('token');
	$updated = notification_fcm_token_update($token, $user_id);
	if($updated) {
		$result['status'] = 1;
	}
	$response = json_encode($result);
	return $response;
}