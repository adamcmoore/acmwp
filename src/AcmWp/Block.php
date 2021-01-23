<?php
namespace AcmWp;


Class Block
{
	protected $name;
	protected $title;
	protected $description;
	protected $category;
	protected $icon;
	protected $keywords;
	protected $supports;
	protected $template;
	protected $fields;

	public function register()
	{
		if (!function_exists('acf_register_block_type')) return;
		if (!function_exists('acf_add_local_field_group')) return;


		add_action('acf/init', function() {
			$args = [
				'name'              => $this->name,
				'title'             => $this->title,
				'description'       => $this->description,
				'category'          => $this->category,
				'icon'              => $this->icon,
				'keywords'          => $this->keywords,
				'supports'          => $this->supports,
			];
			if ($this->template) {
				$args['render_template'] = $this->template;
			} else {
				$args['render_callback'] = [$this, 'render'];
			}

			acf_register_block_type($args);

			$fields = Fields::prefixFieldsKey($this->fields, 'acm_block_'.$this->name);

			acf_add_local_field_group([
				'key'      => 'block_'.$this->name,
				'title'    => $this->title,
				'fields'   => $fields,
				'location' => [[[
					'param'    => 'block',
					'operator' => '==',
					'value'    => 'acf/'.$this->name,
				]]],
			]);
		});
	}


	public function getTemplatePath()
	{
		return get_theme_file_path('/blocks/'.$this->name.'.php');
	}


	public function render(array $block)
	{
		include($this->getTemplatePath());
	}


	protected function setupPostFromAcf()
	{
		$block['data']['post_id'] = acf_maybe_get_POST('post_id');
		if (!$GLOBALS['post'] && $block['data']['post_id']) {
			$GLOBALS['post'] = get_post($block['data']['post_id']);
			setup_postdata($GLOBALS['post']);
		}
	}
}