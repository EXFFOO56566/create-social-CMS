<?php
load_functions("notification::notification");
register_hook('system.started', function($app) {
	if($app->themeType == 'frontend' or $app->themeType == 'mobile') {
		//register assets
		register_asset("notification::css/notification.css");
		register_asset('notification::js/firebase-app.js');
		register_asset('notification::js/firebase-database.js');
		register_asset('notification::js/firebase-messaging.js');
		register_asset("notification::js/notification.js");
	}
});


register_get_pager("notification/load/latest", array('use' => 'notification::notification@notification_load_pager', 'filter' => 'auth'));
register_get_pager("notification/preload", array('use' => 'notification::notification@preload_pager', 'filter' => 'auth'));
register_get_pager("notification/mark", array("use" => 'notification::notification@notification_mark_pager', 'filter' => 'auth'));
register_get_pager("notification/delete", array("use" => 'notification::notification@notification_delete_pager', 'filter' => 'auth'));
register_get_pager("notifications", array("use" => 'notification::notification@notifications_pager', 'filter' => 'auth', 'as' => 'notifications'));

register_pager('nsw.js', array(
		'as' => 'notification-service-worker',
		'use' => 'notification::notification@service_worker_pager'
	)
);

register_pager('notification/fcm/token/update', array(
		'as' => 'notification-fcm-token-update',
		'use' => 'notification::notification@fcm_token_update',
		'filter' => 'auth'
	)
);

register_pager('admincp/notification/fcm/send', array(
		'as' => 'admin-notification-fcm-send',
		'use' => 'notification::admincp@fcm_send',
		'filter' => 'admin-auth'
	)
);

register_hook('user.delete', function($userid) {
	db()->query("DELETE FROM notifications WHERE from_userid='{$userid}' OR to_userid='{$userid}'");
});

register_hook('footer', function() {
	if(is_loggedIn() and !isMobile()) echo "<div id='notification-popup'><div id='content'></div><a onclick='return closeNotificationpopup()' class='close' href=''><i class='ion-close'></i></a></div>";
});

register_hook('before-render-css', function($html) {
	$html .= '<link rel="manifest" href="'.asset_link('js/manifest.json').'">';

	return $html;
});

register_hook('before-render-js', function($html) {
	$enable_notification_sound = get_privacy('enable-notification-sound', 1);
	$push_driver = config('pusher-driver');
	$firebase_api_key = config('firebase-api-key-legacy');
	$firebase_auth_domain = config('firebase-auth-domain');
	$firebase_database_url = config('firebase-database-url');
	$firebase_project_id = config('firebase-project-id');
	$firebase_storage_bucket = config('firebase-storage-bucket');
	$firebase_messaging_sender_id = config('firebase-messaging-sender-id');
	$firebase_public_vapid_key = config('firebase-public-vapid-key');
	$html .= <<<EOT
	    <script>
		    var enableNotificationSound = $enable_notification_sound;
		    var pushDriver = '$push_driver';
            var firebaseAPIKey = '$firebase_api_key';
            var firebaseAuthDomain = '$firebase_auth_domain';
            var firebaseDatabaseURL = '$firebase_database_url';
            var firebaseProjectId = '$firebase_project_id';
            var firebaseStorageBucket = '$firebase_storage_bucket';
            var firebaseMessagingSenderId = '$firebase_messaging_sender_id';
            var firebasePublicVapidKey = '$firebase_public_vapid_key';
	    </script>
EOT;
	return $html;
});

register_hook('admin-started', function() {
	get_menu('admin-menu', 'admin-users')->addMenu(lang('notification::firebase-cloud-messaging'), url_to_pager('admin-notification-fcm-send'), 'fcm-send');
});

register_hook('push.notification', function($result, $type, $detail) {
	if($type == 'notification') {
		$count = count($detail);
		if($count) {
			$notifications = array();
			$notification = $result;
			$notification['options']['title'] = $count > 1 ? lang('notification::notifications') : lang('notification::notification');
			$notification['options']['body'] = $count > 1 ? lang('notification::notifications-notification', array('num' => $count)) : lang('notification::notification-notification', array('num' => $count));
			$notification['options']['link'] = url_to_pager('notifications');
			$notification['status'] = true;
			$notifications[] = array($notification);
			foreach($detail as $notification_id) {
				$notification = fire_hook('notification.push.notification', $result, array(find_notification($notification_id)));
				if($notification['status']) {
					$notifications[] = array($notification);
				}
			}
			$result['notifications'] = $notifications;
			$result['status'] = true;
		}
	}
	return $result;
});

register_hook('pusher.list', function($list) {
	$list['fcm'] = lang('notification::firebase-cloud-messaging-exp');
	return $list;
});

register_hook('user.online.status', function($status, $user) {
	if(config('pusher-driver', 'ajax') == 'fcm') {
		$firebase_database_url = config('firebase-database-url');
		$url = $firebase_database_url.'/users/'.$user['id'].'.json';
		$response = @file_get_contents($url);
		$user_data = json_decode($response);
		$status[0] = isset($user_data->online) && $user_data->online;
	}

	return $status;
});
