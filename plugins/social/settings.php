<?php
return array(
    'integrations' => array(
        'title' => 'Vk Integration',
        'description' => lang("social::social-setting-description"),
        'id' => 'vk-integration',
        'settings' => array(
            'enable-vk' => array(
                'type' => 'boolean',
                'title' => lang('social::enable-vk'),
                'description' => 'Enable VK Sign Up and Login',
                'value' => 0
            ),

            'vk-app-id' => array(
                'type' => 'text',
                'title' => 'VK App ID',
                'description' => 'The App ID of your Web Application\'s VK App',
                'value' => ''
            ),

            'vk-secret-key' => array(
                'type' => 'text',
                'title' => 'VK App Secret',
                'description' => 'The App ID of your Web Application\'s VK App',
                'value' => ''
            ),
        )
    )
);
