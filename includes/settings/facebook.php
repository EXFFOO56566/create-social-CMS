<?php
return array(
    'integrations' => array(
        'title' => 'Facebook Integration',
        'description' => lang("social::social-setting-description"),
        'id' => 'facebook-integration',
        'settings' => array(
            'enable-facebook' => array(
                'type' => 'boolean',
                'title' => lang('social::enable-facebook'),
                'description' => 'Enable Facebook Sign Up, Login and Contact Import',
                'value' => 0
            ),

            'facebook-app-id' => array(
                'type' => 'text',
                'title' => 'Facebook App ID',
                'description' => 'The App ID of your Web Application\'s Facebook App',
                'value' => ''
            ),

            'facebook-secret-key' => array(
                'type' => 'text',
                'title' => 'Facebook App Secret',
                'description' => 'The App Secret of your Web Application\'s Facebook App',
                'value' => ''
            ),
        )
    )
);