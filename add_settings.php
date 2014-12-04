<?php

/**
 * @package RssFeed mod
 * @version 1.0
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2014, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */


// List settings here in the format: setting_key => default_value.  Escape any "s. (" => \")
$mod_settings = array();

// Settings to create the new tables...
$tables = array();
$tables[] = array(
	'table_name' => '{db_prefix}rssfeeds',
	'if_exists' => 'ignore',
	'error' => 'fatal',
	'parameters' => array(),
	'columns' => array(
		array(
			'name' => 'id_feed',
			'auto' => true,
			'default' => '',
			'type' => 'mediumint',
			'size' => 8,
			'null' => false,
		),
		array(
			'name' => 'id_board',
			'default' => 0,
			'type' => 'smallint',
			'size' => 5,
			'null' => false,
		),
		array(
			'name' => 'id_topic',
			'default' => 0,
			'type' => 'mediumint',
			'size' => 8,
			'null' => false,
		),
		array(
			'name' => 'icon',
			'default' => 'xx',
			'type' => 'varchar',
			'size' => 16,
			'null' => false,
		),
		array(
			'name' => 'feedurl',
			'default' => '',
			'type' => 'tinytext',
			'null' => false,
		),
		array(
			'name' => 'title',
			'default' => '',
			'type' => 'tinytext',
			'null' => false,
		),
		array(
			'name' => 'enabled',
			'default' => 1,
			'type' => 'tinyint',
			'size' => 4,
			'null' => false,
		),
		array(
			'name' => 'postername',
			'default' => null,
			'type' => 'tinytext',
			'null' => true,
		),
		array(
			'name' => 'id_member',
			'default' => null,
			'type' => 'mediumint',
			'size' => 8,
			'null' => true,
		),
		array(
			'name' => 'keywords',
			'default' => null,
			'type' => 'tinytext',
			'null' => true,
		),
		array(
			'name' => 'getfull',
			'default' => 0,
			'type' => 'tinyint',
			'size' => 4,
			'null' => false,
		),
		array(
			'name' => 'regex',
			'default' => null,
			'type' => 'tinytext',
			'null' => true,
		),
		array(
			'name' => 'locked',
			'default' => 0,
			'type' => 'tinyint',
			'size' => 4,
			'null' => false,
		),
		array(
			'name' => 'approve',
			'default' => 0,
			'type' => 'tinyint',
			'size' => 4,
			'null' => false,
		),
		array(
			'name' => 'singletopic',
			'default' => 0,
			'type' => 'tinyint',
			'size' => 4,
			'null' => false,
		),
		array(
			'name' => 'topicprefix',
			'default' => null,
			'type' => 'tinytext',
			'null' => true,
		),
		array(
			'name' => 'footer',
			'default' => null,
			'type' => 'tinytext',
			'null' => true,
		),
		array(
			'name' => 'numbertoimport',
			'default' => 0,
			'type' => 'smallint',
			'size' => 5,
			'null' => false,
		),
		array(
			'name' => 'updatetime',
			'default' => 0,
			'type' => 'int',
			'size' => 10,
			'null' => false,
		),
		array(
			'name' => 'importcount',
			'default' => 0,
			'type' => 'mediumint',
			'size' => 8,
			'null' => true,
		),
	),
	'indexes' => array(
		array(
			'columns' => array('id_feed'),
			'type' => 'primary',
			'name' => '',
		),
		array(
			'columns' => array('enabled'),
			'type' => 'index',
			'name' => 'enabled',
		),
	),
);

$tables[] = array(
	'table_name' => '{db_prefix}log_rssfeeds',
	'if_exists' => 'ignore',
	'error' => 'fatal',
	'parameters' => array(),
	'columns' => array(
		array(
			'name' => 'id_feeditem',
			'auto' => true,
			'default' => null,
			'type' => 'mediumint',
			'size' => 8,
			'null' => false,
		),
		array(
			'name' => 'id_feed',
			'default' => null,
			'type' => 'mediumint',
			'size' => 8,
			'null' => false,
		),
		array(
			'name' => 'hash',
			'default' => '',
			'type' => 'tinytext',
			'null' => false,
		),
		array(
			'name' => 'time',
			'default' => 0,
			'type' => 'int',
			'size' => 10,
			'null' => false,
		),
	),
	'indexes' => array(
		array(
			'columns' => array('id_feeditem'),
			'type' => 'PRIMARY',
			'name' => '',
		),
		array(
			'columns' => array('id_feed'),
			'type' => 'INDEX',
			'name' => 'id_feed',
		),
		array(
			'columns' => array('hash (4)'),
			'type' => 'INDEX',
			'name' => 'hash',
		),
	),
);


$rows = array();
$rows[] = array(
	'method' => 'ignore',
	'table_name' => '{db_prefix}scheduled_tasks',
	'columns' => array(
		'next_time' => 'int',
		'time_offset' => 'int',
		'time_regularity' => 'int',
		'time_unit' => 'string',
		'disabled' => 'int',
		'task' => 'string',
		'callable' => 'string',
	),
	'data' => array (1231542000, 126000, 6, 'h', 0, 'rss_feeder', 'RssFeed.php|RssFeed::task#'),
	'keys' => array('id_task'),
);

/******************************************************************************/

// If SSI.php is in the same place as this file, and SMF isn't defined, this is being run standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
// Hmm... no SSI.php and no SMF?
elseif(!defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');

// Figure out if we need to add in this setting or not...
$request = $smcFunc['db_query']('', '
	SELECT value
	FROM {db_prefix}settings
	WHERE variable = \'pruningOptions\'
	LIMIT 1', array());
list($temp_setting) = $smcFunc['db_fetch_row']($request);
$smcFunc['db_free_result']($request);

// Kinda need this...
loadLanguage('Admin');

require_once($sourcedir . '/ManageSettings.php');
$file_count = count(array_keys(ModifyLogSettings(true))) - 2;
$db_count = count(explode(',', $temp_setting));
if ($db_count < ($file_count + 1))
	$mod_settings['pruningOptions'] = $temp_setting . ',30';

// Update the settings (if it wasn't empty)
if (!empty($mod_settings))
	updateSettings($mod_settings);

// Create new tables
if (!empty($tables))
{
	db_extend('packages');
	foreach ($tables as $table)
		$smcFunc['db_create_table']($table['table_name'], $table['columns'], $table['indexes'], $table['parameters'], $table['if_exists'], $table['error']);
}

// Insert rows into tables
if (!empty($rows))
	foreach ($rows as $row)
		$smcFunc['db_insert']($row['method'], $row['table_name'], $row['columns'], $row['data'], $row['keys']);


if (SMF == 'SSI')
	redirectExit('action=admin;area=modsettings;sa=rssfeeds');

?>