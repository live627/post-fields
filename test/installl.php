<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
{
	$ssi = true;
	require_once(dirname(__FILE__) . '/SSI.php');
}
elseif (!defined('SMF'))
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

if (!array_key_exists('db_add_column', $smcFunc))
	db_extend('packages');

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

if (!empty($ssi))
	echo 'Database installation complete!';
