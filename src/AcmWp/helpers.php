<?php

if (!function_exists('truncate')) {
	function truncate($text, $limit, $words = false, $strip_html = true) {
		if ($strip_html) {
			$text = strip_tags($text);
			$text = htmlspecialchars_decode($text);
		}


		if ($words) {
			if ($limit > 0 && str_word_count($text, 0) > $limit) {
				$words = str_word_count($text, 2);
				$pos = array_keys($words);
				$text = trim(substr($text, 0, $pos[$limit])) . '&hellip;';
			}
		} else {
			if ($limit > 0 && strlen($text) > $limit) {
				$text = trim(substr($text, 0, $limit)) . '&hellip;';
			}
		}

		return $text;
	}
}


if (!function_exists('dump')) {
	function dump($var)
	{
		var_dump($var);
	}
}


if (!function_exists('dd')) {
	function dd($var)
	{
		dump($var);
		die();
	}
}