<?php
return array(
    'integrations' => array(
        'title' => 'Google Integration',
        'description' => lang("social::social-setting-description"),
        'id' => 'google-integration',
        'settings' => array(

            'enable-googleplus' => array(
                'type' => 'boolean',
                'title' => lang('social::enable-g+'),
                'description' => 'Enable Google Plus Sign Up and Login',
                'value' => 0
            ),

            'google-api-key' => array(
                'type' => 'text',
                'title' => 'Google API Key',
                'description' => 'The Google Browser Key or Server Key of your Web Application',
                'value' => 'AIzaSyCekO3PhGgE-H9yOO4z-o0q0aOmm4M0JEA'
            ),

            'google-oauth-client-id' => array(
                'type' => 'text',
                'title' => 'Google OAuth Client ID',
                'description' => 'The Google OAuth Client ID of your Web Application',
                'value' => ''
            ),

            'google-oauth-client-secret' => array(
                'type' => 'text',
                'title' => 'Google OAuth Client Secret',
                'description' => 'The Google OAuth Client Secret of your Web Application',
                'value' => ''
            )
        )
    )
);