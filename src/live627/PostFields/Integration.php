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

class Integration
{
    public static function admin_areas(&$admin_areas)
    {
        $ohara = new \Suki\Ohara();
        $ohara->name = 'PostFields';
        $admin_areas['layout']['areas']['postfields'] = array(
            'label' => $ohara->text('title'),
            'icon' => 'settings.gif',
            'function' => function() {
                \live627\PostFields\Dispatcher::getInstance();
            },
            'subsections' => array(
                'index' => array($ohara->text('menu_index')),
                'edit' => array($ohara->text('menu_edit')),
            ),
        );
    }

    public static function load_theme()
    {
        global $sourcedir;
        if (!class_exists('ModHelper\Psr4AutoloaderClass')) {
            require_once($sourcedir . '/PostFields/ModHelper/Psr4AutoloaderClass.php');
        }
        // instantiate the loader
        $loader = new \ModHelper\Psr4AutoloaderClass;
        // register the autoloader
        $loader->register();
        // register the base directories for the namespace prefix
        $loader->addNamespace('ModHelper', $sourcedir . '/PostFields/ModHelper');
        $loader->addNamespace('live627', $sourcedir . '/PostFields/live627');
        $loader->addNamespace('Suki', $sourcedir . '/PostFields/Suki');
    }

    public static function post_form()
    {
        global $board, $context, $options, $user_info;

        $util = new Util();
        $context['fields'] = $util->load_fields($util->filterFields($board));
        loadLanguage('PostFields');
        loadTemplate('PostFields');
        $context['is_post_fields_collapsed'] = $user_info['is_guest'] ? !empty($_COOKIE['postFields']) : !empty($options['postFields']);
    }

    public static function after($msgOptions, $topicOptions)
    {
        global $board, $modSettings, $topic, $user_info;

        $field_list = (new Util)->filterFields($board);
        $changes = $log_changes = $values = array();
        $value = '';
        $_POST['icon'] = 'xx';

        if (isset($_REQUEST['msg'])) {
            $values = $this->getFieldValues($_REQUEST['msg'], array_keys($field_list));
        }

        if (isset($topic)) {
            $request = Database::query('', '
                SELECT id_first_msg
                FROM {db_prefix}topics
                WHERE id_topic = {int:current_topic}',
                array(
                    'current_topic' => $topic,
                )
            );
            list ($topic_value) = Database::fetch_row($request);
            $topic_value = $topic_value != $_REQUEST['msg'];
            Database::free_result($request);
        }
        foreach ($field_list as $field) {
            if ((empty($topic) || empty($topic_value)) && $field['topic_only'] == 'yes') {
                $value = isset($_POST['postfield'][$field['id_field']]) ? $_POST['postfield'][$field['id_field']] : '';
            }
            $class_name = 'postFields_' . $field['type'];

            if (!class_exists($class_name) || (isset($values[$field['id_field']]) && $values[$field['id_field']] == $value)) {
                continue;
            }

            $type = new $class_name($field, $value, !empty($value));
            $changes[] = array($field['id_field'], $type->getValue(), $msgOptions['id']);

            // Rather than calling logAction(), we build our own array to log everything in one go.
            $log_changes[] = array(
                'action' => 'message_field_' . $field['id_field'],
                'id_log' => 2,
                'log_time' => time(),
                'id_member' => $user_info['id'],
                'ip' => $user_info['ip'],
                'extra' => serialize(array('old' => isset($values[$field['id_field']]) ? $values[$field['id_field']] : '', 'new' => $value, 'name' => $field['name'])),
                'id_msg' => $msgOptions['id'],
                'id_topic' => $topicOptions['id'],
                'id_board' => $topicOptions['board'],
            );
        }

        if (!empty($changes)) {
            Database::insert('replace',
                '{db_prefix}message_field_data',
                array('id_field' => 'int', 'value' => 'string', 'id_msg' => 'int'),
                $changes,
                array('id_field', 'id_msg')
            );

            if (!empty($log_changes) && !empty($modSettings['modlog_enabled'])) {
                Database::insert('',
                    '{db_prefix}log_actions',
                    array(
                        'action' => 'string', 'id_log' => 'int', 'log_time' => 'int', 'id_member' => 'int', 'ip' => 'string-16',
                        'extra' => 'string-65534',
                    ),
                    $log_changes,
                    array('id_action')
                );
            }
        }
    }

    public static function post_post_validate(&$post_errors, $posterIsGuest)
    {
        global $board, $smcFunc, $topic;

        foreach ($post_errors as $id => $post_error) {
            if ($post_error == 'no_message') {
                unset($post_errors[$id]);
            }
        }

        if (isset($_POST['postfield'])) {
            $_POST['postfield'] = htmlspecialchars__recursive($_POST['postfield']);
        }

        $field_list = (new Util)->filterFields($board);
        require_once(__DIR__ . '/Class-PostFields.php');
        loadLanguage('PostFields');

        if (isset($topic)) {
            $request = Database::query('', '
                SELECT id_first_msg
                FROM {db_prefix}topics
                WHERE id_topic = {int:current_topic}',
                array(
                    'current_topic' => $topic,
                )
            );
            list ($topic_value) = Database::fetch_row($request);
            $topic_value = $topic_value != $_REQUEST['msg'];
            Database::free_result($request);
        }
        foreach ($field_list as $field) {
            if ((empty($topic) || empty($topic_value)) && $field['topic_only'] == 'yes') {
                $value = isset($_POST['postfield'][$field['id_field']]) ? $_POST['postfield'][$field['id_field']] : '';
            }
            $class_name = '\\live627\\PostFields\\postFields_' . $field['type'];
            if (!class_exists($class_name)) {
                fatal_error('Param "' . $field['type'] . '" not found for field "' . $field['name'] . '" at ID #' . $field['id_field'] . '.', false);
            }

            $type = new $class_name($field, $value, !empty($value));
            $type->validate();
            if (false !== ($err = $type->getError())) {
                $post_errors[] = $err;
            }
        }
    }

    public static function remove_message($message, $decreasePostCount)
    {
        self::remove_messages((array) $message, $decreasePostCount);
    }

    public static function remove_messages($messages, $decreasePostCount)
    {
        global $smcFunc;

        if (!empty($messages)) {
            Database::query('', '
                DELETE FROM {db_prefix}message_field_data
                WHERE id_msg IN ({array_int:message_list})',
                array(
                    'message_list' => $messages,
                )
            );
        }
    }

    public static function remove_topics($topics, $decreasePostCount, $ignoreRecycling)
    {
        global $smcFunc;

        $messages = array();
        $request = Database::query('', '
            SELECT id_msg
            FROM {db_prefix}messages
            WHERE id_topic IN ({array_int:topics})',
            array(
                'topics' => $topics,
            )
        );
        while ($row = Database::fetch_assoc($request)) {
            $messages[] = $row['id_msg'];
        }

        Database::free_result($request);

        if (!empty($messages)) {
            self::remove_messages($messages, $decreasePostCount);
        }
    }

    public static function display_topics($topic_ids)
    {
        global $smcFunc;

        if (empty($topic_ids)) {
            return;
        }

        $messages = array();
        $request = Database::query('', '
            SELECT id_first_msg
            FROM {db_prefix}topics
            WHERE id_topic IN ({array_int:topics})',
            array(
                'topics' => $topic_ids,
            )
        );
        while ($row = Database::fetch_row($request)) {
            $messages[] = $row[0];
        }

        Database::free_result($request);

        if (!empty($messages)) {
            self::display_message_list($messages, true);
        }
    }

    public static function display_message_list($messages, $is_message_index = false)
    {
        global $board, $context, $smcFunc;

        $util = new Util();
        $field_list = $util->filterFields($board, $is_message_index);

        if (empty($field_list)) {
            return;
        }

        $request = Database::query('', '
            SELECT *
            FROM {db_prefix}message_field_data
            WHERE id_msg IN ({array_int:message_list})
                AND id_field IN ({array_int:field_list})',
            array(
                'message_list' => $messages,
                'field_list' => $util->keys($field_list),
            )
        );
        $context['fields'] = array();
        while ($row = Database::fetch_assoc($request)) {
            $exists = isset($row['value']);
            $value = $exists ? $row['value'] : '';

            $context['fields'][$row['id_msg']][$row['id_field']] = $util->renderField($field_list[$row['id_field']], $value, $exists);
        }
        Database::free_result($request);

        if (!empty($context['fields'])) {
            loadLanguage('PostFields');
            loadTemplate('PostFields');
        }
    }

    public static function display_post_done($counter, &$output)
    {
        global $context;
        $field_order = array(1, 2, 4, 5, 6, 10);

        if (!empty($context['fields'][$output['id']])) {
            $body = '
                            <br />
                            <dl class="settings">';

            foreach ($field_order as $fo) {
                $field = $context['fields'][$output['id']][$fo];

                if ($field['id_field'] == 4) {
                    $field = $context['fields'][$output['id']][3];
                    if ($field['output_html'] == 'Fixed') {
                        $field['output_html'] = '£ ' . $context['fields'][$output['id']][4]['output_html'];
                    }
                }

                if ($field['id_field'] == 5) {
                    $field['output_html'] .= ' ' . $context['fields'][$output['id']][7]['output_html'];
                }

                if ($field['id_field'] == 6) {
                    $field['output_html'] .= ' ' . $context['fields'][$output['id']][8]['output_html'];
                }

                if ($field['id_field'] == 5 || $field['id_field'] == 6 || $field['id_field'] == 10) {
                    $body .= '
                            </dl>
                            <hr />
                            <dl class="settings" style="margin-top: 10px;">';
                }

                $body .= '
                                <dt>
                                    <strong>' . $field['name'] . ': </strong><br />
                                </dt>
                                <dd>
                                    ' . $field['output_html'] . '
                                </dd>';
            }

            $output['body'] = $body . '
                            </dl>
                            <hr />
                            <br />' . $output['body'];
        }
    }
}
