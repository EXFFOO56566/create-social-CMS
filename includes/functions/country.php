<?php
/**
 * function to add country
 * @param string $name
 * @return boolean
 */
function add_country($name) {
	if(country_exists($name)) return false;
	db()->query("INSERT INTO `countries` (`country_name`) VALUES('{$name}')");
	fire_hook("country_added", $name, array($name));
	forget_cache("countries");
	return true;
}

function country_exists($name) {
	$query = db()->query("SELECT country_name FROM `countries` WHERE `country_name`='{$name}'");
	if($query and $query->num_rows > 0) return true;
	return false;
}

function get_countries() {
	if(cache_exists("countries")) {
		return get_cache("countries");
	} else {
		$query = db()->query("SELECT `country_name`,`id` FROM `countries` ORDER BY `country_name` ASC ");
		if($query) {
			$r = fetch_all($query);
			$result = array();
			foreach($r as $k) {
				$result[$k['id']] = $k['country_name'];
			}
			set_cacheForever("countries", $result);
			return $result;
		} else {
			return array();
		}
	}
}

function get_country($id) {
	$countries = get_countries();
	if(isset($countries[$id])) return $countries[$id];
	return false;
}

function save_country($id, $name) {
	db()->query("UPDATE `countries` SET `country_name`='{$name}' WHERE `id`='{$id}'");
	forget_cache("countries");
}

function delete_country($id) {
	db()->query("DELETE FROM `countries` WHERE `id`='{$id}'");
	forget_cache("countries");
}

function count_states($id) {
	return count(get_states($id));
}

function get_states($id) {
	$key = "country_".$id."_states";
	if(cache_exists($key)) {
		return get_cache($key);
	} else {
		$query = db()->query("SELECT `id`,`state_name` FROM `country_states` WHERE `country_id`='{$id}' ORDER BY `state_name`");
		if($query) {
			$r = fetch_all($query);
			$result = array();
			foreach($r as $k) {
				$result[$k['id']] = $k['state_name'];
			}
			set_cacheForever($key, $result);
			return $result;
		} else {
			return array();
		}
	}
}

function add_state($id, $name) {
	$key = "country_".$id."_states";
	if(state_exists($id, $name)) return false;
	$query = db()->query("INSERT INTO `country_states` (country_id,state_name) VALUES('{$id}','{$name}')");
	forget_cache($key);
	fire_hook("state_added", $name, array($id, $name));
	return true;
}

function state_exists($id, $name) {
	$query = db()->query("SELECT state_name FROM `country_states` WHERE `country_id`='{$id}' AND `state_name`='{$name}'");
	if($query and $query->num_rows > 0) return true;
	return false;
}

function get_state($id) {
	$query = db()->query("SELECT state_name,country_id FROM country_states WHERE id='{$id}'");
	if($query) return fetch_all($query);
	return false;
}

function delete_state($id, $country) {
	db()->query("DELETE FROM `country_states` WHERE `id`='{$id}'");
	$key = "country_".$country."_states";
	forget_cache($key);
}

function save_state($id, $name, $country) {
	db()->query("UPDATE country_states SET state_name='{$name}',country_id='{$country}' WHERE id='{$id}'");
	$key = "country_".$country."_states";
	forget_cache($key);
	return true;
}

function is_valid_country($country) {
	$countries = get_countries();
	if(in_array($country, $countries)) return true;
	return false;
}

function get_world_languages() {
	return array(
		'af' => array(
			'name' => 'afrikaans',
			'title' => 'Afrikaans'
		),
		'sq' => array(
			'name' => 'albanian',
			'title' => 'Albanian'
		),
		'am' => array(
			'name' => 'amharic',
			'title' => 'Amharic'
		),
		'ar' => array(
			'name' => 'arabic',
			'title' => 'Arabic'
		),
		'hy' => array(
			'name' => 'armenian',
			'title' => 'Armenian'
		),
		'az' => array(
			'name' => 'azerbaijani',
			'title' => 'Azerbaijani'
		),
		'eu' => array(
			'name' => 'basque',
			'title' => 'Basque'
		),
		'be' => array(
			'name' => 'belarusian',
			'title' => 'Belarusian'
		),
		'bn' => array(
			'name' => 'bengali',
			'title' => 'Bengali'
		),
		'bs' => array(
			'name' => 'bosnian',
			'title' => 'Bosnian'
		),
		'bg' => array(
			'name' => 'bulgarian',
			'title' => 'Bulgarian'
		),
		'ca' => array(
			'name' => 'catalan',
			'title' => 'Catalan'
		),
		'ceb' => array(
			'name' => 'cebuano',
			'title' => 'Cebuano'
		),
		'ny' => array(
			'name' => 'chichewa',
			'title' => 'Chichewa'
		),
		'zh-CN' => array(
			'name' => 'chinese-simplified',
			'title' => 'Chinese (Simplified)'
		),
		'zh-TW' => array(
			'name' => 'chinese-traditional',
			'title' => 'Chinese (Traditional)'
		),
		'co' => array(
			'name' => 'corsican',
			'title' => 'Corsican'
		),
		'hr' => array(
			'name' => 'croatian',
			'title' => 'Croatian'
		),
		'cs' => array(
			'name' => 'czech',
			'title' => 'Czech'
		),
		'da' => array(
			'name' => 'danish',
			'title' => 'Danish'
		),
		'nl' => array(
			'name' => 'dutch',
			'title' => 'Dutch'
		),
		'en' => array(
			'name' => 'english',
			'title' => 'English'
		),
		'eo' => array(
			'name' => 'esperanto',
			'title' => 'Esperanto'
		),
		'et' => array(
			'name' => 'estonian',
			'title' => 'Estonian'
		),
		'tl' => array(
			'name' => 'filipino',
			'title' => 'Filipino'
		),
		'fi' => array(
			'name' => 'finnish',
			'title' => 'Finnish'
		),
		'fr' => array(
			'name' => 'french',
			'title' => 'French'
		),
		'fy' => array(
			'name' => 'frisian',
			'title' => 'Frisian'
		),
		'gl' => array(
			'name' => 'galician',
			'title' => 'Galician'
		),
		'ka' => array(
			'name' => 'georgian',
			'title' => 'Georgian'
		),
		'de' => array(
			'name' => 'german',
			'title' => 'German'
		),
		'el' => array(
			'name' => 'greek',
			'title' => 'Greek'
		),
		'gu' => array(
			'name' => 'gujarati',
			'title' => 'Gujarati'
		),
		'ht' => array(
			'name' => 'haitian-creole',
			'title' => 'Haitian Creole'
		),
		'ha' => array(
			'name' => 'hausa',
			'title' => 'Hausa'
		),
		'haw' => array(
			'name' => 'hawaiian',
			'title' => 'Hawaiian'
		),
		'iw' => array(
			'name' => 'hebrew',
			'title' => 'Hebrew'
		),
		'hi' => array(
			'name' => 'hindi',
			'title' => 'Hindi'
		),
		'hmn' => array(
			'name' => 'hmong',
			'title' => 'Hmong'
		),
		'hu' => array(
			'name' => 'hungarian',
			'title' => 'Hungarian'
		),
		'is' => array(
			'name' => 'icelandic',
			'title' => 'Icelandic'
		),
		'ig' => array(
			'name' => 'igbo',
			'title' => 'Igbo'
		),
		'id' => array(
			'name' => 'indonesian',
			'title' => 'Indonesian'
		),
		'ga' => array(
			'name' => 'irish',
			'title' => 'Irish'
		),
		'it' => array(
			'name' => 'italian',
			'title' => 'Italian'
		),
		'ja' => array(
			'name' => 'japanese',
			'title' => 'Japanese'
		),
		'jw' => array(
			'name' => 'javanese',
			'title' => 'Javanese'
		),
		'kn' => array(
			'name' => 'kannada',
			'title' => 'Kannada'
		),
		'kk' => array(
			'name' => 'kazakh',
			'title' => 'Kazakh'
		),
		'km' => array(
			'name' => 'khmer',
			'title' => 'Khmer'
		),
		'ko' => array(
			'name' => 'korean',
			'title' => 'Korean'
		),
		'ku' => array(
			'name' => 'kurdish-Kurmanji',
			'title' => 'Kurdish (Kurmanji)'
		),
		'ky' => array(
			'name' => 'kyrgyz',
			'title' => 'Kyrgyz'
		),
		'lo' => array(
			'name' => 'lao',
			'title' => 'Lao'
		),
		'la' => array(
			'name' => 'latin',
			'title' => 'Latin'
		),
		'lv' => array(
			'name' => 'latvian',
			'title' => 'Latvian'
		),
		'lt' => array(
			'name' => 'lithuanian',
			'title' => 'Lithuanian'
		),
		'lb' => array(
			'name' => 'luxembourgish',
			'title' => 'Luxembourgish'
		),
		'mk' => array(
			'name' => 'macedonian',
			'title' => 'Macedonian'
		),
		'mg' => array(
			'name' => 'malagasy',
			'title' => 'Malagasy'
		),
		'ms' => array(
			'name' => 'malay',
			'title' => 'Malay'
		),
		'ml' => array(
			'name' => 'malayalam',
			'title' => 'Malayalam'
		),
		'mt' => array(
			'name' => 'maltese',
			'title' => 'Maltese'
		),
		'mi' => array(
			'name' => 'maori',
			'title' => 'Maori'
		),
		'mr' => array(
			'name' => 'marathi',
			'title' => 'Marathi'
		),
		'mn' => array(
			'name' => 'mongolian',
			'title' => 'Mongolian'
		),
		'my' => array(
			'name' => 'myanmar-burmese',
			'title' => 'Myanmar (Burmese)'
		),
		'ne' => array(
			'name' => 'nepali',
			'title' => 'Nepali'
		),
		'no' => array(
			'name' => 'norwegian',
			'title' => 'Norwegian'
		),
		'ps' => array(
			'name' => 'pashto',
			'title' => 'Pashto'
		),
		'fa' => array(
			'name' => 'persian',
			'title' => 'Persian'
		),
		'pl' => array(
			'name' => 'polish',
			'title' => 'Polish'
		),
		'pt' => array(
			'name' => 'portuguese',
			'title' => 'Portuguese'
		),
		'pa' => array(
			'name' => 'punjabi',
			'title' => 'Punjabi'
		),
		'ro' => array(
			'name' => 'romanian',
			'title' => 'Romanian'
		),
		'ru' => array(
			'name' => 'russian',
			'title' => 'Russian'
		),
		'sm' => array(
			'name' => 'samoan',
			'title' => 'Samoan'
		),
		'gd' => array(
			'name' => 'scots-gaelic',
			'title' => 'Scots Gaelic'
		),
		'sr' => array(
			'name' => 'serbian',
			'title' => 'Serbian'
		),
		'st' => array(
			'name' => 'sesotho',
			'title' => 'Sesotho'
		),
		'sn' => array(
			'name' => 'shona',
			'title' => 'Shona'
		),
		'sd' => array(
			'name' => 'sindhi',
			'title' => 'Sindhi'
		),
		'si' => array(
			'name' => 'sinhala',
			'title' => 'Sinhala'
		),
		'sk' => array(
			'name' => 'slovak',
			'title' => 'Slovak'
		),
		'sl' => array(
			'name' => 'slovenian',
			'title' => 'Slovenian'
		),
		'so' => array(
			'name' => 'somali',
			'title' => 'Somali'
		),
		'es' => array(
			'name' => 'spanish',
			'title' => 'Spanish'
		),
		'su' => array(
			'name' => 'sundanese',
			'title' => 'Sundanese'
		),
		'sw' => array(
			'name' => 'swahili',
			'title' => 'Swahili'
		),
		'sv' => array(
			'name' => 'swedish',
			'title' => 'Swedish'
		),
		'tg' => array(
			'name' => 'tajik',
			'title' => 'Tajik'
		),
		'ta' => array(
			'name' => 'tamil',
			'title' => 'Tamil'
		),
		'te' => array(
			'name' => 'telugu',
			'title' => 'Telugu'
		),
		'th' => array(
			'name' => 'thai',
			'title' => 'Thai'
		),
		'tr' => array(
			'name' => 'turkish',
			'title' => 'Turkish'
		),
		'uk' => array(
			'name' => 'ukrainian',
			'title' => 'Ukrainian'
		),
		'ur' => array(
			'name' => 'urdu',
			'title' => 'Urdu'
		),
		'uz' => array(
			'name' => 'uzbek',
			'title' => 'Uzbek'
		),
		'vi' => array(
			'name' => 'vietnamese',
			'title' => 'Vietnamese'
		),
		'cy' => array(
			'name' => 'welsh',
			'title' => 'Welsh'
		),
		'xh' => array(
			'name' => 'xhosa',
			'title' => 'Xhosa'
		),
		'yi' => array(
			'name' => 'yiddish',
			'title' => 'Yiddish'
		),
		'yo' => array(
			'name' => 'yoruba',
			'title' => 'Yoruba'
		),
		'zu' => array(
			'name' => 'zulu',
			'title' => 'Zulu'
		)
	);
}

function get_world_locales() {
	return array(
		'af-ZA',
		'am-ET',
		'ar-AE',
		'ar-BH',
		'ar-DZ',
		'ar-EG',
		'ar-IQ',
		'ar-JO',
		'ar-KW',
		'ar-LB',
		'ar-LY',
		'ar-MA',
		'arn-CL',
		'ar-OM',
		'ar-QA',
		'ar-SA',
		'ar-SY',
		'ar-TN',
		'ar-YE',
		'as-IN',
		'az-Cyrl-AZ',
		'az-Latn-AZ',
		'ba-RU',
		'be-BY',
		'bg-BG',
		'bn-BD',
		'bn-IN',
		'bo-CN',
		'br-FR',
		'bs-Cyrl-BA',
		'bs-Latn-BA',
		'ca-ES',
		'co-FR',
		'cs-CZ',
		'cy-GB',
		'da-DK',
		'de-AT',
		'de-CH',
		'de-DE',
		'de-LI',
		'de-LU',
		'dsb-DE',
		'dv-MV',
		'el-GR',
		'en-029',
		'en-AU',
		'en-BZ',
		'en-CA',
		'en-GB',
		'en-IE',
		'en-IN',
		'en-JM',
		'en-MY',
		'en-NZ',
		'en-PH',
		'en-SG',
		'en-TT',
		'en-US',
		'en-ZA',
		'en-ZW',
		'es-AR',
		'es-BO',
		'es-CL',
		'es-CO',
		'es-CR',
		'es-DO',
		'es-EC',
		'es-ES',
		'es-GT',
		'es-HN',
		'es-MX',
		'es-NI',
		'es-PA',
		'es-PE',
		'es-PR',
		'es-PY',
		'es-SV',
		'es-US',
		'es-UY',
		'es-VE',
		'et-EE',
		'eu-ES',
		'fa-IR',
		'fi-FI',
		'fil-PH',
		'fo-FO',
		'fr-BE',
		'fr-CA',
		'fr-CH',
		'fr-FR',
		'fr-LU',
		'fr-MC',
		'fy-NL',
		'ga-IE',
		'gd-GB',
		'gl-ES',
		'gsw-FR',
		'gu-IN',
		'ha-Latn-NG',
		'he-IL',
		'hi-IN',
		'hr-BA',
		'hr-HR',
		'hsb-DE',
		'hu-HU',
		'hy-AM',
		'id-ID',
		'ig-NG',
		'ii-CN',
		'is-IS',
		'it-CH',
		'it-IT',
		'iu-Cans-CA',
		'iu-Latn-CA',
		'ja-JP',
		'ka-GE',
		'kk-KZ',
		'kl-GL',
		'km-KH',
		'kn-IN',
		'kok-IN',
		'ko-KR',
		'ky-KG',
		'lb-LU',
		'lo-LA',
		'lt-LT',
		'lv-LV',
		'mi-NZ',
		'mk-MK',
		'ml-IN',
		'mn-MN',
		'mn-Mong-CN',
		'moh-CA',
		'mr-IN',
		'ms-BN',
		'ms-MY',
		'mt-MT',
		'nb-NO',
		'ne-NP',
		'nl-BE',
		'nl-NL',
		'nn-NO',
		'nso-ZA',
		'oc-FR',
		'or-IN',
		'pa-IN',
		'pl-PL',
		'prs-AF',
		'ps-AF',
		'pt-BR',
		'pt-PT',
		'qut-GT',
		'quz-BO',
		'quz-EC',
		'quz-PE',
		'rm-CH',
		'ro-RO',
		'ru-RU',
		'rw-RW',
		'sah-RU',
		'sa-IN',
		'se-FI',
		'se-NO',
		'se-SE',
		'si-LK',
		'sk-SK',
		'sl-SI',
		'sma-NO',
		'sma-SE',
		'smj-NO',
		'smj-SE',
		'smn-FI',
		'sms-FI',
		'sq-AL',
		'sr-Cyrl-BA',
		'sr-Cyrl-CS',
		'sr-Cyrl-ME',
		'sr-Cyrl-RS',
		'sr-Latn-BA',
		'sr-Latn-CS',
		'sr-Latn-ME',
		'sr-Latn-RS',
		'sv-FI',
		'sv-SE',
		'sw-KE',
		'syr-SY',
		'ta-IN',
		'te-IN',
		'tg-Cyrl-TJ',
		'th-TH',
		'tk-TM',
		'tn-ZA',
		'tr-TR',
		'tt-RU',
		'tzm-Latn-DZ',
		'ug-CN',
		'uk-UA',
		'ur-PK',
		'uz-Cyrl-UZ',
		'uz-Latn-UZ',
		'vi-VN',
		'wo-SN',
		'xh-ZA',
		'yo-NG',
		'zh-CN',
		'zh-HK',
		'zh-MO',
		'zh-SG',
		'zh-TW',
		'zu-ZA'
	);
}

function get_lang_name($id) {
	$id = isset($id) ? $id : get_lang_id();
	$languages = get_world_languages();
	return isset($languages[$id]) ? $languages[$id]['name'] : $id;
}

function get_lang_title($id) {
	$id = isset($id) ? $id : get_lang_id();
	$languages = get_world_languages();
	return isset($languages[$id]) ? $languages[$id]['title'] : $id;
}

function get_lang_id($name = null) {
	$name = isset($name) ? $name : app()->lang;
	$lang_id = $name;
	$languages = get_world_languages();
	foreach($languages as $id => $language) {
		if($language['name'] == $name) {
			$lang_id = $id;
			break;
		}
	}
	return $lang_id;
}

function get_lang_locales($lang_id = null, $encode = null) {
	$lang_id = isset($lang_id) ? $lang_id : get_lang_id();
	$locales = get_world_locales();
	$lang_locales = array();
	foreach($locales as $locale) {
		preg_match('/'.preg_quote($lang_id, '/').'\-.*/', $locale, $matches);
		if(isset($matches[0]) && $matches[0]) {
			$lang_locales[] = str_replace('-', '_', $matches[0]);
		}
	}
	if(isset($encode)) {
		array_walk($lang_locales, function(&$value) use ($encode) {
			$value .= '.'.$encode;
		});
	}
	return $lang_locales;
}

function get_lang_flag($name = null) {
	$name = isset($name) ? $name : app()->lang;
	$lang_id = get_lang_id($name);
	$flag_path = 'themes/default/images/flags/'.$lang_id.'.png';
	$country_flags = array();
	if(file_exists(path($flag_path))) {
		return $flag_path;
	}
	$locales = get_lang_locales($lang_id);
	$recommended_countries = array('us', 'de', 'it', 'jp', 'cn', 'sa', 'es');
	$recommended_countries = fire_hook('language.flags.recommends', $recommended_countries);
	foreach($locales as $locale) {
		preg_match('/'.preg_quote($lang_id, '/').'_.*/', $locale, $matches);
		$array = explode('_', $matches[0]);
		$country_id = strtolower($array[(count($array) - 1)]);
		if(isset($matches[0]) && $matches[0]) {
			$lang_locales[] = str_replace('-', '_', $matches[0]);
			$flag_path = 'themes/default/images/flags/'.$country_id.'.png';
			if(file_exists(path($flag_path))) {
				$country_flags[$country_id] = $flag_path;
			}
		}
	}
	if($country_flags) {
		foreach($recommended_countries as $country_id) {
			if(isset($country_flags[$country_id])) {
				return $country_flags[$country_id];
			}
		}
		foreach($country_flags as $flag) {
			return $flag;
		}
	}
	return 'themes/default/images/flags/flag.png';
}

function date_separator() {
	return config('event-date-separator');
}

function time_separator() {
	return config('event-time-format');
}