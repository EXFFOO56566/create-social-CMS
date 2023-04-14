<?php
return array(
    'site-settings' => array(
        'title' => lang('translator'),
        'description' => '',
        'id' => 'system-task-settings',
        'settings' => array(
            'enable-text-translation' => array(
                'type' => 'boolean',
                'title' => 'Translate Text',
                'description' => '',
                'value' => 0,
            ),

            'microsoft-translate-text-api-key-1' => array(
                'type' => 'text',
                'title' => 'Microsoft Translate Text API KEY 1',
                'description' => '',
                'value' => ''
            ),

            'microsoft-translate-text-api-key-2' => array(
                'type' => 'text',
                'title' => 'Microsoft Translate Text API KEY 2',
                'description' => '',
                'value' => ''
            )
        )
    )
);
 