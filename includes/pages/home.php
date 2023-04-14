<?php

function home_pager($app) {
	//$design = config('home-design', 'splash');
	if(is_loggedIn()) redirect_to_pager('feed');
	$app->onHeader = (config('hide-homepage-header', false)) ? false : true;
	$app->setTitle(lang('welcome-to-social'));
	return $app->render();
}

function translate_pager($app) {
	///CSRFProtection::validate(false);
	require_once(path('includes/libraries/MicrosoftTranslateText.php'));
	$azure_key = config('microsoft-translate-text-api-key-2');
	$content = input('text');
	$to_language = input('to', 'en');
	$from_language = input('from');
	$azure_translator = new MicrosoftTranslateText($azure_key);
	try {
		$translation = $translation = $azure_translator->getTranslation($content, $to_language, $from_language);
		$result = format_output_text($translation);
	} catch(Exception $exception) {
		$message = 'Invalid Microsoft Translate Text API KEY 1';
		$result = $message;
	}
	return $result;
}