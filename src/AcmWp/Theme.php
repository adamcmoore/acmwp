<?php
namespace AcmWp;

use AcmWp\Theme\Images;
use AcmWp\Theme\Navigation;
use AcmWp\Theme\Templates;
use AcmWp\Theme\Customizer;


Class Theme extends App
{
	protected $theme_support = [];
	protected $post_formats = [];
	protected $image_sizes = [];
	protected $nav_menus = [];
	protected $scripts = [];
	protected $stylesheets = [];
	protected $customizer = [];
	protected $widget_areas = [];
	protected $colors = [];
	protected $font_sizes = [];



	public function __construct()
	{
		parent::__construct();

		$this->setupTheme();
		$this->setupScripts();
	}


	protected function setupTheme()
	{
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('wp_print_styles', 'print_emoji_styles');


		add_filter('wp_title', 	  [Templates::class, 'wpTitle'], 	10, 2);


		add_action('customize_register', function($wp_customize) {
			Customizer::register($wp_customize, $this->customizer);
		});


		add_action('after_setup_theme', function () {
			add_theme_support('editor-styles');

			foreach ($this->theme_support as $theme_support) {
				add_theme_support($theme_support);
			}

			if ($this->post_formats) {
				add_theme_support('post-formats', $this->post_formats);
			}

			if ($this->colors) {
				add_theme_support('editor-color-palette', $this->colors);
			}

			if ($this->font_sizes) {
				add_theme_support('editor-font-sizes', $this->font_sizes);
			}

			Images::registerSizes($this->image_sizes);
			Navigation::register($this->nav_menus);
		});



		add_action('init', function () {
			foreach ($this->widget_areas as $id => $widget_area) {
				$widget_area['id'] = $id;
				register_sidebar($widget_area);
			}
		});


		add_filter('body_class', function ($classes) {
			global $post;

			$classes[] = 'app';

			if ($post) {
				$classes[] = $post->post_type.'-'.$post->post_name;
			}

			return $classes;
		});
	}


	private function setupScripts()
	{
		add_action('wp_enqueue_scripts', function () {
			foreach ($this->scripts as $key => $options) {
				wp_register_script($key,
					get_template_directory_uri() . $options['path'],
					$options['deps'],
					$this->version,
					false
				);

				wp_localize_script($key, 'ajax_url', admin_url('admin-ajax.php'));
				wp_enqueue_script($key);
			}

			foreach ($this->stylesheets as $key => $path) {
				wp_enqueue_style($key,
					get_stylesheet_directory_uri() . $path,
					[],
					$this->version
				);
			}
		});


		add_action('admin_init', function() {
			foreach ($this->stylesheets as $key => $path) {
				add_editor_style(
					get_stylesheet_directory_uri() . $path.'?v='.$this->version
				);
			}
		});
	}



	protected function hideAdminBarForNonAdmins()
	{
		if (!current_user_can('manage_options') ) {
			add_filter('show_admin_bar', '__return_false');
		}
	}

}
