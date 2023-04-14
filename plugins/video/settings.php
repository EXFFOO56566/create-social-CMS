<?php
return array(
    'site-settings' => array(
        'title' => 'Video',
        'description' => "Control video plugin settings here",
        'settings' => array(
            'video-upload' => array(
                'type' => 'boolean',
                'title' => 'Enable Video Upload',
                'description' => 'With this option you can disable or enable video upload',
                'value' => 0
            ),
            'video-encoder' => array(
                'type' => 'selection',
                'title' => 'Video Encoder',
                'description' => 'Set your preferred video encoder, if none is selected only mp4 is allowed and will not be encoded',
                'value' => 'none',
                'data' => array(
                    'none' => 'None',
                    'ffmpeg' => 'FFmpeg',
                ),
            ),

            'video-list-limit' => array(
                'type' => 'text',
                'title' => 'Video Listing Per Page',
                'description' => 'Set your limit per page listing of videos',
                'value' => '10'
            ),

            'video-description-limit' => array(
                'type' => 'text',
                'title' => 'video description limit',
                'description' => 'Set video description limit',
                'value' => '100'
            ),

            'default-video-privacy' => array(
                'type' => 'selection',
                'title' => 'Default Video Privacy',
                'description' => 'Set the default video privacy for your members when adding new videos',
                'value' => 1,
                'data' => array(
                    '1' => lang('public'),
                    '2' => lang('user-connections')
                )
            ),

            'video-ffmpeg-path' => array(
                'type' => 'text',
                'title' => 'FFMpeg Path',
                'description' => 'Set your FFmpeg extension full path. <strong>For example : /usr/bin/ffmpeg</strong>',
                'value' => '/usr/bin/ffmpeg'
            ),

            'ignore-ffmpeg' => array(
                'type' => 'boolean',
                'title' => 'Ignore FFMPEG',
                'description' => 'This will ignore FFMPEG if it\'s not support by your server. Video will be uploaded at it is without full processing.',
                'value' => 0
            ),

            'feed-video-event' => array(
                'type' => 'selection',
                'title' => 'Feed Video Event',
                'description' => ' ',
                'value' => 1,
                'data' => array(lang('video::none'), lang('video::autoplay'), lang('video::stick'))
            ),

            /*'feed-video-dailymotion-api-key' => array(
                'type' => 'text',
                'title' => 'Dailymotion API Key',
                'description' => ' ',
                'value' => ''
            ),*/

            'enable-auto-video-processing' => array(
                'type' => 'boolean',
                'title' => 'Enable Automatic Video Processing',
                'description' => 'With this option you can disable or enable automatic video processing',
                'value' => 0
            ),
        )
    )
);
