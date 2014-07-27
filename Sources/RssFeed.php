<?php

/**
 * @package RssFeed mod
 * @version 1.0
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2014, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

if (!defined('SMF'))
	die('No direct access...');

// Use composer!
require_once ($boarddir .'/vendor/autoload.php');

class RssFeed extends Suki\Ohara
{
	public $name = __CLASS__;

	public function __construct()
	{
		$this->setRegistry();
	}

	public function admin(&$adminAreas)
	{
		$adminAreas['config']['areas'][$this->name] = array(
			'label' => $this->text('menu_name'),
			'file' => 'RssFeed.php',
			'function' => 'RssFeed::subActions#',
			'icon' => 'posts.png',
			'subsections' => array(
				'settings' => $this->text('menu_name_settings'),
				'list' => array($this->text('menu_name_list'))
			),
		);
	}

	public function subActions()
	{
		global $context;

		$context['page_title'] = $this->text('menu_name');

		// Safety first!
		$subActions = array(
			'settings',
			'list'
		);

		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $this->text('menu_name'),
			'description' => $this->text('menu_name_desc'),
			'tabs' => array(
				'settings' => array(
				),
				'list' => array(
				)
			),
		);

		if ($this->validate('sa') && isset($subActions[$this->data('sa')]))
		{
			$call = $this->data('sa');
			$call();
			unset($call);
		}

		else
			$this->settings();
	}

	public function settings()
	{

	}

	public function list()
	{

	}

	public function ScheduledTask()
	{

	}
}
