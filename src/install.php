<?php

/**
 * @package PostFields
 * @version 2.0
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2011-2016, John Rayes
 * @license http://opensource.org/licenses/ISC ISC
 */

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF')) {
    $ssi = true;
    require_once(dirname(__FILE__) . '/SSI.php');
} elseif (!defined('SMF')) {
    exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
}

if (!array_key_exists('db_add_column', $smcFunc)) {
    db_extend('packages');
}

$columns = array(
    array(
        'name' => 'id_field',
        'type' => 'mediumint',
        'size' => 8,
        'unsigned' => true,
        'auto' => true,
    ),
    array(
        'name' => 'name',
        'type' => 'varchar',
        'size' => 80,
    ),
    array(
        'name' => 'type',
        'type' => 'varchar',
        'size' => 20,
    ),
    array(
        'name' => 'description',
        'type' => 'varchar',
        'size' => 4096,
    ),
    array(
        'name' => 'enclose',
        'type' => 'varchar',
        'size' => 4096,
    ),
    array(
        'name' => 'options',
        'type' => 'varchar',
        'size' => 4096,
    ),
    array(
        'name' => 'size',
        'type' => 'smallint',
        'size' => 5,
        'unsigned' => true,
    ),
    array(
        'name' => 'default_value',
        'type' => 'varchar',
        'size' => 80,
    ),
    array(
        'name' => 'mask',
        'type' => 'varchar',
        'size' => 20,
    ),
    array(
        'name' => 'regex',
        'type' => 'varchar',
        'size' => 80,
    ),
    array(
        'name' => 'boards',
        'type' => 'varchar',
        'size' => 80,
    ),
    array(
        'name' => 'groups',
        'type' => 'varchar',
        'size' => 80,
    ),
    array(
        'name' => 'bbc',
        'type' => 'enum(\'no\',\'yes\')',
    ),
    array(
        'name' => 'can_search',
        'type' => 'enum(\'no\',\'yes\')',
    ),
    array(
        'name' => 'active',
        'type' => 'enum(\'yes\',\'no\')',
    ),
    array(
        'name' => 'required',
        'type' => 'enum(\'yes\',\'no\')',
    ),
    array(
        'name' => 'eval',
        'type' => 'enum(\'no\',\'yes\')',
    ),
    array(
        'name' => 'topic_only',
        'type' => 'enum(\'no\',\'yes\')',
    ),
    array(
        'name' => 'mi',
        'type' => 'enum(\'no\',\'yes\')',
    ),
);

$indexes = array(
    array(
        'type' => 'primary',
        'columns' => array('id_field')
    ),
);

$smcFunc['db_create_table']('{db_prefix}message_fields', $columns, $indexes, array(), 'overwrite');

$columns = array(
    array(
        'name' => 'id_field',
        'type' => 'mediumint',
        'size' => 8,
        'unsigned' => true,
    ),
    array(
        'name' => 'id_msg',
        'type' => 'int',
        'size' => 10,
        'unsigned' => true,
    ),
    array(
        'name' => 'value',
        'type' => 'varchar',
        'size' => 4096,
    ),
);

$indexes = array(
    array(
        'type' => 'primary',
        'columns' => array('id_field', 'id_msg')
    ),
);

$smcFunc['db_create_table']('{db_prefix}message_field_data', $columns, $indexes, array(), 'update_remove');

$in_col = array(
    'name' => 'string', 'type' => 'string', 'size' => 'string', 'options' => 'string', 'active' => 'string', 'default_value' => 'string',
    'can_search' => 'string', 'groups' => 'string', 'boards' => 'string', 'topic_only' => 'string', 'mi' => 'string', 'mask' => 'string',
);
$in_data = array(
    array(
        'Notice', 'text', 80, '', 'yes', '',
        'no', '1', '1', 'yes', 'no', 'nohtml',
    ),
    array(
        'When', 'select', 80, 'ASAP,Tomorrow,This Week', 'yes', '',
        'no', '1', '1', 'yes', 'no', '',
    ),
    array(
        'Price', 'select', 80, 'Quote Me,Fixed', 'yes', '',
        'no', '1', '1', 'yes', 'yes', '',
    ),
    array(
        '£', 'text', 80, '', 'yes', '',
        'no', '1', '1', 'yes', 'yes', 'nohtml',
    ),
    array(
        'To', 'text', 80, '', 'yes', '',
        'no', '1', '1', 'yes', 'yes', 'nohtml',
    ),
    array(
        'From', 'text', 80, '', 'yes', '',
        'yes', '1', '1', 'yes', 'yes', 'nohtml',
    ),
    array(
        'To', 'text', 80, '', 'yes', '',
        'no', '1', '1', 'yes', 'yes', 'nohtml',
    ),
    array(
        'From', 'text', 80, '', 'yes', '',
        'no', '1', '1', 'yes', 'yes', 'nohtml',
    ),
    array(
        'Add picture', 'text', 80, '', 'yes', '',
        'no', '1', '1', 'yes', 'no', 'img',
    ),
    array(
        'Contact me via', 'select', 80, 'Mobile,Email,Personal Message,Telephone', 'yes', '',
        'no', '1', '1', 'yes', 'no', '',
    ),
);
$smcFunc['db_insert']('',
    '{db_prefix}message_fields',
    $in_col,
    $in_data,
    array('id_field')
);

if (!empty($ssi)) {
    echo 'Database installation complete!';
}

?>