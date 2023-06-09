<?php
return array(
    'site-other-settings' => array(
        'title' => 'Photo',
        'description' => lang("relationship::relationship-setting-description"),
        'settings' => array(
            'default-photo-album-privacy' => array(
                'type' => 'selection',
                'title' => lang('photo::default-photo-album-privacy'),
                'description' => lang('photo::default-photo-album-privacy-desc'),
                'value' => 1,
                'data' => array(
                    '1' => lang('public'),
                    '2' => lang('photo::friends-or-followers')
                )
            ),

            'photo-listing-per-page' => array(
                'type' => 'text',
                'title' => lang('photo::photo-listing-per-page'),
                'description' => lang('photo::photo-listing-per-page-desc'),
                'value' => 20,

            ),
            'photo-compulsory-profile-photo' => array(
                'type' => 'boolean',
                'title' => lang('photo::compulsory-photo'),
                'description' => lang('compulsory-photo-desc'),
                'value' => false
            ),
            'photo-album-listing-per-page' => array(
                'type' => 'text',
                'title' => lang('photo::photo-album-listing-per-page'),
                'description' => lang('photo::photo-album-listing-per-page-desc'),
                'value' => 20,

            ),
        )
    )
);
 