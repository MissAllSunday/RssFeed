<?php

/**
 * @package RssFeed mod
 * @version 1.0
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2016, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');

else if(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php and SSI.php files.');

if ((SMF == 'SSI') && !$user_info['is_admin'])
	die('Admin priveleges required.');

// Prepare and insert this mod's config array.
$_config = array(
	'_availableHooks' => array(
		'admin' => 'integrate_admin_areas',
	),
);

// All good.
updateSettings(array('_configRssFeed' => json_encode($_config)));

// Update or create $modSettings['OharaAutoload']
if (empty($modSettings['OharaAutoload']))
	$pref = array(
		'namespaces' => array(
			'SimplePie' => array('{$vendorDir}/simplepie/simplepie/library'),
		),
		'psr4' => array(),
		'classmap' => array(),
	);

else
{
	$pref = smf_json_decode($modSettings['OharaAutoload'], true);

	$pref['namespaces']['SimplePie'] = array('{$vendorDir}/simplepie/simplepie/library');
}

// Either way, save it.
updateSettings(array('OharaAutoload' => json_encode($pref)));

if (SMF == 'SSI')
	echo 'Database changes are complete!';