<?php
return array(
    'integrations' => array(
        'title' => lang('razorpay::razorpay'),
        'description' => lang("razorpay::razorpay-setting-description"),
        'settings' => array(
            'enable-razorpay' => array(
                'type' => 'boolean',
                'title' => lang('enable-razorpay'),
                'description' => lang('enable-razorpay-desc'),
                'value' => 0,
            ),
            'razor-api-key' => array(
                'title' => lang("razorpay::api-key"),
                'description' => lang("razorpay::api-key-desc"),
                'type' => 'text',
                'value' => ''
            ),
            'razor-secret-key' => array(
                'title' => lang("razorpay::api-secret-key"),
                'description' => lang("razorpay::api-secret-key-desc"),
                'type' => 'text',
                'value' => ''
            ),
        ),
    )
);