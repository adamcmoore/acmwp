<?php
namespace AcmWp;



Class Request
{

	function __construct()
	{
		add_action('template_redirect', [$this, 'turboLinksHeaders']);
	}



	public function turboLinksHeaders()
	{
		$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
		$protocol = "http".$s;
		$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
		$url = $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];

		header("X-XHR-Current-Location: ". $url);
	}


	public static function has(string $key)
	{
		return array_has($_REQUEST, $key);
	}


	public static function get(string $key, $default = null)
	{
		return array_get($_REQUEST, $key, $default);
	}


	public static function url()
	{
		return $_SERVER['REQUEST_URI'];
	}


	public static function path()
	{
		$url = parse_url(self::url());
		return array_get($url, 'path');
	}


	public static function isAjax()
	{
		if( !empty($_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) &&
	      strtolower($_SERVER[ 'HTTP_X_REQUESTED_WITH' ]) == 'xmlhttprequest') {
	    	return true;
		} else {
			return false;
		}
	}


	public static function sanitizeUrl($url)
	{
		if (empty($url)) {
			return $url;
		}
		if (strpos($url, '://') === false) {
			$url = 'http://'. $url;
		}
		return $url;
	}
}
