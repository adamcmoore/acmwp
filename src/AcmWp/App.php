<?php
namespace AcmWp;


class App
{
	protected $file;
	protected $version = '1.0';
	protected $post_types = [];
	protected $taxonomies = [];
	protected $blocks = [];
	protected $block_categories = [];
	protected $block_styles = [];
	protected $users = [];


	public function __construct()
	{
		if (WP_DEBUG) {
			$this->version .= '.'.rand(0, 100000);
		}

		$this->setupGlobals();
		$this->setupPostTypes();
		$this->setupTaxonomies();
		$this->setupBlockCategories();
		$this->setupBlocks();
		$this->setupBlockStyles();
		$this->setupUsers();
	}


	protected function setupGlobals()
	{
		$this->file = __FILE__;
	}


	protected function setupUsers()
	{
		foreach ($this->users as $user) {
			$user->register();
		}
	}


	protected function setupPostTypes()
	{
		foreach ($this->post_types as $post_type) {
			$post_type->register();
		}
	}


	protected function setupTaxonomies()
	{
		foreach ($this->taxonomies as $taxonomy) {
			$taxonomy->register();
		}
	}


	protected function setupBlocks()
	{
		foreach ($this->blocks as $block) {
			$block->register();
		}
	}


	protected function setupBlockCategories()
	{
		add_filter('block_categories', function($categories) {
			return array_merge($categories,  $this->block_categories);
		});
	}


	protected function setupBlockStyles()
	{

		add_action('admin_head', function() {
			$tmpl = "wp.blocks.registerBlockStyle('%s', {
				name: '%s',
				label: '%s',
				isDefault: %s,
			});";


			echo '<script>';
			echo "document.addEventListener('DOMContentLoaded', function() {";

			foreach ($this->block_styles as $block => $styles) {
				foreach ($styles as $name => $style) {
					$default = array_get($style, 'default', false);
					$default = json_encode($default);
					printf($tmpl, $block, $name, $style['label'], $default);
				}
			}

			echo "});";
			echo '</script>';
		});
	}
}