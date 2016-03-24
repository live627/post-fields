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

class Admin extends \Suki\Ohara
{
    public $name = 'PostFields';
    private $util;

    public function __construct()
    {
        $this->util = new Util();
        $this->setRegistry();
    }

    public function Index()
    {
        global $context, $smcFunc;

        // Deleting?
        if (isset($_POST['delete'], $_POST['remove'])) {
            checkSession();
            $this->deleteFields($_POST['remove']);
            redirectexit('action=admin;area=postfields');
        }

        // Changing the status?
        if (isset($_POST['save'])) {
            checkSession();
            foreach ($this->util->getFields() as $field) {
                $bbc = !empty($_POST['bbc'][$field['id_field']]) ? 'yes' : 'no';
                if ($bbc != $field['bbc']) {
                    Database::query('', '
                        UPDATE {db_prefix}message_fields
                        SET bbc = {string:bbc}
                        WHERE id_field = {int:field}',
                        array(
                            'bbc' => $bbc,
                            'field' => $field['id_field'],
                        )
                    );
                }

                $active = !empty($_POST['active'][$field['id_field']]) ? 'yes' : 'no';
                if ($active != $field['active']) {
                    Database::query('', '
                        UPDATE {db_prefix}message_fields
                        SET active = {string:active}
                        WHERE id_field = {int:field}',
                        array(
                            'active' => $active,
                            'field' => $field['id_field'],
                        )
                    );
                }

                $can_search = !empty($_POST['can_search'][$field['id_field']]) ? 'yes' : 'no';
                if ($can_search != $field['can_search']) {
                    Database::query('', '
                        UPDATE {db_prefix}message_fields
                        SET can_search = {string:can_search}
                        WHERE id_field = {int:field}',
                        array(
                            'can_search' => $can_search,
                            'field' => $field['id_field'],
                        )
                    );
                }
                call_integration_hook('integrate_update_post_field', array($field));
            }
            redirectexit('action=admin;area=postfields');
        }

        // New field?
        if (isset($_POST['new'])) {
            redirectexit('action=admin;area=postfields;sa=edit');
        }

        $listOptions = array(
            'id' => 'pf_fields',
            'base_href' => $this->scriptUrl . '?action=admin;area=postfields',
            'default_sort_col' => 'name',
            'no_items_label' => $this->text('none'),
            'items_per_page' => 25,
            'get_items' => array(
                'function' => ['live627\PostFields\Admin', 'process'],
                'params' => array(
                    $this->util->getFields(),
                    'pf_fields',
                ),
            ),
            'get_count' => array(
                'function' => function() {
                    return count($this->util->getFields());
                }
            ),
            'columns' => array(
                'name' => array(
                    'header' => array(
                        'value' => $this->text('fieldname'),
                        'style' => 'text-align: left;',
                    ),
                    'data' => array(
                        'function' => function($rowData) {
                            return sprintf('<a href="%1$s?action=admin;area=postfields;sa=edit;fid=%2$d">%3$s</a><div class="smalltext">%4$s</div>', $this->scriptUrl, $rowData['id_field'], $rowData['name'], $rowData['description']);
                        },
                        'style' => 'width: 40%;',
                    ),
                    'sort' => array(
                        'default' => 'name',
                        'reverse' => 'name DESC',
                    ),
                ),
                'type' => array(
                    'header' => array(
                        'value' => $this->text('fieldtype'),
                    ),
                    'data' => array(
                        'function' => function($rowData) {
                            $textKey = sprintf('type_%1$s', $rowData['type']);
                            return $this->text($textKey);
                        },
                        'style' => 'width: 10%; text-align: center;',
                    ),
                    'sort' => array(
                        'default' => 'type',
                        'reverse' => 'type DESC',
                    ),
                ),
                'bbc' => array(
                    'header' => array(
                        'value' => $this->text('bbc'),
                    ),
                    'data' => array(
                        'function' => function($rowData) {
                            $isChecked = $rowData['bbc'] == 'no' ? '' : ' checked';
                            return sprintf('<span id="bbc_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="bbc[%1$s]" id="bbc_%1$s" value="%1$s"%2$s>', $rowData['id_field'], $isChecked, $this->text($rowData['bbc']), $rowData['bbc']);
                        },
                        'style' => 'width: 10%; text-align: center;',
                    ),
                    'sort' => array(
                        'default' => 'bbc DESC',
                        'reverse' => 'bbc',
                    ),
                ),
                'active' => array(
                    'header' => array(
                        'value' => $this->text('active'),
                    ),
                    'data' => array(
                        'function' => function($rowData) {
                            $isChecked = $rowData['active'] == 'no' ? '' : ' checked';
                            return sprintf('<span id="active_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="active[%1$s]" id="active_%1$s" value="%1$s"%2$s>', $rowData['id_field'], $isChecked, $this->text($rowData['active']), $rowData['active']);
                        },
                        'style' => 'width: 10%; text-align: center;',
                    ),
                    'sort' => array(
                        'default' => 'active DESC',
                        'reverse' => 'active',
                    ),
                ),
                'can_search' => array(
                    'header' => array(
                        'value' => $this->text('can_search'),
                    ),
                    'data' => array(
                        'function' => function($rowData) {
                            $isChecked = $rowData['can_search'] == 'no' ? '' : ' checked';
                            return sprintf('<span id="can_search_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="can_search[%1$s]" id="can_search_%1$s" value="%1$s"%2$s>', $rowData['id_field'], $isChecked, $this->text($rowData['can_search']), $rowData['can_search']);
                        },
                        'style' => 'width: 10%; text-align: center;',
                    ),
                    'sort' => array(
                        'default' => 'can_search DESC',
                        'reverse' => 'can_search',
                    ),
                ),
                'modify' => array(
                    'header' => array(
                        'value' => $this->text('modify'),
                    ),
                    'data' => array(
                        'sprintf' => array(
                            'format' => '<a href="' . $this->scriptUrl . '?action=admin;area=postfields;sa=edit;fid=%1$s">' . $this->text('modify') . '</a>',
                            'params' => array(
                                'id_field' => false,
                            ),
                        ),
                        'style' => 'width: 10%; text-align: center;',
                    ),
                ),
                'remove' => array(
                    'header' => array(
                        'value' => $this->text('remove'),
                    ),
                    'data' => array(
                        'function' => function($rowData) {
                            return sprintf('<span id="remove_%1$s" class="color_no">%2$s</span>&nbsp;<input type="checkbox" name="remove[%1$s]" id="remove_%1$s" value="%1$s">', $rowData['id_field'], $this->text('no'));
                        },
                        'style' => 'width: 10%; text-align: center;',
                    ),
                    'sort' => array(
                        'default' => 'remove DESC',
                        'reverse' => 'remove',
                    ),
                ),
            ),
            'form' => array(
                'href' => $this->scriptUrl . '?action=admin;area=postfields',
                'name' => 'postProfileFields',
            ),
            'additional_rows' => array(
                array(
                    'position' => 'below_table_data',
                    'value' => '<input type="submit" name="save" value="' . $this->text('save') . '" class="submit">&nbsp;&nbsp;<input type="submit" name="delete" value="' . $this->text('delete') . '" onclick="return confirm(' . JavaScriptEscape($this->text('delete_sure')) . ');" class="delete">&nbsp;&nbsp;<input type="submit" name="new" value="' . $this->text('make_new') . '" class="new">',
                    'style' => 'text-align: right;',
                ),
            ),
        );
        require_once($this->sourceDir . '/Subs-List.php');
        call_integration_hook('integrate_list_post_fields', array(&$listOptions));
        createList($listOptions);
        $context['sub_template'] = 'show_list';
        $context['default_list'] = 'pf_fields';
    }

    public function Edit()
    {
        global $context;

        $context['fid'] = isset($_REQUEST['fid']) ? (int) $_REQUEST['fid'] : 0;
        $context['page_title'] = $this->text('title') . ' - ' . ($context['fid'] ? $this->text('edit') : $this->text('add'));
        $context['html_headers'] .= '<script type="text/javascript" src="' . $this->settings['default_theme_url'] . '/scripts/postfieldsadmin.js"></script>';
        loadTemplate('PostFields');
        require_once($this->sourceDir . '/Subs-MessageIndex.php');
        $context['board_list'] = getBoardList();
        loadLanguage('Profile');

        if ($context['fid']) {
            $request = Database::query('', '
                SELECT *
                FROM {db_prefix}message_fields
                WHERE id_field = {int:current_field}',
                array(
                    'current_field' => $context['fid'],
                )
            );
            $context['field'] = array();
            while ($row = Database::fetch_assoc($request)) {
                if ($row['type'] == 'textarea') {
                    list ($rows, $cols) = explode(',', $row['default_value']);
                } else {
                    $rows = 3;
                    $cols = 30;
                }

                $context['field'] = array(
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'enclose' => $row['enclose'],
                    'type' => $row['type'],
                    'length' => $row['size'],
                    'rows' => $rows,
                    'cols' => $cols,
                    'bbc' => $row['bbc'] == 'yes',
                    'default_check' => $row['type'] == 'check' && $row['default_value'] ? true : false,
                    'default_select' => $row['type'] == 'select' || $row['type'] == 'radio' ? $row['default_value'] : '',
                    'options' => strlen($row['options']) > 1 ? explode(',', $row['options']) : array('', '', ''),
                    'active' => $row['active'] == 'yes',
                    'can_search' => $row['can_search'] == 'yes',
                    'mask' => $row['mask'],
                    'regex' => $row['regex'],
                    'boards' => !empty($row['boards']) ? explode(',', $row['boards']) : array(),
                    'groups' => !empty($row['groups']) ? explode(',', $row['groups']) : array(),
                );
            }
            Database::free_result($request);
        }

        // Setup the default values as needed.
        if (empty($context['field'])) {
            $context['field'] = array(
                'name' => '',
                'description' => '',
                'enclose' => '',
                'type' => 'text',
                'length' => 255,
                'rows' => 4,
                'cols' => 30,
                'bbc' => false,
                'default_check' => false,
                'default_select' => '',
                'options' => array('', '', ''),
                'active' => true,
                'can_search' => false,
                'mask' => '',
                'regex' => '',
                'boards' => array(),
                'groups' => [-3],
            );
        }

        $context['groups'] = $this->util->list_groups($context['field']['groups']);
        $context['all_groups_checked'] = empty(array_diff_key($context['groups'], array_filter($context['groups'], function($group) {
            return $group['checked'];
        })));
        $context['all_boards_checked'] = false;
        foreach ($context['board_list'] as $category)
            foreach ($category['boards'] as $board)
                $context['all_boards_checked'] = true *in_array($board['id'], $context['field']['boards']);

        // Are we saving?
        if (isset($_POST['save'])) {
            checkSession();

            if (trim($_POST['name']) == '') {
                fatal_lang_error('post_option_need_name');
            }
            $this->insertField($context['fid']);
            redirectexit('action=admin;area=postfields');
        } elseif (isset($_POST['delete']) && $context['field']['colname']) {
            checkSession();
            $this->deleteFields($context['fid']);
            redirectexit('action=admin;area=postfields');
        }
    }

    public function insertField($field)
    {
        global $smcFunc;

        $_POST['name'] = $smcFunc['htmlspecialchars']($_POST['name']);
        $_POST['description'] = $smcFunc['htmlspecialchars']($_POST['description']);

        $bbc = !empty($_POST['bbc']) ? 'yes' : 'no';
        $active = !empty($_POST['active']) ? 'yes' : 'no';
        $can_search = !empty($_POST['can_search']) ? 'yes' : 'no';

        $mask = isset($_POST['mask']) ? $_POST['mask'] : '';
        $regex = isset($_POST['regex']) ? $_POST['regex'] : '';
        $length = isset($_POST['lengt']) ? (int)$_POST['lengt'] : 255;
        $groups = !empty($_POST['groups']) ? implode(',', array_keys($_POST['groups'])) : '';
        $boards = !empty($_POST['boards']) ? implode(',', array_keys($_POST['boards'])) : '';

        $options = '';
        $newOptions = array();
        $default = isset($_POST['default_check']) && $_POST['type'] == 'check' ? 1 : '';
        if (!empty($_POST['select_option']) && ($_POST['type'] == 'select' || $_POST['type'] == 'radio')) {
            foreach ($_POST['select_option'] as $k => $v) {
                $v = $smcFunc['htmlspecialchars']($v);
                $v = strtr($v, array(',' => ''));

                if (trim($v) == '') {
                    continue;
                }

                $newOptions[$k] = $v;

                if (isset($_POST['default_select']) && $_POST['default_select'] == $k) {
                    $default = $v;
                }
            }
            $options = implode(',', $newOptions);
        }

        if ($_POST['type'] == 'textarea') {
            $default = (int)$_POST['rows'] . ',' . (int)$_POST['cols'];
        }

        $up_col = array(
            'name = {string:name}', ' description = {string:description}', ' enclose = {string:enclose}',
            '`type` = {string:type}', ' size = {int:length}',
            'options = {string:options}',
            'active = {string:active}', ' default_value = {string:default_value}',
            'can_search = {string:can_search}', ' bbc = {string:bbc}', ' mask = {string:mask}', ' regex = {string:regex}',
            'groups = {string:groups}', ' boards = {string:boards}',
        );
        $up_data = array(
            'length' => $length,
            'active' => $active,
            'can_search' => $can_search,
            'bbc' => $bbc,
            'current_field' => $field,
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'enclose' => $_POST['enclose'],
            'type' => $_POST['type'],
            'options' => $options,
            'default_value' => $default,
            'mask' => $mask,
            'regex' => $regex,
            'groups' => $groups,
            'boards' => $boards,
        );
        $in_col = array(
            'name' => 'string', 'description' => 'string', 'enclose' => 'string',
            'type' => 'string', 'size' => 'string', 'options' => 'string', 'active' => 'string', 'default_value' => 'string',
            'can_search' => 'string', 'bbc' => 'string', 'mask' => 'string', 'regex' => 'string', 'groups' => 'string', 'boards' => 'string',
        );
        $in_data = array(
            $_POST['name'], $_POST['description'], $_POST['enclose'],
            $_POST['type'], $length, $options, $active, $default,
            $can_search, $bbc, $mask, $regex, $groups, $boards,
        );
        call_integration_hook('integrate_save_post_field', array(&$up_col, &$up_data, &$in_col, &$in_data));

        if ($field) {
            Database::query('', '
                UPDATE {db_prefix}message_fields
                SET
                    ' . implode(',
                    ', $up_col) . '
                WHERE id_field = {int:current_field}',
                $up_data
            );
        } else {
            Database::insert('',
                '{db_prefix}message_fields',
                $in_col,
                $in_data,
                array('id_field')
            );
        }
    }

    public function deleteFields(array $fields)
    {
        call_integration_hook('integrate_delete_post_fields', array($_POST['remove']));

        // Delete the user data first.
        Database::query('', '
            DELETE FROM {db_prefix}message_data
            WHERE id_field IN ({array_int:fields})',
            array(
                'fields' => $fields,
            )
        );
        // Finally - the fields themselves are gone!
        Database::query('', '
            DELETE FROM {db_prefix}message_fields
            WHERE id_field IN ({array_int:fields})',
            array(
                'fields' => $fields,
            )
        );
    }

    public function process($start, $length, $sort, $list, $listId)
    {
        global $context;

        $sort = [];
        $list_context = $context[$listId];
        foreach ($list as $key => $row) {
            $sort[$key] = $row[$list_context['sort']['id']];
        }
        array_multisort($sort, $list_context['sort']['desc'] ? SORT_DESC : SORT_ASC, $list);

        if ($length) {
            $list = array_slice($list, $start, $length);
        }

        return $list;
    }
}
