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

	/**
	 * Gets all membergroups and filters them according to the parameters.
	 *
	 * @param array $checked list of all id_groups to be checked (have a mark in the checkbox).
	 * @param array $disallowed list of all id_groups that are skipped. Default is an empty array.
	 * @param bool $inherited whether or not to filter out the inherited groups. Default is false.
	 * @return array all the membergroups filtered according to the parameters; empty array if something went wrong.
	 * @since 1.0
	 */
	function list_groups(array $checked, array $disallowed = [], $inherited = false, $permission = null, $board_id = null)
	{
		global $context, $modSettings, $smcFunc, $sourcedir, $txt;

		// We'll need this for loading up the names of each group.
		if (!loadLanguage('ManageBoards'))
			loadLanguage('ManageBoards');

		// Are we also looking up permissions?
		if ($permission !== null)
		{
			require_once($sourcedir . '/Subs-Members.php');
			$member_groups = groupsAllowedTo($permission, $board_id);
			$disallowed = array_diff(array_keys(list_groups(-3)), $member_groups['allowed']);
		}

		$groups = array();
		if (!in_array(-1, $disallowed))
			// Guests
			$groups[-1] = array(
				'id' => -1,
				'name' => $txt['parent_guests_only'],
				'checked' => in_array(-1, $checked) || in_array(-3, $checked),
				'is_post_group' => false,
				'color' => '',
			);

		if (!in_array(0, $disallowed))
			// Regular Members
			$groups[0] = array(
				'id' => 0,
				'name' => $txt['parent_members_only'],
				'checked' => in_array(0, $checked) || in_array(-3, $checked),
				'is_post_group' => false,
				'color' => '',
			);

		// Load membergroups.
		$request = $smcFunc['db_query']('', '
			SELECT group_name, id_group, min_posts, online_color
			FROM {db_prefix}membergroups
			WHERE id_group > {int:is_zero}' . (!$inherited ? '
				AND id_parent = {int:not_inherited}' : '') . (!$inherited && empty($modSettings['permission_enable_postgroups']) ? '
				AND min_posts = {int:min_posts}' : ''),
			array(
				'is_zero' => 0,
				'not_inherited' => -2,
				'min_posts' => -1,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($request))
			if (!in_array($row['id_group'], $disallowed))
				$groups[(int) $row['id_group']] = array(
					'id' => $row['id_group'],
					'name' => trim($row['group_name']),
					'checked' => in_array($row['id_group'], $checked) || in_array(-3, $checked),
					'is_post_group' => $row['min_posts'] != -1,
					'color' => $row['online_color'],
				);
		$smcFunc['db_free_result']($request);

		asort($groups);

		return $groups;
	}
}
