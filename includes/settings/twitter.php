<?php
return array(
    'integrations' => array(
        'title' => 'Twitter Integration',
        'description' => lang("social::social-setting-description"),
        'id' => 'twitter-integration',
        'settings' => array(
            'enable-twitter' => array(
                'type' => 'boolean',
                'title' => lang('social::enable-twitter'),
                'description' => 'Enable Twitter Sign Up and Login',
                'value' => 0
            ),

            'twitter-username' => array(
                'type' => 'text',
                'title' => 'Twitter Username',
                'description' => 'The Username of your Web Application\'s Twitter App',
                'value' => ''
            ),

            'twitter-app-id' => array(
                'type' => 'text',
                'title' => 'Twitter App ID',
                'description' => 'The App ID of your Web Application\'s Twitter App',
                'value' => ''
            ),

            'twitter-secret-key' => array(
                'type' => 'text',
                'title' => 'Twitter App Secret',
                'description' => 'The App Secret of your Web Application\'s Twitter App',
                'value' => ''
            ),
        )
    )
);