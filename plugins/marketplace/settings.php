<?php
return array(
    'site-other-settings' => array(
        'title' => lang('marketplace::marketplace'),
        'description' => lang("marketplace::marketplace-setting-description"),
        'settings' => array(
            'pagination-limit-listings' => array(
                'type' => 'text',
                'title' => lang('marketplace::pagination-limit-listings'),
                'description' => lang('marketplace::pagination-limit-listings-desc'),
                'value' => '20'
            ),

            'pagination-limit-comments' => array(
                'type' => 'text',
                'title' => lang('marketplace::pagination-limit-comments'),
                'description' => lang('marketplace::pagination-limit-comments-desc'),
                'value' => '4'
            ),

            'default-approval' => array(
                'type' => 'selection',
                'title' => lang('marketplace::default-approval'),
                'description' => lang('marketplace::default-approval-desc'),
                'value' => '1',
                'data' => array(
                    '0' => lang('marketplace::dont-approve'),
                    '1' => lang('marketplace::approve')
                )
            ),

            'max-num-listing-photos' => array(
                'type' => 'text',
                'title' => lang('marketplace::max-num-listing-photos'),
                'description' => lang('marketplace::max-num-listing-photos-desc'),
                'value' => '5'
            ),

            'listing-truncate-comment' => array(
                'title' => lang('marketplace::listing-truncate-comment'),
                'description' => lang('marketplace::listing-truncate-comment-desc'),
                'type' => 'boolean',
                'value' => 0
            ),

            'currency' => array(
                'title' => lang('marketplace::currency'),
                'description' => lang('marketplace::currency-desc'),
                'type' => 'text',
                'value' => '$'
            ),
        )
    )
);