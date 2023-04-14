<?php
return array(
    'site-other-settings' => array(
        'title' => 'Page ',
        'description' => lang("Page settings"),
        'settings' => array(
            'enable-inline-pages-suggestion' => array(
                'type' => 'boolean',
                'title' => lang('Enable page feed inline suggestion'),
                'description' => lang('Enable page feed inline suggestion'),
                'value' => true
            ),
            'pages-render-after-post-number' => array(
                'type' => 'text',
                'title' => lang('Render pages'),
                'description' => lang('Render pages after feed number'),
                'value' => 10,

            ),
            'inline-pages-suggestion-limit' => array(
                'type' => 'text',
                'title' => lang('Inline Page Limit'),
                'description' => lang('Number of pages to be displayed'),
                'value' => 10,

            ),
        )
    )
);
