<?php
return array(
    'site-settings' => array(
        'title' => lang('media'),
        'description' => lang("media-settings-description"),
        'id' => 'media-site-settings',
        'settings' => array(
            'max-image-size' => array(
                'title' => lang('maximum-upload-image-size').' (Bytes)',
                'description' => lang('maximum-upload-image-size-desc'),
                'type' => 'text',
                'value' => '10000000'
            ),

            'image-file-types' => array(
                'title' => 'Allow Image File Types',
                'description' => 'Set allowed image file types',
                'type' => 'text',
                'value' => 'jpg,png,gif,jpeg'
            ),
            'video-file-types' => array(
                'title' => 'Allow Video File Types',
                'description' => 'Set allowed video file types',
                'type' => 'text',
                'value' => 'mp4,mov,wmv,3gp,avi,flv,f4v,webm'
            ),
            'files-file-types' => array(
                'title' => 'Allow Files  Types',
                'description' => 'Set allowed files types',
                'type' => 'text',
                'value' => 'doc,xml,exe,txt,zip,rar,mp3,jpg,png,css,psd,pdf,3gp,ppt,pptx,xls,xlsx,docx,fla,avi,mp4,swf,ico,gif,jpeg'
            ),
            'support-animated-image' => array(
                'title' => lang('enable-support-for-animated-image'),
                'description' => lang('enable-support-for-animated-image-desc'),
                'type' => 'boolean',
                'value' => 1,
            ),

            'enable-video-upload' => array(
                'title' => lang('enable-video-upload-support'),
                'description' => lang('enable-video-upload-support-desc'),
                'type' => 'boolean',
                'value' => 1
            ),

            'max-video-upload' => array(
                'title' => lang('maximum-upload-video-size').' (Bytes)',
                'description' => lang('maximum-upload-video-size-desc'),
                'type' => 'text',
                'value' => '10000000'
            ),

            'max-audio-upload' => array(
                'title' => lang('maximum-upload-audio-size').' (Bytes)',
                'description' => lang('maximum-upload-audio-size-desc'),
                'type' => 'text',
                'value' => '10000000'
            ),

            'enable-attachment-files' => array(
                'title' => lang('enable-file-attachment-upload'),
                'description' => lang('enable-file-attachment-upload-desc'),
                'type' => 'boolean',
                'value' => 1
            ),

            'max-file-upload' => array(
                'title' => lang('maximum-file-attacment-upload-size').' (Bytes)',
                'description' => lang('maximum-file-attacment-upload-size-desc'),
                'type' => 'text',
                'value' => '10000000'
            ),

            'allow-guest-download-file' => array(
                'title' => 'Allow Guest To Download File',
                'description' => '',
                'type' => 'boolean',
                'value' => 1
            ),

            'max-photos-upload' => array(
                'title' => 'Max number of image upload at once',
                'description' => '',
                'type' => 'text',
                'value' => 10
            )
        )
    )
);
 