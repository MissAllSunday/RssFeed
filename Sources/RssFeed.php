<?php

/**
 * @package RssFeed mod
 * @version 1.0
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2016, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

if (!defined('SMF'))
	die('No direct access...');

// Ohara autoload!
require_once $sourcedir .'/ohara/src/Suki/autoload.php';

use Suki\Ohara;

class RssFeed extends Suki\Ohara
{
	public $name = __CLASS__;

	protected $_useConfig = true;

	public function __construct()
	{
		// Initialize everything.
		$this->setRegistry();
	}

	public function addAdmin(&$adminAreas)
	{
		$adminAreas['config']['areas'][$this->name] = array(
			'label' => $this->text('modName'),
			'file' => 'RssFeed.php',
			'function' => array($this, 'call'),
			'icon' => 'posts',
			'subsections' => array(
				'list' => array($this->text('modName')),
				'add' => array($this->text('feed_add'))
			),
		);
	}

	public function call()
	{
		global $context;

		loadTemplate($this->name);

		// Always check the session.
		checkSession('request');

		// A feed ID is going to be used a lot so better set this right now, 0 for adding a new feed.
		$this->feedID = $this['data']->validate('feedID') ? $this['data']->get('feedID') : 0;
		$context['page_title'] = $this->text('modName');
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => $this->text('modName'),
			'description' => $this->text('feed_desc'),
			'tabs' => array(
				'list' => array(),
				'add' => array(),
			),
		);

		// Subactions.
		$subActions = array('list', 'add');

		$this->_sa = $this['data']->get('sa');
		$call = ($this->_sa && in_array($this->_sa, $subActions) ?  $this->_sa : 'list') . 'Feed';

		return $this->{$call}();
	}

	public function listFeed()
	{
		global $smcFunc, $context, $settings;

		$subActions = array('delete', 'enable');
		$do = $this['data']->get('do');

		// Something to do? do do do...
		if (!empty($do) && in_array($do, $subActions))
		{
			$call = $do . 'Feed';
			$this->{$call}();

			// Set a proper message and do a redirect. Let us assume everything went fine...
			$this->setUpdate('message', array($this['data']->get('do') => 'info'));
			return redirectexit('action=admin;area='. $this->name .';'. $context['session_var'] .'='. $context['session_id']);
		}

		$context['sub_template'] = 'rss_feeder_list';

		// Any message?
		$context['feed_message'] = $this->getUpdate('message');

		// Quick trick for PHP < 5.4.
		$that = $this;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*)
			FROM {db_prefix}rssfeeds',
			array(
			)
		);

		list($numFeeds) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		// Create the table that will display the feeds.
		$listOptions = array(
			'id' => 'rss_feederList',
			'items_per_page' => 10,
			'default_sort_col' => 'icon',
			'base_href' => $this->scriptUrl . '?action=admin;area='. $this->name .';sa=rssfeeds;'. $context['session_var'] .'='. $context['session_id'],
			'no_items_label' => $this->text('none'),
			'get_items' => array(
				'function' => function ($start, $items_per_page, $sort) use ($smcFunc)
				{
					$request = $smcFunc['db_query']('', '
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
					while ($row = $smcFunc['db_fetch_assoc']($request))
						$feeds[] = $row;

					$smcFunc['db_free_result']($request);

					return $feeds;
				},
			),
			'get_count' => array(
				'function' => function() use ($numFeeds)
				{
					return $numFeeds;
				}
			),
			'columns' => array(
				'icon' => array(
					'header' => array(
						'value' => $this->text('feed_enabled'),
					),
					'data' => array(
						'function' => function ($rowData) use($that, $settings, $smcFunc)
						{
							if (empty($rowData['name']) && $rowData['enabled'])
							{
								$smcFunc['db_query']('', '
									UPDATE {db_prefix}rssfeeds
									SET enabled = 0
									WHERE id_feed = {int:feed}',
									array(
										'feed' => $rowData['id_feed'],
									)
								);

								// Log an error about the issue, just so the user can see why their feed was disabled...
								log_error($that->text('modName') . ': ' . $rowData['title'] . ' (' . $that->text('board_error') . ')');

								$rowData['enabled'] = 0;
							}

							return '<a href="' . $that->scriptUrl . '?action=admin;area='. $that->name .';sa='. $that->_sa .';feedID=' . $rowData['id_feed'] .';do=enable;enable='. ($rowData['enabled'] ? '0' : '1') . '" class="generic_icons '. ($rowData['enabled'] ? 'valid' : 'disable') . '"></a>';
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
							'format' => '<a href="' . $this->scriptUrl . '?action=admin;area='. $this->name .';sa=add;feedID=%1$d">' . $this->text('feed_modify') . '</a>',
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
							'format' => '<input type="checkbox" name="toDelete[]" value="%1$d" class="check" />',
							'params' => array(
								'id_feed' => false,
							),
						),
						'style' => 'text-align: center; width: 32px;',
					),
				),
			),
			'form' => array(
				'href' => $this->scriptUrl . '?action=admin;area='. $this->name .';sa='. $this->_sa .';;do=delete',
				'name' => 'rssfeedForm',
			),
			'additional_rows' => array(
				array(
					'position' => 'above_column_headers',
					'value' => '
						[<a href="' . $this->scriptUrl . '?action=admin;area=;area='. $this->name .';sa=add">'. $this->text('feed_add') . '</a>]',
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

	public function addFeed()
	{
		global $smcFunc, $context, $txt, $settings;

		// Saving?
		if ($this['data']->validate('do') && $this['data']->get('do') == 'save')
			return $this->saveFeed();

		$context['fields'] = array(
			'enabled' => array('type' => 'check'),
			'title' => array('type' => 'text'),
			'feedurl' => array('type' => 'text'),
			'postername' => array('type' => 'text'),
			'topicprefix' => array('type' => 'text'),
			'numbertoimport' => array('type' => 'text'),
			'keywords' => array('type' => 'text'),
			'locked' => array('type' => 'check'),
			'singletopic' => array('type' => 'check'),
		);

		// Any errors?
		$context['feed'] = $this->getUpdate('data');
		$context['errors'] = $this->getUpdate('errors');

		$context['sub_template'] = 'rss_feeder_add';

		// Load the boards and categories for adding or editing a feed.
		$request = $smcFunc['db_query']('', '
			SELECT b.id_board, b.name, b.child_level, c.name AS cat_name, c.id_cat
			FROM {db_prefix}boards AS b
				LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)',
			array()
		);
		$context['categories'] = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
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
		$smcFunc['db_free_result']($request);

		if (empty($context['categories']))
			fatal_lang_error('RssFeed_feed_no_boards', false);

		// If we're just adding a feed, we can return, don't need to do anything further.
		if ($this->feedID && empty($context['feed']))
		{
			// Lets get the feed from the database
			$request = $smcFunc['db_query']('', '
				SELECT *
				FROM {db_prefix}rssfeeds
				WHERE id_feed = {int:feed}
				LIMIT 1',
				array(
					'feed' => $this->feedID,
				)
			);

			// There is no feed.
			if ($smcFunc['db_num_rows']($request) != 1)
				fatal_lang_error('RssFeed_feed_not_found', false);

			$context['feed'] = $smcFunc['db_fetch_assoc']($request);
			$context['feed'] = $this['data']->sanitize($context['feed']);
			$smcFunc['db_free_result']($request);
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
			$context['icon_url'] = $settings[file_exists($settings['theme_dir'] . '/images/post/' . $context['icon'] . '.gif') ? 'images_url' : 'default_images_url'] . '/post/' . $context['icon'] . '.gif';
			array_unshift($context['icons'], array(
				'value' => $context['icon'],
				'name' => $txt['current_icon'],
				'url' => $context['icon_url'],
				'is_last' => empty($context['icons']),
				'selected' => true,
			));
		}
	}

	public function deleteFeed()
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}rssfeeds
			WHERE id_feed IN ({array_int:feed_list})',
			array(
				'feed_list' => $this['data']->get('toDelete'),
			)
		);
	}

	public function enableFeed($feed = false, $enable = '')
	{
		global $smcFunc;

		$enable = !empty($enable) ? $enable : $this['data']->get('enable');

		// An extra security check.
		$enable = (bool) $enable;
		$enable = (int) $enable;

		// Quick change on the status...
		$smcFunc['db_query']('', '
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
		global $smcFunc, $context;

		// By default everything is blank.
		$insertOptions = array(
			'enabled' => 0,
			'title' => '',
			'feedurl' => '',
			'postername' => '',
			'topicprefix' => '',
			'numbertoimport' => 1,
			'keywords' => '',
			'locked' => 0,
			'singletopic' => 0,
			'icon' => '',
			'id_board' => 0,
			'getfull' => 0,
			'regex' => '',
			'footer' => '',
		);

		// Get the data.
		$insertOptions = array_merge($insertOptions, $this['data']->get('feed'));

		$context['errors'] = array();

		// And now the required that we can throw errors on...
		if (!$insertOptions['title'])
			$context['errors']['title'] = ($insertOptions['title'] = '');

		else
			$insertOptions['title'] = preg_replace('~[&]([^;]{8}|[^;]{0,8}$)~', '&amp;$1', $insertOptions['title']);

		if (!$insertOptions['feedurl'])
			$context['errors']['feedurl'] = ($insertOptions['feedurl'] = '');

		if (!$insertOptions['postername'])
			$context['errors']['postername'] = ($insertOptions['postername'] = '');

		// Do a query to get the member's id
		else
		{
			$request = $smcFunc['db_query']('', '
				SELECT id_member
				FROM {db_prefix}members
				WHERE real_name = {string:name}
					OR member_name = {string:name}
				LIMIT 1',
				array(
					'name' => $insertOptions['postername'],
				)
			);

			if ($smcFunc['db_num_rows']($request) != 1)
				$context['errors']['poster'] = ($insertOptions['postername'] = '');

			else
				list($insertOptions['id_member']) = $smcFunc['db_fetch_row']($request);

			$smcFunc['db_free_result']($request);
		}

		if (!empty($insertOptions['getfull']) && empty($insertOptions['regex']))
			$context['errors']['regex'] = ($insertOptions['regex'] = '');

		// if we had any errors, lets kick back a screen and highlight them...
		if (!empty($context['errors']))
		{
			$this->setUpdate('errors', $context['errors']);
			$this->setUpdate('data', $insertOptions);
			redirectexit('action=admin;area='. $this->name .';sa=add;'. $context['session_var'] .'='. $context['session_id'] . ($this->feedID ? ';feedID='. $this->feedID : ''));
		}

		// Gotta need at least 1 feed to import...
		if (empty($insertOptions['numbertoimport']))
			$insertOptions['numbertoimport'] = 1;

		else
			$insertOptions['numbertoimport'] = (int) $insertOptions['numbertoimport'];

		// Looks like we're good.
		// Modifying an existing feed?
		if ($this->feedID)
		{
			$smcFunc['db_query']('','
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
					enabled = {int:enabled},
					singletopic = {int:singletopic},
					topicprefix = {string:topicprefix},
					footer = {string:footer},
					numbertoimport = {int:numbertoimport}
				WHERE id_feed = {int:id_feed}',
				array_merge(array('id_feed' => $this->feedID), $insertOptions)
			);
			$this->setUpdate('message', array('update' => 'info'));
		}
		// Or I guess we're inserting a new one
		else
		{
			// Fix up the stuff for insertion, make sure the arrays are aligned.
			$insertRows = array(
			'enabled' => 'int',
			'title' => 'string',
			'feedurl' => 'string',
			'postername' => 'string',
			'topicprefix' => 'string',
			'numbertoimport' => 'string',
			'keywords' => 'string',
			'locked' => 'int',
			'singletopic' => 'int',
			'icon' => 'string',
			'id_board' => 'int',
			'id_member' => 'int',
			'getfull' => 'int',
			'regex' => 'string',
			'footer' => 'string',
		);
			ksort($insertRows);
			ksort($insertOptions);

			$smcFunc['db_insert']('',
				'{db_prefix}rssfeeds',
				$insertRows,
				$insertOptions,
				array('id_feed')
			);
			$id_feed = $smcFunc['db_insert_id']('{db_prefix}rssfeeds', 'id_feed');

			$this->setUpdate('message', array('insert' => (empty($id_feed) ? 'error' : 'info')));
		}

		// Either way, redirect back to the list page.
		redirectexit('action=admin;area='. $this->name .';'. $context['session_var'] .'='. $context['session_id']);
	}

	public function task()
	{
		global $smcFunc, $cachedir, $modSettings, $context, $txt;

		// Adjust the timeout value... who knows how many feeds we have and how many items we're getting
		// If this whole thing can't run in 5 minutes, we've got issues, and I'm sure the host would complain...
		@set_time_limit(300);
		if (function_exists('apache_reset_timeout'))
			apache_reset_timeout();

		// Scheduled tasks doesn't load everything we need.
		require_once($this->sourceDir . '/Class-BrowserDetect.php');
		require_once($this->sourceDir . '/Logging.php');

		loadEssentialThemeData();
		$context['character_set'] = empty($modSettings['global_character_set']) ? $txt['lang_character_set'] : $modSettings['global_character_set'];

		// Lets do this....
		// First grab all of the enabled feeds...
		$request = $smcFunc['db_query']('', '
			SELECT f.id_feed, f.id_board, t.id_topic, f.icon, f.feedurl, f.postername, f.id_member, f.keywords, f.getfull, f.regex, f.locked, f.approve, f.singletopic, f.topicprefix, f.footer, f.numbertoimport
			FROM {db_prefix}rssfeeds as f
				LEFT JOIN {db_prefix}topics as t ON (t.id_topic = f.id_topic)
				INNER JOIN {db_prefix}boards as b ON (b.id_board = f.id_board)
			WHERE enabled = 1',
			array()
		);

		$feed_list = array();
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$feed_list[$row['id_feed']] = array(
				'board_id' => $row['id_board'],
				'topic_id' => empty($row['singletopic']) ? 0 : (empty($row['id_topic']) ? 0 : $row['id_topic']),
				'url' => $row['feedurl'],
				'icon' => $row['icon'],
				'poster_name' => $row['postername'],
				'poster_id' => $row['id_member'],
				'keywords' => array_filter(explode(',', $row['keywords']), 'trim'),
				'full_article' => $row['getfull'],
				'regex' => $row['regex'],
				'lock_topic' => $row['locked'],
				'require_approval' => $row['approve'] && !empty($modSettings['postmod_active']),
				'single_topic' => $row['singletopic'],
				'topic_prefix' => $row['topicprefix'],
				'footer' => $row['footer'],
				'import_count' => $row['numbertoimport'],
			);
		$smcFunc['db_free_result']($request);

		require_once($this->sourceDir . '/Subs-Editor.php');

		// We'll just run through each feed now... someone's gonna get a post count increase....
		foreach ($feed_list as $id => $feed)
		{
			// If this is already set, let's kill it, memory hog, it can be.
			if (isset($rss_data))
			{
				if (is_a($rss_data, 'SimplePie'))
					$rss_data->__destruct();

				unset($rss_data);
			}

			$rss_data = new SimplePie();
			$rss_data->enable_cache(true);
			$rss_data->set_cache_location($cachedir);
			$rss_data->set_cache_duration(129600); // 1.5 days Most scheduled tasks will run once a day so its pointless to set the cache lower than that.
			$rss_data->set_output_encoding($context['character_set']);
			$rss_data->strip_htmltags(true);
			$rss_data->set_feed_url($feed['url']);
			$rss_data->init();

			// If we don't get a valid chunk of data back, disable the feed
			if ($rss_data->error())
			{
				$smcFunc['db_query']('', '
					UPDATE {db_prefix}rssfeeds
					SET enabled = 0
					WHERE id_feed = {int:feed}',
					array(
						'feed' => $id,
					)
				);

				// Log an error about the issue, just so the user can see why their feed was disabled...
				log_error($this->text('modName') . ': ' . $feed['url'] . ' (' . $rss_data->error() . ')');
				continue;
			}

			// Run through each item in the feed
			$item_count = 0;
			$get_items = $rss_data->get_items();
			krsort($get_items);
			foreach ($get_items as $item)
			{
				// Do we have a cap on how many to import?
				if (!empty($feed['import_count']) && $item_count >= $feed['import_count'])
					continue 1;

				// If this item doesn't have a link or title, let's skip it
				if ($item->get_title() === null)
					continue;

				// Keyword search??
				if (!empty($feed['keywords']) && !$this->keywords($feed['keywords'], $item->get_title() . ($item->get_content() !== null ? ' ' . $item->get_content() : '')))
					continue;

				// OK, so this is a valid item to post about, has it already been logged?
				$request = $smcFunc['db_query']('', '
					SELECT id_feeditem
					FROM {db_prefix}log_rssfeeds
					WHERE id_feed = {int:feed}
						AND hash = {string:hash}
					LIMIT 1',
					array(
						'feed' => $id,
						'hash' => md5($item->get_title()),
					)
				);

				// It does exist already... skip it
				if ($smcFunc['db_num_rows']($request) != 0)
					continue;

				// I think it's time to actually post the feed... it has a link, it matched keywords (if we had them), it doesn't already exist...

				// Should we get the whole feed??
				$body = $item->get_content() !== null ? $item->get_content() : $item->get_title();
				$redirect_url = '';
				if (!empty($feed['full_article']) && $item->get_permalink() !== null)
				{
					$full_article = new SimplePie_File($item->get_permalink(), 10, 5, $rss_data->useragent);
					if ($full_article->success)
					{
						$matches = array();
						preg_match($feed['regex'], $full_article->body, $matches);
						$body = !empty($matches[1]) ? $matches[1] : $body;
						// If we had a redirect, let's update the link so it posts correct in the message
						if (!empty($full_article->redirects))
							$redirect_url = $full_article->url;
					}
				}
				// If the body is still empty at this point, no point in posting, so skip this item
				if (empty($body))
					continue;

				// We're all set to finally create the post
				// Strip out all of the tags so it's just text with new lines and line breaks
				// We're gonna try and maintain as much HTML as possible
				$body = html_to_bbc(preg_replace(array('~^\s+~', '~\s+$~'), '', $body));
				$title = $item->get_title();
				$feed_title = $rss_data->get_title() !== null ? $rss_data->get_title() : '';

				// compile the source
				$source = '';

				// Mess with the body.
				call_integration_hook('integrate_'. $this->name .'_body', array(&$body, &$title));

				// Format the post
				$message =
	($item->get_permalink() !== null ? '[url=' . $item->get_permalink() . ']' . $title . '[/url]' : $title) . '
	' . ($item->get_date() !== null ? '[b]' . $item->get_date() . '[/b]
	' : '') . '
	' . $body . '

	' . (!empty($source) ? '[b]' . $txt['RssFeed_feed_source'] . ':[/b] ' . $source . '

	' : '') . (!empty($feed['footer']) ? $feed['footer'] : '');

				// Might have to update the subject for the single topic people
				$subject = (!empty($feed['topic_prefix']) ? $feed['topic_prefix'] . ' ' : '') . (!empty($feed['single_topic']) && empty($feed['topic_id']) && !empty($feed_title) ? $feed_title : $title);

				// Create the message/topic/poster options and insert the topic on the board
				$msgOptions = array(
					'subject' => $subject,
					'body' => $message,
					'approved' => !$feed['require_approval'],
					'smileys_enabled' => false,
					'icon' => $feed['icon'],
				);
				$topicOptions = array(
					'id' => $feed['topic_id'],
					'board' => $feed['board_id'],
					'is_approved' => !$feed['require_approval'],
					'lock_mode' => !empty($feed['lock_topic']) ? 1 : null,
				);
				$posterOptions = array(
					'id' => $feed['poster_id'],
					'name' => $feed['poster_name'],
					'update_post_count' => true,
				);

				require_once($this->sourceDir . '/Subs-Post.php');

				if(createPost($msgOptions, $topicOptions, $posterOptions))
				{
					// Update the log table with this feed
					$smcFunc['db_insert']('',
						'{db_prefix}log_rssfeeds',
						array('id_feed' => 'int', 'hash' => 'string', 'time' => 'int'),
						array($id, md5($item->get_title()), time()),
						array('id_feeditem')
					);
					$item_count++;

					// Successful insertion, if a new topic was created and we're supposed keep adding to it, update the feed so we do
					if (!empty($feed['single_topic']) && $feed['topic_id'] != $topicOptions['id'])
						$feed['topic_id'] = $topicOptions['id'];
				}
			}

			// Set the update time of the feed in the database... not sure what I'll use this for yet, but it's nice to have
			$request = $smcFunc['db_query']('', '
				UPDATE {db_prefix}rssfeeds
				SET updatetime = {int:time},
					importcount = importcount + {int:item_count},
					id_topic = {int:topic}
				WHERE id_feed = ({int:feed})',
				array(
					'time' => time(),
					'item_count' => $item_count,
					'topic' => $feed['topic_id'],
					'feed' => $id,
				)
			);
		}

		return true;
	}

	public function keywords($keywords, $string)
	{
		global $context;

		if (function_exists('mb_strtolower'))
			$string = mb_strtolower($string, $context['character_set']);

		else
			$string = strtolower($string);

		if (!is_array($keywords))
			$keywords = explode(",", $keywords);

		foreach($keywords as $keyword)
		{
			if (function_exists('mb_strtolower'))
				$keyword = mb_strtolower($keyword, $context['character_set']);
			else
				$keyword = strtolower($keyword);

			if (strpos($string, trim($keyword)) !== false)
				return true;
		}

		return false;
	}
}
