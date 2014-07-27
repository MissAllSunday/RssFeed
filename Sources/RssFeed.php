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

	public function settings()
	{

	}

	public function ScheduledTask()
	{

	}
}
