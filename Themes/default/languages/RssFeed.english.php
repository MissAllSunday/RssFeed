<?php

/**
 * @package RssFeed mod
 * @version 1.0
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2016, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

global $txt;

$txt['RssFeed_modName'] = 'RSS Feeds';
$txt['RssFeed_modName_settings'] = 'Settings';
$txt['RssFeed_modName_list'] = 'Feed List';
$txt['RssFeed_update_enable'] = 'Successfully enable the feed';
$txt['RssFeed_update_delete'] = 'Successfully deleted the feed';
$txt['RssFeed_update_add'] = 'Successfully added the feed';
$txt['RssFeed_update_save'] = 'Successfully saved the feed';
$txt['RssFeed_none'] = 'None';
$txt['RssFeed_board_error'] = 'No board';
$txt['RssFeed_feed_board'] = 'Board';
$txt['RssFeed_feed_count'] = 'count';
$txt['RssFeed_feed_updated'] = 'Last Import';
$txt['RssFeed_feed_modify'] = 'Modify';
$txt['RssFeed_feed_add'] = 'Add a new feed';
$txt['RssFeed_feed_delete'] = 'Delete';
$txt['RssFeed_feed_confirm'] = 'Are you sure you wish to delete the selected feeds?';
$txt['RssFeed_feed_enabled'] = 'Enabled';
$txt['RssFeed_feed_enabled_desc'] = 'Enabled/Disabled this specific feed';
$txt['RssFeed_feed_title'] = 'Feed Title';
$txt['mods_cat_modifications_rssfeeds'] = 'RSS Feeder';
$txt['RssFeed_feed_desc'] = 'This section allows you to setup and view RSS feeds that post to your forum.';
$txt['RssFeed_feed_enabled'] = 'Enabled';
$txt['RssFeed_feed_title'] = 'Feed Title';
$txt['RssFeed_feed_title_desc'] = 'This should be an internal title used for the feed.';
$txt['RssFeed_feed_feedurl'] = 'Feed URL';
$txt['RssFeed_feed_feedurl_desc'] = 'This should be a valid RSS feed.  If the parser finds it to be invalid, it will be disabled.';
$txt['RssFeed_feed_postername'] = 'Post Feed As';
$txt['RssFeed_feed_postername_desc'] = 'Enter who you would like this topic posted as.';
$txt['RssFeed_feed_topicprefix'] = 'Topic Prefix';
$txt['RssFeed_feed_topicprefix_desc'] = 'If you would like this feed\'s posts prefixed, enter one here.  This is optional';
$txt['RssFeed_feed_numbertoimport'] = 'Number To Import';
$txt['RssFeed_feed_numbertoimport_desc'] = 'You can set the number of items to import on each load.  0 to import all.';
$txt['RssFeed_feed_locked'] = 'Topic Locked';
$txt['RssFeed_feed_locked_desc'] = 'The topic where the feeds will be posted is going to be locked';
$txt['RssFeed_feed_approve'] = 'Require Topic Approval';
$txt['RssFeed_feed_full_article'] = 'Retrieve Full Article';
$txt['RssFeed_feed_singletopic'] = 'Post Items In Single Topic';
$txt['RssFeed_feed_singletopic_desc'] = 'All messages will be posted on the same topic.';
$txt['RssFeed_feed_regex'] = 'Regular Expression';
$txt['RssFeed_feed_regex_desc'] = 'Required if retrieving full article.  This must be <a href="http://us.php.net/manual/en/book.pcre.php">PCRE</a> format.  The match is expected to be in second index of the array ([1]).';
$txt['RssFeed_feed_keywords'] = 'Keywords To Find';
$txt['RssFeed_feed_keywords_desc'] = 'If you would like feeds only to be imported if they contain certain keywords, enter those in a comma delimited list here (ex: key1, key2).';
$txt['RssFeed_feed_board'] = 'Board';
$txt['RssFeed_feed_modify'] = 'Modify';
$txt['RssFeed_feed_none'] = 'There are no feeds to display';
$txt['RssFeed_feed_add'] = 'Add Feed';
$txt['RssFeed_feed_delete'] = 'Delete Selected';
$txt['RssFeed_feed_confirm'] = 'Are you sure you wish to delete the selected feeds?';
$txt['RssFeed_feed_not_found'] = 'Could not find a feed with that id.';
$txt['RssFeed_feed_no_boards'] = 'There are no boards to post feeds to.';
$txt['RssFeed_feed_add_error'] = 'The information highlighted in red is required';
$txt['RssFeed_feed_insert_error'] = 'Could not insert the new feed';
$txt['RssFeed_feed_insert_info'] = 'The feed was created successfully';
$txt['RssFeed_feed_update_info'] = 'The feed was updated successfully';
$txt['RssFeed_feed_enable_info'] = 'The feed was changed successfully';
$txt['RssFeed_feed_delete_info'] = 'The feed was successfully deleted';
$txt['scheduled_task_desc_rss_feeder'] = 'Fetches RSS feeds that have been setup in the Feeder settings.';
$txt['scheduled_task_rss_feeder'] = 'RSS Feeder';
$txt['pruneRssFeedLog'] = 'Remove RSS Feeder entries older than:<div class="smalltext">(0 to disable)</div>';
$txt['RssFeed_feed_footer'] = 'Footer Text';
$txt['RssFeed_feed_footer_desc'] = 'If you would like any text in the post after the feed is posted, enter it here.  Any BBCode is allowed';
$txt['RssFeed_feed_count'] = 'Posts';
$txt['RssFeed_feed_board_error'] = 'No board';
$txt['RssFeed_feed_source'] = 'Source';
$txt['RssFeed_feed_updated'] = 'Last Import';