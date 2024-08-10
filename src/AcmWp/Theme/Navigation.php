<?php
namespace AcmWp\Theme;

if (! defined('ABSPATH')) exit;

Class Navigation
{

	public static function register(array $menus)
	{
		register_nav_menus($menus);
	}


	public static function getMenuTitle($theme_location)
	{
		$locations = get_nav_menu_locations();
		$menu = wp_get_nav_menu_object(array_get($locations, $theme_location));

		if (isset($menu) && isset($menu->name)) {
			return $menu->name;
		} else {
			return '';
		}
	}


	public function menuHasItems($theme_location) {
		$locations = get_nav_menu_locations();
		$menu  = wp_get_nav_menu_object(array_get($locations, $theme_location));

		if (!isset($menu) || !$menu) {
			return false;
		} else {
			return ($menu->count > 0);
		}
	}
}