<?php

function builder_pager($app) {
	$app->setTitle(lang('menu-builder'));
	$message = '';
	return $app->render(view('site_menu/builder/index', array('message' => $message)));
}
