<?php
return array(
	'title' => 'Default Theme ',
	'description' => 'Customize default theme here',
	'settings' => array(
		'enable-popup-language-selection' => array(
			'type' => 'boolean',
			'title' => 'Enable Homepage Popup Language Selection',
			'description' => 'Do you want a popup language selection on homepage for member first visit',
			'value' => 1
		),
		'hide-homepage-header' => array(
			'type' => 'boolean',
			'title' => 'Hide Header From Homepage',
			'description' => 'With this option you can hide header from homepage',
			'value' => 0
		),
		'show-explorer' => array(
			'type' => 'boolean',
			'title' => 'Show Explorer for Website',
			'description' => 'With this option you can hide explorer from website',
			'value' => 0
		),
		'show-nav-brand-logo' => array(
			'type' => 'boolean',
			'title' => 'Show Nav Brand Logo',
			'description' => 'Show/Hide Nav Brand Logo',
			'value' => 1
		),
		'show-nav-brand-title' => array(
			'type' => 'boolean',
			'title' => 'Show Nav Brand Title',
			'description' => 'Show/Hide Nav Brand Title',
			'value' => 0
		),
		'show-sidebar' => array(
			'type' => 'boolean',
			'title' => 'Show Side Bar for Website',
			'description' => 'With this option you can hide Side Bar from website',
			'value' => 1
		),
		'main-menu-limit' => array(
			'type' => 'text',
			'title' => 'Main Menu List Limit',
			'description' => 'Set the number of menu items to show before going into explorer menu',
			'value' => 8
		),
		'show-main-menu-guest' => array(
			'type' => 'boolean',
			'title' => 'Show Main Menu to Guest',
			'description' => 'Set if you want to show main menu to guest',
			'value' => 0
		),
		'verify-badge-color' => array(
			'type' => 'color',
			'title' => 'Verify Badge Color',
			'description' => 'Set verify badge color. Default is <strong>#19B5FE</strong>',
			'value' => '#19B5FE'
		),
		'loading-line-color' => array(
			'type' => 'color',
			'title' => 'Ajax Loading Line Color',
			'description' => 'Set ajax loading line color. Default is <strong>#F62459</strong>',
			'value' => '#F62459'
		),
		'header-bg-color' => array(
			'type' => 'color',
			'title' => 'Header Background Color',
			'description' => 'Set header background color. Default is <strong>#4C4C4E</strong>',
			'value' => '#4C4C4E'
		),
		'header-brand-color' => array(
			'type' => 'color',
			'title' => 'Header Brand Color',
			'description' => 'Set header brand color. Default is <strong>#FFFFFF</strong>',
			'value' => '#FFFFFF'
		),
		'header-search-background' => array(
			'type' => 'color',
			'title' => 'Header Search Background Color',
			'description' => 'Set header search background color. Default is <strong>#1C2733</strong>',
			'value' => '#1C2733'
		),
		'header-search-placeholder-color' => array(
			'type' => 'color',
			'title' => 'Header Search Placeholder Color',
			'description' => 'Set header search placeholder color. Default is <strong>#FFFFFF</strong>',
			'value' => '#FFFFFF'
		),
		'header-search-text-color' => array(
			'type' => 'color',
			'title' => 'Header Search Text Color',
			'description' => 'Set header search text color. Default is <strong>#FFFFFF</strong>',
			'value' => '#FFFFFF'
		),
		'header-menu-color' => array(
			'type' => 'color',
			'title' => 'Header Menu Text Color',
			'description' => 'Set header menu color. Default is <strong>#FFFFFF</strong>',
			'value' => '#FFFFFF'
		),
		'header-menu-icon-color' => array(
			'type' => 'color',
			'title' => 'Header Menu Icon Color',
			'description' => 'Set header menu icon color. Default is <strong>#FFFFFF</strong>',
			'value' => '#FFFFFF'
		),
		'footer-bg-color' => array(
			'type' => 'color',
			'title' => 'Footer Background Color',
			'description' => 'Set footer background color. Default is <strong>#CCCCCC</strong>',
			'value' => '#CCCCCC'
		),
		'landing-reg-bg-color' => array(
			'type' => 'color',
			'title' => 'Landing Sign Up Block Background Color',
			'description' => 'Set Landing Sign Up Block Background Color. Default is <strong>rgba(46, 62, 69, 0.36)</strong>',
			'value' => 'rgba(46, 62, 69, 0.36)'
		),
		'welcomes-notes-font' => array(
			'type' => 'text',
			'title' => 'Landing welcomes notes Font',
			'description' => 'Set thr default welcome note font. Default is <strong>cursive</strong>',
			'value' => 'Segoe UI, Arial, sans-serif'
		),
		'link-color' => array(
			'type' => 'color',
			'title' => 'Link Color',
			'description' => 'Set Link color. Default is <strong>#2B354F</strong>',
			'value' => '#2B354F'
		),
		'explorer-bg-color' => array(
			'type' => 'color',
			'title' => 'Explorer Background Color',
			'description' => 'Set explorer background color. Default is <strong>#F4F4F4</strong>',
			'value' => '#F4F4F4'
		),
		'explorer-link-color' => array(
			'type' => 'color',
			'title' => 'Explorer Link Color',
			'description' => 'Set explorer background color. Default is <strong>#1C2331</strong>',
			'value' => '#1C2331'
		),
		'explorer-dropdown-bg-color' => array(
			'type' => 'color',
			'title' => 'Explorer Dropdown Background Color',
			'description' => 'Set explorer background color. Default is <strong>#626A73</strong>',
			'value' => '#626A73'
		),
		'explorer-dropdown-text-color' => array(
			'type' => 'color',
			'title' => 'Explorer Dropdown Text Color',
			'description' => 'Set explorer text color. Default is <strong>#808080</strong>',
			'value' => '#808080'
		),
		'main-body-bg-color' => array(
			'type' => 'color',
			'title' => 'Main Body Background Color',
			'description' => 'Set main body background color. Default is <strong>#E9EBEE</strong>',
			'value' => '#E9EBEE'
		),
		'chat-list-bg-color' => array(
			'type' => 'color',
			'title' => 'Chat List Background Color',
			'description' => 'Set caht online list background color. Default is <strong>#F2F2F2</strong>',
			'value' => '#F2F2F2'
		),
		'button-bg-color' => array(
			'type' => 'color',
			'title' => 'Action Button Background Color',
			'description' => 'Set Button background color. Default is <strong>#4C4C4C</strong>',
			'value' => '#0abde3'
		),
		'dropdown-menu-hover-bg-color' => array(
			'type' => 'color',
			'title' => 'Dropdown Menu Hover Background Color',
			'description' => 'Set dropdown menu hover background color. Default is <strong>#54A0FF</strong>',
			'value' => '#54A0FF'
		),
		'dropdown-menu-hover-text-color' => array(
			'type' => 'color',
			'title' => 'Dropdown Menu Hover Text Color',
			'description' => 'Set dropdown menu hover text color. Default is <strong>#FFFFFF</strong>',
			'value' => '#FFFFFF'
		),
		'button-text-color' => array(
			'type' => 'color',
			'title' => 'Action Button Text Color',
			'description' => 'Set Button text color. Default is <strong>#FFFFFF</strong>',
			'value' => '#FFFFFF'
		),
        'scroll-bar-size' => array(
            'type' => 'text',
            'title' => 'Scrollbar Size',
            'description' => 'Set Scrollbar Size. Default is <strong>4px</strong>',
            'value' => '4px'
        ),
        'scroll-bar-height' => array(
            'type' => 'text',
            'title' => 'Scrollbar Height',
            'description' => 'Set Scrollbar Height. Default is <strong>4px</strong>',
            'value' => '4px'
        ),
	)
);