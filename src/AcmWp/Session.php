<?php
namespace AcmWp;

class Session
{
	public static function prefix(): string
	{
		return wp_get_theme().'_';
	}

	public static function prefixKey(string $key): string
	{
		return self::prefix().$key;
	}


	public static function get(string $key, $default = null)
	{
		return array_get($_SESSION, self::prefixKey($key), $default);
	}

	
	public static function pull(string $key, $default = null)
	{
		$value = array_get($_SESSION, self::prefixKey($key), $default);
		unset($_SESSION[self::prefixKey($key)]);

		return $value;
	}


	public static function set(string $key, $value)
	{
		$_SESSION[self::prefixKey($key)] = $value;
	}


	public static function addError(string $message)
	{
		self::addMessage('error', $message);
	}
	

	public static function addSuccess(string $message)
	{
		self::addMessage('success', $message);
	}
	

	public static function addWarning(string $message)
	{
		self::addMessage('warning', $message);
	}
	

	public static function addInfo(string $message)
	{
		self::addMessage('info', $message);
	}
	
	
	public static function addMessage(string $type, string $message)
	{
		$messages = self::get('messages', []);
		$type_messages = array_get($messages, $type, []);
		$type_messages[] = $message;
		$messages[$type] = $type_messages;

		self::set('messages', $messages);
	}

	
	public static function getMessages()
	{
		return self::pull('messages', []);;
	}
}