<?php
namespace AcmWp;


Class Fields
{
	public static function prefixFieldsKey($fields, $namespace) {
		foreach ($fields as $key => $field) {
			if (array_key_exists('sub_fields', $field)) {
				$fields[$key]['sub_fields'] = self::prefixFieldsKey($field['sub_fields'], $namespace);
			}
			if (!array_key_exists('name', $field)) {
				$fields[$key]['name'] = $key;
			}
			if (!array_key_exists('key', $field)) {
				$fields[$key]['key'] = $namespace . '_' . $key;
			}
		}

		return $fields;
	}
}