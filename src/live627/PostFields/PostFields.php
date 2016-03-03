<?php

/**
 * @package PostFields
 * @version 2.0
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2011-2016, John Rayes
 * @license http://opensource.org/licenses/ISC ISC
 */

namespace live627\PostFields;
use \ModHelper\Database;

if (!defined('SMF')) {
	die('Hacking attempt...');
}

class PostFields extends \Suki\Ohara
{
	public $name = __CLASS__;
	protected static $_activity = array();

	public function __construct()
	{
		$this->setRegistry();
	}

	function total_getPostFields()
	{
		global $smcFunc;

		$list = array();
		$request = $smcFunc['db_query']('', '
			SELECT *
			FROM {db_prefix}message_fields');
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$list[$row['id_field']] = $row;
		$smcFunc['db_free_result']($request);
		call_integration_hook('integrate_get_post_fields', array(&$list));
		return $list;
	}

	function total_getPostFieldsSearchable()
	{
		global $smcFunc;

		$list = array();
		$request = $smcFunc['db_query']('', '
			SELECT *
			FROM {db_prefix}message_fields
			WHERE can_search = \'yes\'');
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$list[$row['id_field']] = $row;
		$smcFunc['db_free_result']($request);
		call_integration_hook('integrate_get_post_fields_searchable', array(&$list));
		return $list;
	}

	function get_post_fields_filtered($board, $is_message_index = false)
	{
		global $context, $user_info;

		$fields = total_getPostFields();
		$list = array();
		foreach ($fields as $field)
		{
			$board_list = array_flip(explode(',', $field['boards']));
			if (!isset($board_list[$board]))
				continue;

			$group_list = explode(',', $field['groups']);
			$is_allowed = array_intersect($user_info['groups'], $group_list);
			if (empty($is_allowed))
				continue;

			$list[$field['id_field']] = $field;
		}
		call_integration_hook('integrate_get_post_fields_filtered', array(&$list, $board));
		return $list;
	}

	function rennder_field($field, $value, $exists)
	{
		global $scripturl, $settings, $sourcedir;

		require_once($sourcedir . '/Class-PostFields.php');
		$class_name = 'postFields_' . $field['type'];
		if (!class_exists($class_name))
			fatal_error('Param "' . $field['type'] . '" not found for field "' . $field['name'] . '" at ID #' . $field['id_field'] . '.', false);

		$param = new $class_name($field, $value, $exists);
		$param->setHtml();
		// Parse BBCode
		if ($field['bbc'] == 'yes')
			$param->output_html = parse_bbc($param->output_html);
		// Allow for newlines at least
		elseif ($field['type'] == 'textarea')
			$param->output_html = strtr($param->output_html, array("\n" => '<br>'));

		// Enclosing the user input within some other text?
		if (!empty($field['enclose']) && !empty($output_html))
		{
			$replacements = array(
				'{SCRIPTURL}' => $scripturl,
				'{IMAGES_URL}' => $settings['images_url'],
				'{DEFAULT_IMAGES_URL}' => $settings['default_images_url'],
				'{INPUT}' => $param->output_html,
			);
			call_integration_hook('integrate_enclose_post_field', array($field['id_field'], &$field['enclose'], &$replacements));
			$param->output_html = strtr($field['enclose'], $replacements);
		}

		return array(
			'name' => $field['name'],
			'description' => $field['description'],
			'type' => $field['type'],
			'input_html' => $param->input_html,
			'output_html' => $param->getOutputHtml(),
			'id_field' => $field['id_field'],
			'value' => $value,
		);
	}
}
	}
