<?php

function currencyconversion_pager($app) {
	$Currency = getCurrency();
	return $app->render(view('currencyconversion::currencyconversion', array('Currency' => $Currency)));


}