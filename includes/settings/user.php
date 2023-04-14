<?php
return array(
    'user' => array(
        'title' => lang('users'),
        'description' => lang('user-settings-description'),
        'id' => 'user-site-settings',
        'settings' => array(
            'design-profile' => array(
                'type' => 'boolean',
                'title' => lang('enable-profile-design'),
                'description' => lang('enable-profile-design-desc'),
                'value' => 1,
            ),
            'enable-last-seen' => array(
                'type' => 'boolean',
                'title' => lang('enable-profile-lastseen'),
                'description' => '',
                'value' => 1,
            ),
            'request-verification' => array(
                'type' => 'boolean',
                'title' => lang('verification-request'),
                'description' => lang('verification-request-desc'),
                'value' => 1,
            ),

            'default-profile-privacy' => array(
                'type' => 'selection',
                'title' => lang('default-profile-privacy'),
                'description' => lang('default-profile-privacy-desc'),
                'value' => 1,
                'data' => array(
                    '1' => lang('everyone'),
                    '2' => lang('friends-followers')
                )
            ),
            'default-birthdate-privacy' => array(
                'type' => 'selection',
                'title' => lang('default-birthdate-privacy'),
                'description' => lang('default-birthdate-privacy-desc'),
                'value' => 1,
                'data' => array(
                    '1' => lang('everyone'),
                    '2' => lang('friends-followers')
                )
            ),
            'enable-birthdate' => array(
                'type' => 'boolean',
                'title' => lang('enable-birthdate'),
                'description' => lang('enable-birthdate-desc'),
                'value' => 1
            ),

            'birthdate-min-age' => array(
                'type' => 'text',
                'title' => lang('birthdate-minimum-age'),
                'description' => lang('birthdate-minimum-age-desc'),
                'value' => '10'
            ),

            'login-trial-enabled' => array(
                'type' => 'boolean',
                'title' => lang('enable-login-trial'),
                'description' => lang('enable-login-trial-desc'),
                'value' => '1',
            ),

            'login-trials-limit' => array(
                'type' => 'selection',
                'title' => lang('login-trial-limit'),
                'description' => lang('login-trial-limit-desc'),
                'value' => '5',
                'data' => array('5' => '5 Times', '10' => '10 Times', '15' => '15 Times', '20' => '20 Times')
            ),

            'login-trial-wait-time' => array(
                'type' => 'selection',
                'title' => lang('login-trial-wait-time'),
                'description' => lang('login-trial-wait-time'),
                'value' => '15',
                'data' => array(
                    '5' => '5 '.lang('minutes'),
                    '10' => '10 '.lang('minutes'),
                    '15' => '15 '.lang('minutes'),
                    '20' => '20 '.lang('minutes'),
                    '30' => '30 '.lang('minutes')
                )
            ),

            'allow-change-email' => array(
                'type' => 'boolean',
                'title' => lang('allow-change-email'),
                'description' => lang('allow-change-email-desc'),
                'value' => '1',
            ),


            'allow-change-username' => array(
                'type' => 'boolean',
                'title' => lang('allow-change-username'),
                'description' => lang('allow-change-username-desc'),
                'value' => '1',
            ),

            'loose-verify-badge-username' => array(
                'type' => 'boolean',
                'title' => lang('remove-verify-badge-change-username'),
                'description' => lang('remove-verify-badge-change-username-desc'),
                'value' => '1',
            ),

            'enable-gender' => array(
                'type' => 'boolean',
                'title' => lang('enable-gender-display'),
                'description' => lang('enable-gender-display-desc'),
                'value' => '1',
            ),
            'enable-gender-filter' => array(
                'type' => 'boolean',
                'title' => lang('people::enable-gender-filter'),
                'description' => lang('people::enable-gender-filter-desc'),
                'value' => true
            ),
            'enable-age-filter' => array(
                'type' => 'boolean',
                'title' => lang('people::enable-age-filter'),
                'description' => lang('people::enable-age-filter-desc'),
                'value' => true
            ),
            'enable-country-filter' => array(
                'type' => 'boolean',
                'title' => lang('people::enable-country-filter'),
                'description' => lang('people::enable-country-filter-desc'),
                'value' => true
            ),
            'enable-online-filter' => array(
                'type' => 'boolean',
                'title' => lang('people::enable-online-filter'),
                'description' => lang('people::enable-gender-filter-desc'),
                'value' => true
            ),
            'enable-feature-filter' => array(
                'type' => 'boolean',
                'title' => lang('people::enable-feature-filter'),
                'description' => lang('people::enable-gender-filter-desc'),
                'value' => true
            ),
            'max-page-result' => array(
                'type' => 'text',
                'title' => lang('people::max-page-result'),
                'description' => lang('people::max-page-result-desc'),
                'value' => '20'
            ),
            'featured-badge-bg-color' => array(
                'title' => lang('people::featured-badge-bg-color'),
                'description' => lang('people::featured-badge-bg-color-desc'),
                'type' => 'text',
                'value' => 'rgba(255, 0, 0, 0.5)'
            ),

            'featured-badge-text-color' => array(
                'title' => lang('people::featured-badge-text-color'),
                'description' => lang('people::featured-badge-text-color-desc'),
                'type' => 'text',
                'value' => '#FFCCCC'
            ),
            'people-dashboard-menu-link' => array(
                'type' => 'boolean',
                'title' => lang('people::show-people-dashboard-link'),
                'description' => lang('people::show-people-dashboard-link-desc'),
                'value' => false
            ),
            'people-explorer-menu-link' => array(
                'type' => 'boolean',
                'title' => lang('people::show-people-explorer-link'),
                'description' => lang('people::show-people-explorer-link-desc'),
                'value' => true
            ),
            'people-footer-menu-link' => array(
                'type' => 'boolean',
                'title' => lang('people::show-people-footer-link'),
                'description' => lang('people::show-people-footer-link-desc'),
                'value' => false
            ),
            'user-name-option' => array(
                'type' => 'boolean',
                'title' => lang('enable-username-display'),
                'description' => lang('enable-username-display-desc'),
                'value' => false
            )
        )
    )
);
 