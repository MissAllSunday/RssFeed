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
		$this->feedID = $this->validate('feed') ? $this->data('feed') : 0;

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

		// Any errors?
		$context['rss_feed'] = $this->getMessage('data');
		$context['feed_errors'] = $this->getMessage('errors');

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

			$context['rss_feed'] = $this->smcFunc['db_fetch_assoc']($request);
			$context['rss_feed'] = htmlspecialchars__recursive($context['rss_feed']);
			$this->smcFunc['db_free_result']($request);
		}

		$context['icon'] = !empty($context['rss_feed']['icon']) ? $context['rss_feed']['icon'] : 'xx';

		require_once($this->sourceDir . '/Subs-Editor.php');

		// Message icons - customized icons are off?
		$context['icons'] = getMessageIcons(!empty($context['rss_feed']['id_board']) ? $context['rss_feed']['id_board'] : 0);

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

		// If they deleted or saved, let's show the main list
		$context['sub_template'] = 'rss_feeder_list';

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

							return '<a href="' . $that->scriptUrl . '?action=admin;area=RssFeed;sa=rssfeeds;feed=' . $rowData['id_feed'] . ($rowData['enabled'] ? ';disable' : ';enable') . '"><img src="' . $that->settings['images_url'] . ($rowData['enabled'] ? '/rss_enabled.gif' : '/rss_disabled.gif') . '" alt="*" /></a>';
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
							'format' => '<a href="' . $this->scriptUrl . '?action=admin;area=RssFeed;sa=rssfeeds;feed=%1$d">' . $this->text('feed_modify') . '</a>',
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
		// First we check the session...
		checkSession();

		// Put the insert array together...
		$insertOptions = array();

		// Let's do the 'unrequireds' first...
		$insertOptions['id_board'] = $this->data('feed_board');
		$insertOptions['icon'] = $this->data('icon') ? preg_replace('~[\./\\\\*\':"<>]~', '', $this->data('icon')) : 'xx';
		$insertOptions['enabled'] = $this->data('feed_enabled') ? $this->data('feed_enabled') : 0;
		$insertOptions['keywords'] = $this->data('feed_keywords') ? $this->data('feed_keywords') : '';
		$insertOptions['locked'] = $this->data('feed_locked') ? 1 : 0;
		$insertOptions['getfull'] = $this->data('feed_full') ? 1 : 0;
		$insertOptions['approve'] = $this->data('feed_approve') ? 1 : 0;
		$insertOptions['singletopic'] = $this->data('feed_singletopic') ? 1 : 0;
		$insertOptions['topicprefix'] = $this->data('feed_prefix') ? $this->data('feed_prefix') : '';
		$insertOptions['footer'] = $this->data('feed_footer') ? $this->data('feed_footer') : '';
		$insertOptions['numbertoimport'] = $this->data('feed_import') ? $this->data('feed_import') : 0;

		$context['feed_errors'] = array();

		// And now the requireds that we can throw errors on...
		if (!$this->data('feed_title'))
			$context['feed_errors']['feed_title'] = ($insertOptions['title'] = '');

		else
			$insertOptions['title'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $this->data('feed_title'));

		if (!$this->data('feed_url'))
			$context['feed_errors']['feed_url'] = ($insertOptions['feedurl'] = '');

		else
			$insertOptions['feedurl'] = $this->data('feed_url');

		if (!$this->data('feed_poster'))
			$context['feed_errors']['feed_poster'] = ($insertOptions['postername'] = '');

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
					'name' => $this->data('feed_poster'),
				)
			);
			if ($this->smcFunc['db_num_rows']($request) != 1)
				$context['feed_errors']['feed_poster'] = ($insertOptions['postername'] = $this->data('feed_poster'));
			else
			{
				$insertOptions['postername'] = $this->data('feed_poster');
				list($insertOptions['id_member']) = $this->smcFunc['db_fetch_row']($request);
			}
			$this->smcFunc['db_free_result']($request);
		}

		$insertOptions['regex'] = $this->data('feed_regex') ? $this->data('feed_regex') : '';
		if (!empty($insertOptions['getfull']) && empty($insertOptions['regex']))
			$context['feed_errors']['feed_regex'] = '';

		// if we had any errors, lets kick back a screen and highlight them...
		if (!empty($context['feed_errors']))
		{
			$this->setMessage('errors', $context['feed_errors']);
			$this->setMessage('data', $insertOptions);
			redirectexit('action=admin;area=RssFeed;sa=add'. ($this->feedID ? ';feed='. $this->feedID : ''));
		}

		// Looks like we're good.
		// Modifying an existing feed?
		if (!empty($_REQUEST['feed_id']))
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
				array_merge(array('id_feed' => (int)$_REQUEST['feed_id']), $insertOptions)
			);
			$context['feed_insert_success'] = $txt['rss_feed_update_success'];
		}
		// Or I guess we're inserting a new one
		else
		{
			// Fix up the stuff for insertion, make sure the arrays are aligned
			$insertRows = array( 'singletopic' => 'int', 'icon' => 'string', 'footer' => 'string', 'getfull' => 'int', 'id_board' => 'int', 'feedurl' => 'string', 'title' => 'string', 'enabled' => 'int', 'postername' => 'string', 'id_member' => 'int', 'keywords' => 'string', 'regex' => 'string', 'locked' => 'int', 'approve' => 'int', 'topicprefix' => 'string', 'numbertoimport' => 'int' );
			ksort($insertRows);
			ksort($insertOptions);

			$this->smcFunc['db_insert']('',
				'{db_prefix}rssfeeds',
				$insertRows,
				$insertOptions,
				array('id_feed')
			);
			$id_feed = $this->smcFunc['db_insert_id']('{db_prefix}rssfeeds', 'id_feed');
			if (empty($id_feed))
				$context['feed_insert_error'] = $txt['rss_feed_insert_error'];
			else
				$context['feed_insert_success'] = $txt['rss_feed_insert_success'];
		}
	}

	public function ScheduledTask()
	{

	}
}
