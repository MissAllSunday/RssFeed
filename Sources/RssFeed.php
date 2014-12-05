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
		// Initialize everything.
		$this->setRegistry();
	}

	public function admin(&$adminAreas)
	{
		$adminAreas['config']['areas'][$this->name] = array(
			'label' => $this->text('menu_name'),
			'file' => 'RssFeed.php',
			'function' => 'RssFeed::call#',
			'icon' => 'posts.png',
			'subsections' => array(
				'list' => array($this->text('menu_name_list')),
				'add' => array($this->text('feed_add'))
			),
		);
	}

	public function call()
	{
		global $context;

		loadTemplate($this->name);

		// A feed ID is going to be used a lot so better set this right now, 0 for adding a new feed.
		$this->feedID = $this->validate('feedID') ? $this->data('feedID') : 0;

		$context['page_title'] = $this->text('menu_name');

		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $this->text('menu_name'),
			'description' => $this->text('feed_desc'),
			'tabs' => array(
				'list' => array(),
				'add' => array(),
			),
		);

		// Subactions.
		$subActions = array('list', 'add',);

		$call = ($this->validate('sa') && in_array($this->data('sa'), $subActions) ? $this->data('sa') : 'list') . 'Feed';

		return $this->$call();
	}

	public function addFeed()
	{
		global $context;

		// Saving?
		if ($this->data('do') && $this->data('do') == 'save')
			return $this->saveFeed();

		$context['fields'] = array(
			'enabled' => array('type' => 'check'),
			'title' => array('type' => 'text'),
			'url' => array('type' => 'text'),
			'poster' => array('type' => 'text'),
			'prefix' => array('type' => 'text'),
			'import' => array('type' => 'text'),
			'keywords' => array('type' => 'text'),
			'locked' => array('type' => 'check'),
			'single' => array('type' => 'check'),
		);

		// Any errors?
		$context['feed'] = $this->getMessage('data');
		$context['errors'] = $this->getMessage('errors');

		$context['sub_template'] = 'rss_feeder_add';

		// Load the boards and categories for adding or editing a feed.
		$request = $this->smcFunc['db_query']('', '
			SELECT b.id_board, b.name, b.child_level, c.name AS cat_name, c.id_cat
			FROM {db_prefix}boards AS b
				LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)',
			array()
		);
		$context['categories'] = array();
		while ($row = $this->smcFunc['db_fetch_assoc']($request))
		{
			if (!isset($context['categories'][$row['id_cat']]))
				$context['categories'][$row['id_cat']] = array (
					'name' => strip_tags($row['cat_name']),
					'boards' => array(),
				);

			$context['categories'][$row['id_cat']]['boards'][] = array(
				'id' => $row['id_board'],
				'name' => strip_tags($row['name']),
				'category' => strip_tags($row['cat_name']),
				'child_level' => $row['child_level'],
				'selected' => !empty($_SESSION['move_to_topic']) && $_SESSION['move_to_topic'] == $row['id_board'] && $row['id_board'] != $board,
			);
		}
		$this->smcFunc['db_free_result']($request);

		if (empty($context['categories']))
			fatal_lang_error('RssFeed_feed_no_boards', false);

		// If we're just adding a feed, we can return, don't need to do anything further
		if ($this->feedID)
		{
			// Lets get the feed from the database
			$request = $this->smcFunc['db_query']('', '
				SELECT *
				FROM {db_prefix}rssfeeds
				WHERE id_feed = {int:feed}
				LIMIT 1',
				array(
					'feed' => $this->feedID,
				)
			);

			// No Feed?? ut oh... hacker!!
			if ($this->smcFunc['db_num_rows']($request) != 1)
				fatal_lang_error('RssFeed_feed_not_found', false);

			$context['feed'] = $this->smcFunc['db_fetch_assoc']($request);
			$context['feed'] = htmlspecialchars__recursive($context['feed']);
			$this->smcFunc['db_free_result']($request);
		}

		$context['icon'] = !empty($context['feed']['icon']) ? $context['feed']['icon'] : 'xx';

		require_once($this->sourceDir . '/Subs-Editor.php');

		// Message icons - customized icons are off?
		$context['icons'] = getMessageIcons(!empty($context['feed']['id_board']) ? $context['feed']['id_board'] : 0);

		if (!empty($context['icons']))
			$context['icons'][count($context['icons']) - 1]['is_last'] = true;

		$context['icon_url'] = '';
		for ($i = 0, $n = count($context['icons']); $i < $n; $i++)
		{
			$context['icons'][$i]['selected'] = $context['icon'] == $context['icons'][$i]['value'];
			if ($context['icons'][$i]['selected'])
				$context['icon_url'] = $context['icons'][$i]['url'];
		}
		if (empty($context['icon_url']))
		{
			$context['icon_url'] = $this->settings[file_exists($this->settings['theme_dir'] . '/images/post/' . $context['icon'] . '.gif') ? 'images_url' : 'default_images_url'] . '/post/' . $context['icon'] . '.gif';
			array_unshift($context['icons'], array(
				'value' => $context['icon'],
				'name' => $txt['current_icon'],
				'url' => $context['icon_url'],
				'is_last' => empty($context['icons']),
				'selected' => true,
			));
		}
	}

	public function listFeed()
	{
		global $context;

		$context['sub_template'] = 'rss_feeder_list';

		// Any message?
		$context['feed_message'] = $this->getMessage('message');

		// Quick trick for PHP < 5.4.
		$that = $this;

		// Create the table that will display the feeds.
		$listOptions = array(
			'id' => 'rss_feeder_list',
			'items_per_page' => 10,
			'default_sort_col' => 'icon',
			'base_href' => $this->scriptUrl . '?action=admin;area=RssFeed;sa=rssfeeds',
			'no_items_label' => $this->text('none'),
			'get_items' => array(
				'function' => function ($start, $items_per_page, $sort) use ($that)
				{
					$request = $that->smcFunc['db_query']('', '
						SELECT f.id_feed, b.name, f.title, f.feedurl, f.enabled, f.importcount, f.updatetime
						FROM {db_prefix}rssfeeds AS f
							LEFT JOIN {db_prefix}boards AS b ON (b.id_board = f.id_board)
						ORDER BY {raw:sort}
						LIMIT ' . $start . ', ' . $items_per_page,
						array (
							'sort' => $sort,
						)
					);
					$feeds = array();
					while ($row = $that->smcFunc['db_fetch_assoc']($request))
						$feeds[] = $row;

					$that->smcFunc['db_free_result']($request);

					return $feeds;
				},
			),
			'get_count' => array(
				'function' => function() use ($that)
				{
					$request = $that->smcFunc['db_query']('', '
						SELECT COUNT(*)
						FROM {db_prefix}rssfeeds',
						array(
						)
					);
					list($numFeeds) = $that->smcFunc['db_fetch_row']($request);
					$that->smcFunc['db_free_result']($request);

					return $numFeeds;
				}
			),
			'columns' => array(
				'icon' => array(
					'header' => array(
						'value' => $this->text('feed_enabled'),
					),
					'data' => array(
						'function' => function ($rowData) use($that)
						{
							if (empty($rowData['name']) && $rowData['enabled'])
							{
								$that->smcFunc['db_query']('', '
									UPDATE {db_prefix}rssfeeds
									SET enabled = 0
									WHERE id_feed = {int:feed}',
									array(
										'feed' => $rowData['id_feed'],
									)
								);

								// Log an error about the issue, just so the user can see why their feed was disabled...
								log_error($that->text('menu_name') . ': ' . $rowData['title'] . ' (' . $that->text('board_error') . ')');

								$rowData['enabled'] = 0;
							}

							return '<a href="' . $that->scriptUrl . '?action=admin;area=RssFeed;sa=rssfeeds;feedID=' . $rowData['id_feed'] . ($rowData['enabled'] ? ';disable' : ';enable') . '"><img src="' . $that->settings['images_url'] . ($rowData['enabled'] ? '/rss_enabled.gif' : '/rss_disabled.gif') . '" alt="*" /></a>';
						},
						'style' => 'text-align: center; width: 130px;',
					),
					'sort' => array(
						'default' => 'f.enabled',
						'reverse' => 'f.enabled DESC',
					),
				),
				'title' => array(
					'header' => array(
						'value' => $this->text('feed_title'),
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a target="_blank" href="%1$s">%2$s</a>',
							'params' => array(
								'feedurl' => true,
								'title' => true,
							),
						),
						'style' => 'text-align: center;',
					),
					'sort' => array(
						'default' => 'f.title',
						'reverse' => 'f.title DESC',
					),
				),
				'board' => array(
					'header' => array(
						'value' => $this->text('feed_board'),
					),
					'data' => array(
						'function' => function ($rowData) use($that)
						{
							return empty($rowData['name']) ? '<em><< ' . $that->text('board_error') . ' >></em>' : $rowData['name'];
						},
						'style' => 'text-align: center;',
					),
					'sort' => array(
						'default' => 'b.name',
						'reverse' => 'b.name DESC',
					),
				),
				'count' => array(
					'header' => array(
						'value' => $this->text('feed_count'),
					),
					'data' => array(
						'db' => 'importcount',
						'style' => 'text-align: center;',
					),
					'sort' => array(
						'default' => 'f.importcount',
						'reverse' => 'f.importcount DESC',
					),
				),
				'update' => array(
					'header' => array(
						'value' => $this->text('feed_updated'),
					),
					'data' => array(
						'function' => function ($rowData)
						{
							global $txt;

							return $rowData['updatetime'] == 0 ? $txt['never'] : timeformat($rowData['updatetime']);
						},
						'style' => 'text-align: center;',
					),
					'sort' => array(
						'default' => 'f.updatetime',
						'reverse' => 'f.updatetime DESC',
					),
				),
				'modify' => array(
					'header' => array(
						'value' => $this->text('feed_modify'),
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<a href="' . $this->scriptUrl . '?action=admin;area=RssFeed;sa=rssfeeds;feedID=%1$d">' . $this->text('feed_modify') . '</a>',
							'params' => array(
								'id_feed' => false,
							),
						),
						'style' => 'text-align: center; width: 50px;',
					),
				),
				'check' => array(
					'header' => array(
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="check" />',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="checked_feeds[]" value="%1$d" class="check" />',
							'params' => array(
								'id_feed' => false,
							),
						),
						'style' => 'text-align: center; width: 32px;',
					),
				),
			),
			'form' => array(
				'href' => $this->scriptUrl . '?action=admin;area=RssFeed;sa=rssfeeds',
				'name' => 'rssfeedForm',
			),
			'additional_rows' => array(
				array(
					'position' => 'above_column_headers',
					'value' => '
						[<a href="' . $this->scriptUrl . '?action=admin;area=;area=RssFeed;sa=add">'. $this->text('feed_add') . '</a>]',
					'style' => 'text-align: right;',
				),
				array(
					'position' => 'below_table_data',
					'value' => '
						<input class="button_submit" type="submit" onclick="return confirm(\'' . $this->text('feed_confirm') . '\')" name="delete" value="'. $this->text('feed_delete') . '" />',
					'style' => 'text-align: right;',
				),
			),
		);

		require_once($this->sourceDir . '/Subs-List.php');
		createList($listOptions);

		// No longer needed.
		unset($that);
	}

	public function deleteFeed()
	{
		$this->smcFunc['db_query']('', '
			DELETE FROM {db_prefix}rssfeeds
			WHERE id_feed IN ({array_int:feed_list})',
			array(
				'feed_list' => $this->data('toDelete'),
			)
		);
	}

	public function enableFeed($feed = false, $enable = '')
	{
		$enable = !empty($enable) ? (int) $enable : $this->data('enable');

		// Quick change on the status...
		$this->smcFunc['db_query']('', '
			UPDATE {db_prefix}rssfeeds
			SET enabled = {int:option}
			WHERE id_feed = {int:feed}',
			array(
				'option' => $enable,
				'feed' => $this->feedID,
			)
		);
	}

	public function saveFeed()
	{
		// Check the session.
		checkSession();

		// By default everything is blank.
		$insertOptions = array(
			'enabled' => 0,
			'title' => '',
			'url' => '',
			'poster' => '',
			'prefix' => '',
			'import' => '',
			'keywords' => '',
			'locked' => 0,
			'single' => 0,
			'icon' => '',
			'board' => 0,
			'full' => 0,
			'regex' => '',
			'footer' => '',
		);

		// Get the data.
		$insertOptions = $this->data('feed');

		$context['errors'] = array();

		// And now the required that we can throw errors on...
		if (!$insertOptions['title'])
			$context['errors']['title'] = ($insertOptions['title'] = '');

		else
			$insertOptions['title'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $insertOptions['title']);

		if (!$insertOptions['url'])
			$context['errors']['url'] = ($insertOptions['url'] = '');

		else
			$insertOptions['url'] = $this->data('url');

		if (!$insertOptions['poster'])
			$context['errors']['poster'] = ($insertOptions['poster'] = '');

		// Do a query to get the member's id
		else
		{
			$request = $this->smcFunc['db_query']('', '
				SELECT id_member
				FROM {db_prefix}members
				WHERE real_name = {string:name}
					OR member_name = {string:name}
				LIMIT 1',
				array(
					'name' => $insertOptions['poster'],
				)
			);

			if ($this->smcFunc['db_num_rows']($request) != 1)
				$context['errors']['poster'] = ($insertOptions['poster'] = '');

			else
			{
				list($insertOptions['id_member']) = $this->smcFunc['db_fetch_row']($request);
			}
			$this->smcFunc['db_free_result']($request);
		}

		if (!empty($insertOptions['full']) && empty($insertOptions['regex']))
			$context['errors']['feed_regex'] = '';

		// if we had any errors, lets kick back a screen and highlight them...
		if (!empty($context['errors']))
		{
			$this->setMessage('errors', $context['errors']);
			$this->setMessage('data', $insertOptions);
			redirectexit('action=admin;area=RssFeed;sa=add'. ($this->feedID ? ';feedID='. $this->feedID : ''));
		}

		// Looks like we're good.
		// Modifying an existing feed?
		if ($this->feedID)
		{
			$this->smcFunc['db_query']('','
				UPDATE {db_prefix}rssfeeds
				SET
					id_board = {int:id_board},
					feedurl = {string:feedurl},
					title = {string:title},
					icon = {string:icon},
					enabled = {int:enabled},
					postername = {string:postername},
					id_member = {int:id_member},
					keywords = {string:keywords},
					regex = {string:regex},
					locked = {int:locked},
					getfull = {int:getfull},
					approve = {int:approve},
					singletopic = {int:singletopic},
					topicprefix = {string:topicprefix},
					footer = {string:footer},
					numbertoimport = {int:numbertoimport}
				WHERE id_feed = {int:id_feed}',
				array_merge(array('id_feed' => $this->feedID), $insertOptions)
			);
			$this->setMessage('message', array('update' => 'info');
		}
		// Or I guess we're inserting a new one
		else
		{
			// Fix up the stuff for insertion, make sure the arrays are aligned.
			$insertRows = array(
			'enabled' => 'int',
			'title' => 'string',
			'url' => 'string',
			'poster' => 'string',
			'prefix' => 'string',
			'import' => 'string',
			'keywords' => 'string',
			'locked' => 'int',
			'single' => 'int',
			'icon' => 'string',
			'board' => 'int',
			'full' => 'int',
			'regex' => 'string',
			'footer' => 'string',
		);
			ksort($insertRows);
			ksort($insertOptions);

			$this->smcFunc['db_insert']('',
				'{db_prefix}rssfeeds',
				$insertRows,
				$insertOptions,
				array('id_feed')
			);
			$id_feed = $this->smcFunc['db_insert_id']('{db_prefix}rssfeeds', 'id_feed');

			$this->setMessage('message', array('insert' => (empty($id_feed) ? 'error' : 'info'));
		}

		// Either way, redirect back to the list page.
		redirectexit('action=admin;area=RssFeed');
	}

	public function ScheduledTask()
	{

	}
}
