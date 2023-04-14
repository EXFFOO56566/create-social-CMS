<?php
return array(
    'site-other-settings' => array(
        'title' => 'Currency Conversion',
        'description' => lang("currencyconversion::currencyconversion"),
        'id' => 'currency-system-settings',
        'settings' => array(
            'enable-currencyconversion' => array(
                'type' => 'boolean',
                'title' => lang('currencyconversion::enable-currencyconversion'),
                'description' => '',
                'value' => 1
            )
        )
    )
);