<?php
namespace AcmWp\Theme;

if (! defined('ABSPATH')) exit;

Class Templates
{

	public static function  wpTitle($title, $sep)
	{
		if (is_feed()) {
			return $title;
		}

		global $page, $paged;

		$blog_name = get_bloginfo('name', 'display');

		// Add the blog name
		if (is_home() || is_front_page()) {
			$title = $blog_name;
		} else {
			$title .=  ' // '. $blog_name;
		}

		// Add a page number if necessary:
		if (($paged >= 2 || $page >= 2 ) && ! is_404()) {
			$title .= " // " . sprintf( __('Page %s', '_s'), max($paged, $page));
		}

		return $title;
	}


	public static function loadTemplatePart($template_name, $part_name = null, $data = [])
	{
	    ob_start();

	    if (isset($data) && is_array($data)) {
		    foreach ($data as $key => $value) {
		    	$key = strtolower($key);
		    	set_query_var(str_replace('-', '_', $key), $value);
		    }
		}

	    get_template_part($template_name, $part_name);
	    $var = ob_get_contents();
	    ob_end_clean();

	    return $var;
	}


	public static function getExcerpt($post_id)
	{
		$the_post = get_post($post_id); //Gets post ID
		$the_excerpt = $the_post->post_content; //Gets post_content to be used as a basis for the excerpt
		$excerpt_length = 35; //Sets excerpt length by word count
		$the_excerpt = strip_tags(strip_shortcodes($the_excerpt)); //Strips tags and images
		$words = explode(' ', $the_excerpt, $excerpt_length + 1);

		if(count($words) > $excerpt_length) :
		    array_pop($words);
		    array_push($words, '&hellip;');
		    $the_excerpt = implode(' ', $words);
		endif;

		return $the_excerpt;
	}
}