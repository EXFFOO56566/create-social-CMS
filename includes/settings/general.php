<?php
return array(
    'site-settings' => array(
        'title' => lang("general"),
        'description' => lang("general-settings-description"),
        'id' => 'general-site-settings',
        'settings' => array(
            'site_title' => array(
                'type' => 'text',
                'title' => lang('your-site-title'),
                'description' => lang('your-site-title-desc'),
                'value' => 'sociaVIBE',
            ),

            'title_separator' => array(
                'type' => 'text',
                'title' => lang('your-site-title-separator'),
                'description' => lang('your-site-title-separator'),
                'value' => '-',
            ),

            'site-keywords' => array(
                'type' => 'text',
                'title' => lang('site-keywords'),
                'description' => '',
                'value' => '',
            ),

            'site-description' => array(
                'type' => 'text',
                'title' => lang('site-description'),
                'description' => '',
                'value' => '',
            ),

            'user-home' => array(
                'type' => 'text',
                'title' => lang('user-home'),
                'description' => 'Logged in users home page link',
                'value' => url('feed')
            ),

            'timezone' => array(
                'type' => 'selection',
                'title' => lang('set-your-timezone'),
                'description' => lang('set-your-timezone-desc'),
                'value' => 'GMT',
                'data' => get_timezones()
            ),
            'pusher-driver' => array(
                'type' => 'selection',
                'title' => lang('pusher-driver'),
                'description' => lang('pusher-driver-desc'),
                'value' => 'ajax',
                'data' => Pusher::getInstance()->lists()
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


            'session_timeout' => array(
                'type' => 'selection',
                'title' => lang('enable-session-timeout'),
                'description' => lang('enable-session-timeout-desc'),
                'value' => 1800,
                'data' => array(
                    '0' => 'Disabled',
                    '300' => '5 '.lang('minutes'),
                    '600' => '10 '.lang('minutes'),
                    '900' => '15 '.lang('minutes'),
                    '1800' => '30 '.lang('minutes'),
                    '3600' => '1 '.lang('hour'),
                    '86400' => '24 '.lang('hours')
                )
            ),
            'enable-membership' => array(
                'type' => 'boolean',
                'title' => lang('enable-membership'),
                'description' => lang('enable-membership-desc'),
                'value' => 0,
            ),
            'enable-captcha' => array(
                'type' => 'selection',
                'title' => lang('enable-captcha'),
                'description' => lang('enable-captcha-desc'),
                'value' => 0,
                'data' => array(
                    '1' => 'In-Build Captcha',
                    '2' => 'Google reCaptcha',
                    '0' => 'None'
                )
            ),
            'enable-gdpr' => array(
                'type' => 'boolean',
                'title' => lang('enable-gdpr'),
                'description' => lang('enable-gdpr-desc'),
                'value' => 0
            ),
            'enable-csrf' => array(
                'type' => 'boolean',
                'title' => 'Enable CSRF',
                'description' => '',
                'value' => 1,
            ),
            'google-analytics-code' => array(
                'title' => 'Google Analytics Code',
                'description' => lang('google-analytics-code-desc'),
                'type' => 'textarea',
                'value' => '',
            ),
        ),
    ),
    'integrations' => array(
        'title' => lang("Recaptcha"),
        'description' => lang("Recaptcha Settings"),
        'id' => 'recaptcha-integration-settings',
        'settings' => array(
            'recaptcha-key' => array(
                'type' => 'text',
                'title' => 'reCaptcha Key',
                'description' => '',
                'value' => '',
            ),
            'recaptcha-secret' => array(
                'type' => 'text',
                'title' => 'reCaptcha Secret',
                'description' => '',
                'value' => '',
            ),
        )
    )
);