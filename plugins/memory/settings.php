<?php
return array(
    'user-other-settings' => array(
        'title' => 'Memory',
        'description' => lang("memory::memory"),
        'settings' => array(
            'memories-interval' => array (
                'type' => 'selection',
                'value' => 2,
                'description' => lang("memory::memory"),
                'title' => lang('memory-interval'),
                'data' => array(
                    1 => lang('memory::yearly'),
                    2 => lang('memory::monthly'),
                    3 => lang('memory::weekly'),
                )
            )
        )
    )
);