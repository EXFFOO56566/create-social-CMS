<?php
return array(
    'site-settings' => array(
        'title' => lang("System Controls"),
        'description' => lang("system-settings-description"),
        'id' => 'system-settings',
        'settings' => array(
            'debug' => array(
                'type' => 'boolean',
                'title' => lang('enable-debug'),
                'description' => lang('enable-debug-desc'),
                'value' => '0',
            ),

            'shutdown-site' => array(
                'type' => 'boolean',
                'title' => lang('shutdown-site'),
                'description' => lang('shutdown-site-desc'),
                'value' => '0',
            ),

            'shutdown-message' => array(
                'type' => 'textarea',
                'title' => lang('shutdown-message'),
                'description' => lang('shutdown-message-desc'),
                'value' => '',
            ),

            'https' => array(
                'type' => 'boolean',
                'title' => lang('enable-https'),
                'description' => lang('enable-https-desc'),
                'value' => '0',
            ),
            'tasks-run-key' => array(
                'title' => 'Run Access Key',
                'description' => 'Set your tasks run access key here',
                'type' => 'text',
                'value' => 'runaccesskey',
            ),
        )
    )
);