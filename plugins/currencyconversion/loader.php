<?php

register_pager('currency-convert', array('use' => 'currencyconversion::currencyconversion@currencyconversion_pager', 'as' => 'currency-convert', 'filter' => 'user-auth'));
load_functions("currencyconversion::currencyconversion");
register_asset("currencyconversion::css/currencyconversion.css");
register_asset("currencyconversion::js/currencyconversion.js");
register_hook("role.permissions", function($roles) {
	$roles[] = array(
		'title' => lang('currencyconversion::currencyconversion-permissions'),
		'description' => '',
		'roles' => array(
			'can-use-currency-converter' => array('title' => lang('currencyconversion::can-use-currency-converter'), 'value' => 1),
		)
	);
	return $roles;
});