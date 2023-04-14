<?php
return array(
    'user' => array(
        'title' => lang('profile-completion-settings'),
        'description' => lang("profile-completion-settings-desc"),
        'id' => 'profile-complete-settings',
        'settings' => array(
            'completion-first_name' => array(
                'type' => 'text',
                'title' => lang('first-name'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-last_name' => array(
                'type' => 'text',
                'title' => lang('last-name'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-username' => array(
                'type' => 'text',
                'title' => lang('username'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-email_address' => array(
                'type' => 'text',
                'title' => lang('email'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-cover' => array(
                'type' => 'text',
                'title' => lang('cover'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-avatar' => array(
                'type' => 'text',
                'title' => lang('avatar'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-country' => array(
                'type' => 'text',
                'title' => lang('country'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-state' => array(
                'type' => 'text',
                'title' => lang('state'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-city' => array(
                'type' => 'text',
                'title' => lang('city'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-gender' => array(
                'type' => 'text',
                'title' => lang('gender'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-bio' => array(
                'type' => 'text',
                'title' => lang('first-name'),
                'description' => '',
                'value' => 8.33
            ),
            'completion-m_status' => array(
                'type' => 'text',
                'title' => lang('status'),
                'description' => '',
                'value' => 8.33
            ),
        )
    ),
    'site-settings' => array(
        'title' => lang('Users Profile Completion'),
        'description' => lang("Enables users to complete their Profile"),
        'id' => 'profile-complete-settings',
        'settings' => array(
            'enable-profile-completion' => array(
                'type' => 'boolean',
                'title' => lang('Profile Completion'),
                'description' => '',
                'value' => false
            ),
            'enable-force-profile-completion' => array(
                'type' => 'boolean',
                'title' => lang('Force Profile Completion'),
                'description' => '',
                'value' => false
            ),
            'profile-completion-bar-color' => array(
                'type' => 'color',
                'title' => 'Profile Completion background bar Color',
                'description' => 'Profile Completion background bar Color. Default is <strong> #0d6996</strong>',
                'value' => ' #0d6996'
            ),
            'profile-completion-progress-bar-color' => array(
                'type' => 'color',
                'title' => 'Profile Completion progress bar Color',
                'description' => 'Profile Completion progress bar Color. Default is <strong> #007bff</strong>',
                'value' => ' #007bff'
            ),
        )
    )
);