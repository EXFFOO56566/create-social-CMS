<?php
return array(
    'site-other-settings' => array(
        'title' => lang('event::event'),
        'description' => '',
        'settings' => array(
            'event-time-format' => array(
                'title' => lang('event::event-time-format'),
                'description' => lang('event::event-time-format-desc'),
                'type' => 'selection',
                'value' => '12',
                'data' => array(
                    '12' => lang('event::12-hour-format'),
                    '24' => lang('event::24-hour-format')
                )
            ),
            'event-date-separator' => array(
                'title' => lang('event::event-date-separator'),
                'description' => lang('event::event-date-separator-desc'),
                'type' => 'selection',
                'value' => ' ',
                'data' => array(
                    ' ' => lang('event::space').' ( )',
                    '-' => lang('event::dash').' (-)',
                    '.' => lang('event::dot').' (.)',
                    '/' => lang('event::slash').' (/)',
                )
            ),
            'event-date-order' => array(
                'title' => lang('event::event-date-order'),
                'description' => lang('event::event-date-order-desc'),
                'type' => 'selection',
                'value' => 'day',
                'data' => array(
                    'day' => lang('event::day-comes-first'),
                    'month' => lang('event::month-comes-first')
                )
            ),
        )
    )
);