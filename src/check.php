<?php

/**
 * @package PostFields
 * @version 2.0
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2011-2016, John Rayes
 * @license http://opensource.org/licenses/ISC ISC
 */
 
$required_php_version = '5.4.0';
if (version_compare(PHP_VERSION, $required_php_version, '<')) {
	die('Post Fields requires a minimum of PHP ' . $required_php_version . ' in order to function. (You are currently running PHP: ' . PHP_VERSION . ')');
}
