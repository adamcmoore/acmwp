<?php
namespace AcmWp\Theme;

if (! defined('ABSPATH')) exit;

Class Customizer
{

	public static function register($wp_customize, array $options)
	{
		foreach ($options as $group_name => $group) {
			$wp_customize->add_section($group_name,
				[
					'title'       => $group['title'],
					'priority'    => $group['priority'],
					'description' => array_get($group, 'description', ''),
					'capability'  => array_get($group, 'capability', 'edit_theme_options'),
				]
			);

			$priority = 0;
			foreach ($group['settings'] as $setting_name => $setting) {
				$priority += 10;

				$wp_customize->add_setting($setting_name,
					[
						'priority'   => $priority,
						'default'    => array_get($setting, 'default', ''),
						'type'       => array_get($setting, 'type', 'option'),
						'capability' => array_get($setting, 'capability', 'edit_theme_options'),
						'transport'  => array_get($setting, 'transport', 'postMessage'),
					]
				);

				$control_settings = [
					'section'  => $group_name,
					'settings' => $setting_name,
					'label'    => $setting['label'],
					'type'     => $setting['control_type'],
					'priority' => 10,
				];

				switch ($setting['control_type']) {
					default:
						$wp_customize->add_control(new \WP_Customize_Control(
							$wp_customize, $setting_name, $control_settings
						));
						break;
				}
			}
		}
	}
}
