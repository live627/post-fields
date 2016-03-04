<?php

/**
 * @package PostFields
 * @version 2.0
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2011-2016, John Rayes
 * @license http://opensource.org/licenses/ISC ISC
 */
if (file_exists(__DIR__ . '/SSI.php') && !defined('SMF')) {
	$ssi = true;
	require_once(__DIR__ . '/SSI.php');
} elseif (!defined('SMF')) {
	exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');
}

if (!class_exists('ModHelper\Psr4AutoloaderClass')) {
	require_once(__DIR__ . '/PostFields/ModHelper/Psr4AutoloaderClass.php');
}
// instantiate the loader
$loader = new \ModHelper\Psr4AutoloaderClass;
// register the autoloader
$loader->register();
// register the base directories for the namespace prefix
$loader->addNamespace('ModHelper', __DIR__ . '/PostFields/ModHelper');

(new \ModHelper\Hooks)->add('integrate_pre_include', '$sourcedir/PostFields/live627/PostFields/Integration.php')
	->add('integrate_load_theme', '\\live627\\PostFields\\Integration::load_theme')
	->add('integrate_admin_areas', '\\live627\\PostFields\\Integration::admin_areas')
	->execute(empty($context['uninstalling']));

if (!empty($ssi)) {
	echo 'Database installation complete!';
}
