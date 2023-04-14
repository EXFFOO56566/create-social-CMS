<?php
function weather_get_ip() {
	$ip = server('REMOTE_ADDR');
	$private = preg_match('/^192\.168/', $ip);
	$valid = ip2long($ip) !== false;
	if(!$valid || $private) {
		try {
			$response = @file_get_contents('https://api.ipify.org/?format=json');
			$ip_info = json_decode($response);
			$ip = isset($ip_info->ip) ? $ip_info->ip : false;
		} catch(Exception $e) {
			$ip = false;
		}
	}
	return $ip;
}

function weather_get_ip_info($ip = null) {
	$ip = isset($ip) ? $ip : weather_get_ip();
	try {
		$url = 'http://ip-api.com/json/'.$ip;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$response = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		$ip_info = json_decode($response);
	} catch(Exception $e) {
		$ip_info = false;
	}
	return $ip_info;
}

function weather_get_weather_info() {
	try {
		$ip_info = weather_get_ip_info();
		$city_name = $ip_info->city;
		$region_code = $ip_info->region;
		$base_url = 'http://query.yahooapis.com/v1/public/yql';
		$sql = 'select * from weather.forecast where woeid in (select woeid from geo.places(1) where text="'.$city_name.', '.$region_code.'")';
		$url = $base_url.'?q='.urlencode($sql).'&format=json&u=c';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$contents = curl_exec($ch);
		$error = curl_error($ch);
		curl_close($ch);
		$phpObj = json_decode($contents);
		if(!(isset($phpObj->query->results) && $phpObj->query->results)) {
			return false;
		}
	} catch(Exception $e) {
		$phpObj = false;
	}
	return $phpObj;
}

function weather_get_temp($fahr) {
	$temp_unit = config('weather-temp-unit', 'F');
	if($temp_unit == 'F') {
		$temp = ceil($fahr).'°F';
	} else {
		$temp = ceil(($fahr - 32) * 0.55).'°C';
	}
	return $temp;
}