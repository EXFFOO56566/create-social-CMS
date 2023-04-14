<?php

/**
 * @param \App $app
 * @return string
 */
function fcm_send($app) {
	$app->setTitle(lang('firebase-cloud-messaging'));
	$val = input('val', null, array('body'));
	$message = null;
	if($val) {
		CSRFProtection::validate();
		/**
		 * @var $subject
		 * @var $body
		 * @var $to
		 * @var $non
		 * @var $selected
		 */
		extract($val);
		$body = html_purifier_purify($body);
		$db = db();
		$query = null;
		get_users();
		if($to == 'all') {
			$query = $db->query("SELECT `fcm_token` FROM `users` WHERE `fcm_token` != ''");
		} elseif($to == 'selected') {
			if(isset($selected)) {
				$selected = implode(', ', $selected);
				$query = $db->query("SELECT `fcm_token` FROM `users` WHERE `id` IN (".$selected.")");
			}
		} elseif($to == 'non-active') {
			$number = (int) $non['number'];
			$type = $non['type'];
			$time = time();
			if($type == 'day') {
				$time = $time - ($number * 86400);
			} elseif($type == 'month') {
				$time = $time - ($number * 2628000);
			} else {
				$time = $time - ($number * 31540000);
			}
			$query = $db->query("SELECT `fcm_token` FROM users  WHERE `online_time` < ".$time." AND `fcm_token` != ''");
		}
		if($query) {
			$site_logo = config('site-logo') ? url_img(config('site-logo')) : img('images/logo.png');
			$firebase_api_key = config('firebase-api-key');
			$project_id = config('firebase-project-id');
			$url = 'https://fcm.googleapis.com/v1/projects/'.$project_id.'/messages:send';
			$headers = 'Authorization:Bearer '.$firebase_api_key."\r\n".'Content-Type: application/json'."\r\n".'Accept: application/json'."\r\n";
			$registration_ids = array();
			while($user = $query->fetch_row()) {
				$registration_ids[] = $user[0];
			}
			$data = array(
				'message' => array(
					'to' => '',
					'notification' => array(
						'title' => $subject,
						'body' => $body,
						'icon' => $site_logo,
						'badge' => $site_logo,
						'click_action' => url(),
					),
					'webpush' => array(
						'headers' => array(
							'Urgency' => 'high'
						),
						'notification' => array(
							'body' => $body,
							'icon' => $site_logo,
							'badge' => $site_logo,
							'click_action' => url(),
							'requireInteraction' => 'true'
						)
					),
				)
			);
			$fields = array('registration_ids' => $registration_ids, 'data' => $data);
			$content = json_encode($fields);
			$context = array('http' => array( 'method' => 'POST', 'header' => $headers, 'content' => $content));
			$context = stream_context_create($context);
			$response = file_get_contents($url,false, $context);
			$debug = false;
			if($debug) {
				header('Content-Type: text/json');
				$debug = array(
					'url' => $url,
					'fields' => $fields,
					'response' => $response
				);
				print(json_encode($debug, JSON_PRETTY_PRINT));
				exit;
			}
			$message = lang('notification::message-sent-successfully');
		} else {
			$message = lang('notification::message-not-sent');
		}
	}

	return $app->render(view('notification::fcm/send', array('message' => $message)));
}