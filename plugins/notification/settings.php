<?php
return array(
    'site-settings' => array(
        'title' => 'Notification',
        'description' => lang("notification::notification-setting-description"),
        'settings' => array(
            'enable-email-notification' => array(
                'title' => lang('enable-email-notifications'),
                'description' => '',
                'type' => 'boolean',
                'value' => 1
            ),

            'notification-list-limit' => array(
                'title' => 'Notification List Limit',
                'description' => '',
                'type' => 'selection',
                'value' => 10,
                'data' => array(
                    '5' => 5,
                    '10' => 10,
                    '15' => 15,
                    '20' => 20,
                    '30' => 30,
                    '40' => 40,
                    '50' => 50
                )
            ),

			'notification-dropdown-list-limit' => array(
				'title' => 'Notification Drop Down List Limit',
				'description' => '',
				'type' => 'selection',
				'value' => 5,
				'data' => array(
					'2' => 2,
					'3' => '3',
					'5' => '5',
					'6' => 6,
					'7' => 7,
					'8' => 8,
					'9' => 9,
					'10' => 10,
					'15' => 15,
					'20' => 20
				)
			),

			'pusher-driver' => array(
				'type' => 'selection',
				'title' => lang('pusher-driver'),
				'description' => lang('pusher-driver-desc'),
				'value' => 'ajax',
				'data' => Pusher::getInstance()->lists()
			),

			'enable-push-notification' => array(
				'title' => lang('notification::enable-push-notification'),
				'description' => ' ',
				'type' => 'boolean',
				'value' => 1
			),

			'ajax-polling-interval' => array(
				'type' => 'selection',
				'title' => lang('ajax-polling-interval'),
				'description' => lang('ajax-polling-interval-desc'),
				'value' => '5000',
				'data' => array(
					'1000' => '1 '.lang('seconds'),
					'3000' => '3 '.lang('seconds'),
					'5000' => '5 '.lang('seconds'),
					'10000' => '10 '.lang('seconds'),
					'20000' => '20 '.lang('seconds'),
					'30000' => '30 '.lang('seconds'),
					'40000' => '40 '.lang('seconds'),
					'50000' => '50 '.lang('seconds'),
					'60000' => '1 '.lang('minute'),
					'120000' => '2 '.lang('minute'),
					'180000' => '3 '.lang('minute'),
					'240000' => '4 '.lang('minute'),
					'300000' => '5 '.lang('minute')
				)
			),
        ),
    ),
	'integrations' => array(
	    'title' => 'Firebase',
	    'description' => lang("notification::firebase-setting-description"),
	    'id' => 'firebase-settings',
	    'settings' => array(
			'firebase-api-key' => array(
				'title' => lang('notification::firebase-api-key'),
				'description' => lang('notification::firebase-api-key-desc'),
				'type' => 'text',
				'value' => ''
			),
			'firebase-api-key-legacy' => array(
				'title' => lang('notification::firebase-api-key-legacy'),
				'description' => lang('notification::firebase-api-key-legacy-desc'),
				'type' => 'text',
				'value' => ''
			),
			'firebase-auth-domain' => array(
				'title' => lang('notification::firebase-auth-domain'),
				'description' => lang('notification::firebase-auth-domain-desc'),
				'type' => 'text',
				'value' => ''
			),
			'firebase-database-url' => array(
				'title' => lang('notification::firebase-database-url'),
				'description' => lang('notification::firebase-database-url-desc'),
				'type' => 'text',
				'value' => ''
			),
			'firebase-project-id' => array(
				'title' => lang('notification::firebase-project-id'),
				'description' => lang('notification::firebase-project-id-desc'),
				'type' => 'text',
				'value' => ''

			),
			'firebase-storage-bucket' => array(
				'title' => lang('notification::firebase-storage-bucket'),
				'description' => lang('notification::firebase-storage-bucket-desc'),
				'type' => 'text',
				'value' => ''
			),
			'firebase-messaging-sender-id' => array(
				'title' => lang('notification::firebase-messaging-sender-id'),
				'description' => lang('notification::firebase-messaging-sender-id-desc'),
				'type' => 'text',
				'value' => ''
			),
			'firebase-public-vapid-key' => array(
				'title' => lang('notification::firebase-public-vapid-key'),
				'description' => lang('notification::firebase-public-vapid-key-desc'),
				'type' => 'text',
				'value' => ''
			)
		)
	)
);
 