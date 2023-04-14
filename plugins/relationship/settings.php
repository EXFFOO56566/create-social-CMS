<?php
return array(
    'user' => array(
        'title' => lang("relationship::relationship-method"),
        'description' => lang("relationship::relationship-setting-description"),
        'settings' => array(
            'relationship-method' => array(
                'type' => 'selection',
                'title' => lang("relationship::relationship-method"),
                'description' => lang("relationship::relationship-method-desc"),
                'value' => 3,
                'data' => array(
                    '1' => lang("relationship::follow-system"),
                    '2' => lang("relationship::friend-system"),
                    '3' => lang("relationship::friend-follow-system")
                )
            ),
            'default-relationship-privacy' => array(
                'type' => 'selection',
                'title' => lang('relationship::default-relationship-privacy'),
                'description' => lang('relationship::default-relationship-privacy-desc'),
                'value' => 2,
                'data' => array(
                    '1' => lang('relationship::public'),
                    '2' => lang('relationship::user-connections')
                )
            ),
        )
    )
);