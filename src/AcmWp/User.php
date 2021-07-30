<?php
namespace AcmWp;


class User
{
	protected $role;
	protected $label;
	protected $capabilities;
	protected $fields = [];

	protected $core_roles = [
		'administrator',
		'editor',
		'author',
		'contributor',
		'subscriber',
	];


	public function register()
	{
		add_action('init', [$this, 'addCustomRole'], 10);
		add_action('init', [$this, 'registerFields'], 10);
	}


	public function isCoreRole()
	{
		return in_array($this->role, $this->core_roles);
	}


	public function addCustomRole(): void
	{
		if ($this->isCoreRole()) return;

		wp_roles()->add_role($this->role, $this->label, $this->capabilities);
	}


	public function registerFields() {
		if (!function_exists("register_field_group")) return;

		$defaults = [
			'id' 		=> 'role-'.$this->role,
			'title' 	=> 'Options',
			'fields' 	=> [],
			'location' 	=> [[
				[
					'param' 	=> 'user_role',
					'operator' 	=> '==',
					'value' 	=> $this->role,
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
			$config['fields'] = Fields::prefixFieldsKey($config['fields'], 'role-'.$this->role);

			register_field_group($config);
		}
	}
}