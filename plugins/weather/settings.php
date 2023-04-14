<?php
return array(
    'user-other-settings' => array(
        'title' => lang('weather::weather-settings'),
        'description' => '',
        'settings' => array(
            'weather-temp-unit' => array(
                'type' => 'selection',
                'title' => lang('weather::temperature-unit'),
                'description' => '',
                'value' => 'F',
                'data' => array(
                    'C' => lang('weather::centigrade'),
                    'F' => lang('weather::fahrenheit')
                )
            )
        )
    )
);
 