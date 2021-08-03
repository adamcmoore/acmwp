<?php
namespace AcmWp;

use Doctrine\Common\Inflector\Inflector;


Class PostType
{
	protected $name;

	protected $default_admin_orderby = 'name';
	protected $default_admin_order = 'ASC';
	protected $excerpt_length = 80;
	protected $excerpt_more = '&hellip;';
	protected $page_size = 20;
	protected $args = [];
	protected $fields = [];
	protected $metaboxes = [];


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
		add_action('after_setup_theme', [$this, 'registerPostType'], 1);
		add_action('init', [$this, 'registerFields'], 1);
		add_filter('register_post_type_args', [$this, 'registerPostTypeArgs'], 10, 2);

		add_filter('manage_edit-' . $this->name . '_columns', [$this, 'adminColumns']);
		add_filter('manage_edit-' . $this->name . '_sortable_columns', [$this, 'adminColumnSorts']);
		add_action('manage_' . $this->name . '_posts_custom_column', [$this, 'adminColumnVals'], 10, 2);
		add_action('pre_get_posts', [$this, 'adminColumnSort']);

		add_action('pre_get_posts', [$this, 'pageSize'], 999, 1);
		add_action('add_meta_boxes', [$this, 'displayMetaboxes']);
		add_action('save_post', [$this, 'processMetaboxes'], 10, 2);

		add_filter('excerpt_length', [$this, 'excerptLength']);
		add_filter('get_the_excerpt', [$this, 'getTheExcerpt'], 10, 2);
		add_filter('excerpt_more', [$this, 'excerptMore']);
		add_filter('the_content', [$this, 'tidyContent'], 9999, 1); // Run After wpautop
	}



// Registration
// ===============================================================================================================
	public function registerPostType()
	{
		if (!isset($this->name)) {
			return;
		}
		if (post_type_exists($this->name)) {
			return;
		}

		$singular       = str_replace('_', ' ', $this->name);
		$plural         = Inflector::pluralize($singular);
		$frontend_title = get_option($this->name . '_label');

		if ($frontend_title && array_key_exists('frontend_title', $this->args['labels'])) {
			$this->args['labels']['frontend_title'] = $frontend_title;
		} else {
			$frontend_title = ucwords($plural);
		}

		$defaults = [
			'labels'      => [
				'frontend_title'     => $frontend_title,
				'name'               => ucwords($plural),
				'singular_name'      => ucwords($singular),
				'add_new'            => 'Add New',
				'add_new_item'       => 'Add New ' . ucwords($singular),
				'edit_item'          => 'Edit ' . ucwords($singular),
				'new_item'           => 'New ' . ucwords($singular),
				'all_items'          => 'All ' . ucwords($plural),
				'view_item'          => 'View ' . ucwords($singular),
				'search_items'       => 'Search ' . ucwords($plural),
				'not_found'          => 'No ' . $plural . ' found',
				'not_found_in_trash' => 'No ' . $plural . ' found in the Trash',
				'menu_name'          => ucwords($plural)
			],
			'description' => ucwords($plural),
			'rewrite'     => [
				'slug' => Inflector::pluralize($this->name)
			]
		];

		register_post_type($this->name, array_replace_recursive($defaults, $this->args));
	}


	public function registerPostTypeArgs($args, $post_type)
	{
		if ($this->name !== $post_type) return $args;
		if (empty($this->args)) return $args;

		$args = array_replace_recursive($args, $this->args);

		return $args;
	}


	public function registerFields()
	{
		if (!function_exists("register_field_group")) {
			return;
		}

		$defaults = [
			'fields'   => [],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => $this->name,
					]
				]
			],
			'options' => [
				'position'       => 'normal',
				'layout'         => 'default',
				'hide_on_screen' => []
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



// Filters
// ===============================================================================================================
	public function pageSize($query)
	{
		if (is_admin() || ! $query->is_main_query()) {
			return;
		}
		if ($query->get('post_type') !== $this->name) {
			return;
		}
		if (!$query->is_post_type_archive) {
			return;
		}

		$query->set('posts_per_page', object_get($this, 'page_size', $this->page_size));
	}


	public function tidyContent($content)
	{
		// Fix WP Auto-Pee as placing p's in tags.
		$content = preg_replace('/(<[^\n\r\t>]*)[\n\r\t]+([^\n\r\t>]*>)/imx', '$1$2', $content);
		// Strip out naughty spacers
		//$content =  preg_replace('/(\n)(&nbsp;)+/im', '\1 \3', $content);

		return $content;
	}


	public function getTheExcerpt($excerpt, $post)
	{
		if ($post->post_type !== $this->name) return $excerpt;

		$excerpt = strip_tags($excerpt);
		$excerpt = wp_trim_words($excerpt, $this->excerpt_length, $this->excerpt_more);

		return $excerpt;
	}


	public function excerptLength($length)
	{
		return $this->excerpt_length;
	}


	public function excerptMore($more)
	{
		return $this->excerpt_more;
	}



// Admin Columns
// ==============================================================================================
	public function adminColumns($columns)
	{
		foreach (object_get($this, 'columns', []) as $key => $config) {
			if ($config === false) {
				unset($columns[$key]);

			} else {
				$columns[$key] = $config['label'];
			}
		}

		return $columns;
	}


	public function adminColumnSorts($columns) {
		foreach (object_get($this, 'columns', []) as $key => $config) {
			if (array_get($config, 'orderby')) {
				$columns[$key] = $key;
			}
		}

		return $columns;
	}


	public function adminColumnSort($query) {
		if ( ! is_admin()) {
			return;
		}
		if ($this->name !== $query->get('post_type')) {
			return;
		}

		$order_by = $query->get('orderby');
		$order    = $query->get('order', 'ASC');

		if (isset($this->columns) && array_get($this->columns, $order_by . '.orderby')) {
			$order_by = array_get($this->columns, $order_by . '.orderby');

		} elseif ( ! $order_by) {
			$order_by = $this->default_admin_orderby;
			if ( ! $order_by) {
				$order_by = 'post_title';
			}
			$order = $this->default_admin_order;
		}

		$query->set('orderby', $order_by);
		$query->set('order', $order);
	}


	public function adminColumnVals($column_name, $post_id) {
		if (isset($this->columns) && array_get($this->columns, $column_name)) {
			$fn = Inflector::classify($column_name);
			if (is_callable([$this, 'render' . $fn . 'ColumnVal'])) {
				$value = call_user_func([$this, 'render' . $fn . 'ColumnVal'], $post_id);
			} else {
				$value = get_field($column_name, $post_id);
			}
			echo $value;
		}
	}



// Meta Boxes
// ==============================================================================================
	public function displayMetaboxes($post_type) {
		if ($post_type !== $this->name) {
			return;
		}

		foreach ($this->metaboxes as $key => $config) {
			$nonce_key = 'acm_metabox_' . $key;

			$method = [$this, 'show' . studly_case($key) . 'Metabox'];
			if (is_callable($method)) {
				$visible = call_user_func($method);
				if ( ! $visible) {
					continue;
				}
			}

			$method = [$this, 'display' . studly_case($key) . 'Metabox'];

			if ( ! is_callable($method)) {
				continue;
			}

			$args = array_merge(
				array_get($config, 'args', []),
				[
					'nonce'     => wp_nonce_field('acm_metabox_' . $key, $nonce_key, null, false),
					'post_data' => session_pull($nonce_key),
				]
			);

			add_meta_box(
				$key,
				array_get($config, 'title'),
				$method,
				$this->name,
				array_get($config, 'context', 'member'),
				array_get($config, 'priority', 'low'),
				$args
			);
		}
	}


	public function processMetaboxes($post_id, $post) {
		global $post;

		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			return;
		}
		if (object_get($post, 'post_type') !== $this->name) {
			return;
		}
		if ( ! is_admin()) {
			return;
		}
		if (wp_is_post_autosave($post_id)) {
			return;
		}
		if (wp_is_post_revision($post_id)) {
			return;
		}

		foreach ($this->metaboxes as $key => $config) {
			$nonce_key    = 'acm_metabox_' . $key;
			$nonce_name   = post_get($nonce_key);
			$nonce_action = $nonce_key;

			if ( ! isset($nonce_name)) {
				return;
			}
			if ( ! wp_verify_nonce($nonce_name, $nonce_action)) {
				return;
			}

			$method = [$this, 'process' . studly_case($key) . 'Metabox'];
			if ( ! is_callable($method)) {
				continue;
			}
			if ( ! current_user_can('edit_post', $post_id)) {
				return;
			}

			remove_action('save_post', [$this, 'processMetaboxes'], 10, 2);
			call_user_func($method, $post_id, $post);
			add_action('save_post', [$this, 'processMetaboxes'], 10, 2);

			$_SESSION[$nonce_key] = $_POST;
		}
	}



// Loaders
// ==============================================================================================
	private function _filterQueryByPostFormat($query, $post)
	{
		if (get_post_format($post)) {
			$query['tax_query'][] = [
				'taxonomy' => 'post_format',
				'field'    => 'slug',
				'terms'    => [
					'post-format-'.get_post_format($post),
				]
			];
		}

		return $query;
	}


	private function _filterQueryByTaxonomy($query, $post, $taxonomy, $terms = [])
	{
		if ($post && empty($terms)) {
			$terms = get_the_terms($post, $taxonomy);
		}

		$term_list = [];

		if ($terms) {
			foreach ($terms as $term) {
				$term_list[] = $term->term_id;
			}
		}

		$query['tax_query'][] = [
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => $term_list
		];

		return $query;
	}


	public function _getAll(): array {
		$query = [
			'post_status'    => 'publish',
			'post_type'      => $this->name,
			'posts_per_page' => -1,
		];

		return get_posts($query);
	}


	public function _getRelated(
		\WP_Post $post,
		string $by_taxonomy = null,
		int $number = 3,
		bool $shuffle = false,
		bool $same_format = false
	): array {
		$query = [
			'post_status'    => 'publish',
			'post_type'      => $post->post_type,
			'posts_per_page' => $number,
			'post__not_in'   => [$post->ID],
			'tax_query'      => [],
		];


		if ($same_format) {
			$query = $this->_filterQueryByPostFormat($query, $post);
		}

		if ($by_taxonomy) {
			$query = $this->_filterQueryByTaxonomy($query, $post, $by_taxonomy);
		}


		$related = get_posts($query);


		// Fill the array if we don't have enough related
		$missing = $number - count($related);
		if ($missing > 0) {

			$related_ids = [];
			foreach ($related as $related_post) {
				$related_ids[] = $related_post->ID;
			}

			// Remove the Category from the query
			if (isset($post_format_tax_query)) {
				$query['tax_query'] = [$post_format_tax_query];
			} else {
				unset($query['tax_query']);
			}

			$query['posts_per_page'] = $missing;
			$query['post__not_in']   = array_merge([$post->ID], $related_ids);
			$extra = get_posts($query);

			$related = array_merge($related, $extra);
		}

		if ($shuffle) {
			shuffle($related);
		}

		return $related;
	}


	public function _getRecent(
		\WP_Post $post = null,
		string $by_taxonomy = null,
		int $number = 3,
		bool $same_format = false,
		array $in_cats = [],
		bool $exact_number = true
	): array {

		$exclude_post_ids = [];

		if ($post) {
			$exclude_post_ids = [$post->ID];
		}

		// Get sticky posts first
		$sticky_ids = get_option('sticky_posts');
		$sticky_ids = array_diff($sticky_ids, $exclude_post_ids);

		$query = [
			'post_status'    => 'publish',
			'post_type'      => $this->name,
			'posts_per_page' => $number,
			'post__in'       => $sticky_ids,
			'post__not_in'   => $exclude_post_ids,
			'tax_query'      => [],
			'orderby' => [
				'date' => 'DESC',
			]
		];

		if ($post && $same_format) {
			$query = $this->_filterQueryByPostFormat($query, $post);
		}
		if ($post || $by_taxonomy) {
			$query = $this->_filterQueryByTaxonomy($query, $post, $by_taxonomy, $in_cats);
		}


		$recent = get_posts($query);
		$number -= count($recent);

		if ($number === 0) {
			return $recent;
		}


		// Get non sticky posts next
		$exclude_post_ids = array_merge($sticky_ids, $exclude_post_ids);
		unset($query['post__in']);
		$query['post__not_in'] = $exclude_post_ids;
		$query['posts_per_page'] = $number;

		if ($post && $same_format) {
			$query = $this->_filterQueryByPostFormat($query, $post);
		}
		if ($post || $by_taxonomy) {
			$query = $this->_filterQueryByTaxonomy($query, $post, $by_taxonomy, $in_cats);
		}

		$recent = array_merge($recent, get_posts($query));


		// Fill the array if we don't have enough related
		$number -= count($recent);
		if ($exact_number && $number > 0) {

			$recent_ids = [];
			foreach ($recent as $recent_post) {
				$recent_ids[] = $recent_post->ID;
			}

			// Remove the Category from the query
			if (isset($post_format_tax_query)) {
				$query['tax_query'] = [$post_format_tax_query];
			} else {
				unset($query['tax_query']);
			}

			$exclude_post_ids = array_merge($recent_ids, $exclude_post_ids);
			$query['post__not_in']   = $exclude_post_ids;
			$query['posts_per_page'] = $number;
			$extra = get_posts($query);

			$recent = array_merge($recent, $extra);
		}


		return $recent;
	}


	public function _getByIds(array $ids, $post_type = null)
	{
		if (count($ids) === 0) {
			return [];
		}

		return get_posts([
			'post_type'		 => $post_type ?? $this->name,
			'post__in'       => $ids,
			'posts_per_page' => -1
		]);
	}


	public function _getSiblingPostIds(\WP_Post $post, int $number = -1): array
	{
		return get_posts([
			'post_type'			=> 'any',
			'post__not_in'		=> [$post->ID],
			'post_parent'		=> $post->post_parent,
			'post_type'		    => $post->post_type,
			'posts_per_page'	=> $number,
			'fields'			=> 'ids',
			'depth' 			=> 1,
		]);
	}


	public function _getChildPostIds(\WP_Post $post, int $number = -1): array
	{
		return get_posts([
			'post_type'			=> 'any',
			'post_parent'		=> $post->ID,
			'posts_per_page'	=> $number,
			'fields'			=> 'ids',
			'depth' 			=> 1,
		]);
	}


	public function _getPostIdsByType(string $post_type, int $number = -1): array
	{
		return get_posts([
			'post_type'			=> $post_type,
			'posts_per_page'	=> $number,
			'fields'			=> 'ids',
			'depth' 			=> 1,
		]);
	}



// Helpers
// ==============================================================================================
	public static function attachFields($posts, $fields = [])
	{
		$single = !is_array($posts);
		if ($single) {
			$posts = [$posts];
		}

		foreach ($posts as $key => $post) {
			if (!is_array($fields) || empty($fields)) {
				$values = get_fields($post);

				if ($values) {
					foreach ($values as $field => $value) {
						if (!$field) continue;
						$posts[$key]->{$field} = $value;
					}
				}
			} else {
				foreach ($fields as $field) {
					$posts[$key]->{$field} = get_field($field, $post);
				}
			}
		}

		if ($single) {
			$posts = $posts[0];
		}

		return $posts;
	}


	public static function attachImages($posts)
	{
		$single = !is_array($posts);
		if ($single) {
			$posts = [$posts];
		}

		foreach ($posts as $key => $post) {
			$posts[$key]->image = acm()->images->getThumbnailObject($post);
		}

		if ($single) {
			$posts = $posts[0];
		}

		return $posts;
	}


	public static function attachTaxonomies($posts, $taxonomies)
	{
		foreach ($taxonomies as $taxonomy) {
			$posts = self::attachTaxonomy($posts, $taxonomy);
		}

		return $posts;
	}


	public static function attachTaxonomy($posts, $taxonomy)
	{
		foreach ($posts as $key => $post) {
			$posts[$key]->{$taxonomy} = get_the_terms($post, $taxonomy);
		}

		return $posts;
	}


}
