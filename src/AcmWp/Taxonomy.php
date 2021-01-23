<?php
namespace AcmWp;

use Doctrine\Common\Inflector\Inflector;


Class Taxonomy
{
	protected $name;
	protected $post_types = [];
	protected $args = [];
	protected $fields = [];


	function __construct()
	{
	}


	static function __callStatic($method, $params)
	{
		$instance = new self();

		if (method_exists($instance, '_'.$method)) {
			return call_user_func_array([$instance, '_'.$method], $params);
		}
	}


	public function register()
	{
		add_action('after_setup_theme', [$this, 'registerTaxonomy'], 10); // After Posts
		add_action('init', [$this, 'registerFields'], 10);
	}


	public function isBuiltIn()
	{
		return in_array($this->name, ['category', 'tag']);
	}


	public function registerTaxonomy()
	{
		if (!isset($this->name)) return;
		if (!isset($this->post_types)) return;

		if ($this->isBuiltIn()) {
			foreach ($this->post_types as $post_type) {
				register_taxonomy_for_object_type($this->name, $post_type);
			}

		} else {
			$singular 	= str_replace('_', ' ', $this->name);
			$plural 	= Inflector::pluralize($singular);

			$defaults = [
				'hierarchical' 	=> true,
				'query_var' 	=> true,
				'show_in_rest'  => true,
				'rewrite' 		=> [
					'slug'		=> Inflector::pluralize($this->name)
				],
				'labels' => [
					'name'               => ucwords($plural),
					'singular_name'      => ucwords($singular),
					'add_new'            => 'Add New',
					'add_new_item'       => 'Add New '.ucwords($singular),
					'edit_item'          => 'Edit '.ucwords($singular),
					'new_item'           => 'New '.ucwords($singular),
					'all_items'          => 'All '.ucwords($plural),
					'view_item'          => 'View '.ucwords($singular),
					'menu_name'          => ucwords($plural)
				]
			];

			register_taxonomy($this->name, $this->post_types, array_merge($defaults, $this->args));
		}
	}


	public function registerFields() {
		if (!function_exists("register_field_group")) return;

		$defaults = [
			'id' 		=> $this->name,
			'title' 	=> 'Options',
			'fields' 	=> [],
			'location' 	=> [[
				[
					'param' 	=> 'ef_taxonomy',
					'operator' 	=> '==',
					'value' 	=> $this->name,
				]
			]],
			'options' => [
				'position' 	=> 'normal',
				'layout' 	=> 'no_box',
			],
			'menu_order' => 0,
		];


		// Create all fields
		foreach ($this->fields as $key => $field_group) {
			if (!is_array($field_group) or count($field_group) === 0) {
				continue;
			}
			$config = wp_parse_args($field_group, $defaults);
			$config['fields'] = Fields::prefixFieldsKey($config['fields'], $this->name);

			register_field_group($config);
		}
	}


	public function _getAllTerms(bool $hide_empty = true, int $parent = null)
	{
		$query = [
			'taxonomy' 	 => $this->name,
			'hide_empty' => $hide_empty,
		];

		if (!is_null($parent)) {
			$query['parent'] = $parent;
		}

		return get_terms($query);
	}


	public function _getTerm(int $term_id)
	{
		return get_term($term_id, $this->name);
	}


	public function _getTermChildren(int $term_id)
	{
		return get_term_children($term_id, $this->name);
	}

}