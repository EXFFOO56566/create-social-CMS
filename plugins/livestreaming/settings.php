<?php
include_once(path('plugins/livestreaming/functions/livestream.php'));
return array(
	'title' => 'LiveStream',
	'description' => "Control LiveStream plugin settings here",
	'settings' => array(
		'record-stream' => array(
			'type' => 'boolean',
			'title' => 'Enable LiveStream Recording',
			'description' => 'With this option you can disable or enable LiveStream recording, slow device\'s browser might crash if this feature is enabled',
			'value' => 0
		),
		'enable-pre-recorded' => array(
			'type' => 'boolean',
			'title' => 'Enable Pre-Recorded Media Streaming',
			'description' => 'If you want your user to be able to upload and stream video or mp3 music file',
			'value' => 0
		),
		'livestreaming-server' => array(
			'type' => 'selection',
			'title' => 'LiveStreaming Server',
			'description' => 'Set your configured LiveStream mediaserver, for best experience purchase a mediaserver',
			'value' => 'none',
			'data' => get_livestream_servers_for_settings(),
		),

		'livestream-list-limit' => array(
			'type' => 'text',
			'title' => 'livestream Listing Per Page',
			'description' => 'Set your limit per page listing of livestreams',
			'value' => '10'
		),

		'default-livestream-privacy' => array(
			'type' => 'selection',
			'title' => 'Default livestream Privacy',
			'description' => 'Set the default livestream privacy for your members when adding new livestreams',
			'value' => 1,
			'data' => array(
				'1' => lang('public'),
				'2' => lang('user-connections')
			)
		),

		'livestream-record-time' => array(
			'type' => 'text',
			'title' => 'Highest recording time (Seconds',
			'description' => 'Set highest recording time in seconds for livestream, streaming will continue but recording will stop',
			'value' => 600
		),
	)
);
 