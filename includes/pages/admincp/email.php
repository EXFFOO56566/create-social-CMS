<?php

get_menu("admin-menu", 'settings')->setActive();

get_menu("admin-menu", "settings")->findMenu('email-manager')->setActive();

function templates_pager($app) {
	$emailId = input('id', 'header');
	$langId = input('lang', 'english');
	$val = input('val', null, array('body'));

	if($val) {
		CSRFProtection::validate();
		save_email_template($val);
	}

	$template = get_email_template($emailId, $langId);
	if(!$template) {
		//try load it from english language else redirect back to header email template
		$template = get_email_template($emailId, 'english');
		if(!$template) redirect_to_pager('admin-email-templates');
	}

	$app->setTitle(lang('email-templates'));
	return $app->render(view('email/templates', array(
		'id' => $emailId,
		'lang' => $langId,
		'template' => $template
	)));
}

function settings_pager($app) {
	$app->setTitle(lang('email-settings'));
	$val = input("val");
	if($val) {
		CSRFProtection::validate();
		//we are saving the settings
		save_admin_settings($val);
	}

	$settings = array(
		'email-from-name' => array(
			'type' => 'text',
			'title' => lang('from-name'),
			'description' => lang('email-from-name-desc'),
			'value' => '',
		),

		'email-from-address' => array(
			'type' => 'text',
			'title' => lang('from-address'),
			'description' => lang('email-from-address-desc'),
			'value' => '',
		),

		'email-driver' => array(
			'type' => 'selection',
			'title' => lang('email-driver'),
			'description' => lang('email-driver-desc'),
			'value' => 'mail',
			'data' => array(
				'mail' => lang('use-built-in-mail'),
				'smtp' => lang('send-email-through-smtp')
			)
		),

		'email-smtp-host' => array(
			'type' => 'text',
			'title' => lang('smtp-host'),
			'description' => lang('email-smtp-host-desc'),
			'value' => '',
		),

		'email-smtp-username' => array(
			'type' => 'text',
			'title' => lang('smtp-username'),
			'description' => lang('email-smtp-username-desc'),
			'value' => '',
		),

		'email-smtp-password' => array(
			'type' => 'text',
			'title' => lang('smtp-password'),
			'description' => lang('email-smtp-password-desc'),
			'value' => '',
		),

		'email-smtp-port' => array(
			'type' => 'text',
			'title' => lang('smtp-port'),
			'description' => lang('email-smtp-port-desc'),
			'value' => '',
		),

		'email-ssl' => array(
			'type' => 'selection',
			'title' => lang('use-ssl'),
			'description' => lang('use-ssl-desc'),
			'value' => 'none',
			'data' => array(
				'none' => lang('none'),
				'tls' => 'TLS',
				'ssl' => 'SSL',
			)
		),

		/**'email-queue' => array(
		 * 'type' => 'boolean',
		 * 'title' => lang('email-queue'),
		 * 'description' => lang('email-queue-desc'),
		 * 'value' => '0',
		 * ),**/

		'email-mail-hash-expire-time' => array(
			'type' => 'selection',
			'title' => lang('email-hash-expire-time'),
			'description' => lang('email-hash-expire-time'),
			'value' => '3600',
			'data' => array(
				'600' => '10 '.lang('minutes'),
				'1800' => '30 '.lang('minutes'),
				'3600' => '1 '.lang('hours'),
				'7200' => '2 '.lang('hours'),
				'86400' => '24 '.lang('hours'),
				'172800' => '48 '.lang('hours'),
			)
		),

		'email-enable-subscription' => array(
			'type' => 'boolean',
			'title' => lang('enable-subscription'),
			'description' => lang('enable-subscription-desc'),
			'value' => '0',
		)
	);

	return $app->render(view('settings/content', array('settings' => $settings, 'title' => lang('email'), 'message' => '', 'description' => lang('email-settings-desc'))));
}

function mailing_pager($app) {
	$app->setTitle(lang('mailing-list'));

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
		 * @var $subscription
		 */
		extract($val);
		$body = html_purifier_purify($body);
		$db = null;
		if($to == 'all') {
			$db = db()->query("SELECT * FROM users ");
		} elseif($to == 'selected') {
			if(isset($selected)) {
				$selected = implode(',', $selected);
				$db = db()->query("SELECT * FROM users  WHERE id IN ({$selected}) ");
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

			$db = db()->query("SELECT * FROM users  WHERE online_time < {$time} ");
		} elseif(plugin_loaded('sitereseter') and $to == 'backups') {
			$db = db()->query("SELECT * FROM backup_emails");
		}
		if($db) {
			ini_set('max_execution_time', '0');
			$sent = 0;
			$total = 0;
			while($user = $db->fetch_assoc()) {
				$status = 1;
				if(isset($subscription) && $subscription) {
					save_admin_settings(array('email-enable-subscription' => 1));
					$mailing_list_subscription = mailer()->mailingListSubscription($user['id']);
					$hash = $mailing_list_subscription['hash'];
					$status = $mailing_list_subscription['status'];
					$content = $body.view('email/unsubscribe_button', array('hash' => $hash));
				} else {
					save_admin_settings(array('email-enable-subscription' => 0));
					$content = $body;
				}
				if($status) {
					try {
						mailer()->setAddress($user['email_address'])->setSubject($subject)->setMessage($content)->send();
						$sent++;
					} catch(Exception $e) {
					}
				}
				$total++;
			}
			$message = lang('num-mail-sent', array('num' => $sent.'/'.$total));
		} else {
			$message = lang('mail-not-sent');
		}
	}

	return $app->render(view('email/lists', array('message' => $message)));
}
