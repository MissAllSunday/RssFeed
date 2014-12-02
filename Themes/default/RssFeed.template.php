<?php

/**
 * @package RssFeed mod
 * @version 1.0
 * @author Jessica González <suki@missallsunday.com>
 * @copyright Copyright (c) 2014, Jessica González
 * @license http://www.mozilla.org/MPL/2.0/
 */

function template_rss_feeder_list()
{
	global $context, $txt;

	if (!empty($context['feed_insert_success']))
		echo '
				<div class="windowbg" id="profile_success">
					', $context['feed_insert_success'], '
				</div>';

	if (!empty($context['feed_insert_error']))
		echo '
				<div class="windowbg" id="profile_error">
					', $context['feed_insert_error'], '
				</div>';

	template_show_list('rss_feeder_list');
}

function template_rss_feeder_add()
{
	global $context, $txt, $settings, $scripturl, $modSettings;

	echo '
				<form name="postmodify" action="', $scripturl, '?action=admin;area=modsettings;sa=rssfeeds" method="post" accept-charset="ISO-8859-1">
					<div class="title_bar">
						<h3 class="titlebg">', $txt['RssFeed_feed_add'], '</h3>
					</div>
					<span class="upperframe"><span></span></span>
					<div class="roundframe">';

	if (!empty($context['feed_errors']))
		echo '
						<div class="windowbg" id="profile_error">
							', $txt['RssFeed_feed_add_error'], '
						</div>';

	echo '
						<dl class="settings">
							<dt>
								<strong', (isset($context['feed_errors']['feed_title'])) ? ' class="error"' : '', '>', $txt['RssFeed_feed_title'], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_title_desc'], '</span>
							</dt>
							<dd>
								<input type="text" size="50" name="feed_title" value="', !empty($context['rss_feed']['title']) ? $context['rss_feed']['title'] : '', '" />
							</dd>
							<dt>
								<strong', (isset($context['feed_errors']['feed_url'])) ? ' class="error"' : '', '>', $txt['RssFeed_feed_url'], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_url_desc'], '</span>
							</dt>
							<dd>
								<input type="text" size="50" name="feed_url" value="', !empty($context['rss_feed']['feedurl']) ? $context['rss_feed']['feedurl'] : '', '" />
							</dd>
							<dt>
								<strong>', $txt['message_icon'], '</strong>
							</dt>
							<dd>
								<select name="icon" id="icon" onchange="showimage()">';

		// Loop through each message icon allowed, adding it to the drop down list.
		foreach ($context['icons'] as $icon)
			echo '
									<option value="', $icon['value'], '"', $icon['value'] == $context['icon'] ? ' selected="selected"' : '', '>', $icon['name'], '</option>';

		echo '
								</select>
								<img align="top" src="', $context['icon_url'], '" name="icons" hspace="15" alt="" />
							</dd>
							<dt>
								<strong>', $txt['board'], '</strong>
							</dt>
							<dd>
								<select name="feed_board">';

	foreach ($context['categories'] AS $category)
	{
		echo '
									<optgroup label="', $category['name'], '">';
		foreach ($category['boards'] as $board)
			echo '
										<option value="', $board['id'], '"', (!empty($context['rss_feed']['id_board']) && $context['rss_feed']['id_board'] == $board['id']) ? ' selected="selected"' : '', '>', $board['child_level'] > 0 ? str_repeat('==', $board['child_level']-1) . '=&gt;' : '', $board['name'], '</option>';
		echo '
									</optgroup>';
	}

	echo '
								</select>
							</dd>
							<dt>
								<strong', (isset($context['feed_errors']['feed_poster'])) ? ' class="error"' : '', '>', $txt['RssFeed_feed_poster'], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_poster_desc'], '</span>
							</dt>
							<dd>
								<input type="text" name="feed_poster" id="feed_poster" value="', !empty($context['rss_feed']['postername']) ? $context['rss_feed']['postername'] : '', '" size="30" />
							</dd>
							<dt>
								<strong>', $txt['RssFeed_feed_prefix'], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_prefix_desc'], '</span>
							</dt>
							<dd>
								<input type="text" name="feed_prefix" value="', !empty($context['rss_feed']['topicprefix']) ? $context['rss_feed']['topicprefix'] : '', '" size="30" />
							</dd>
							<dt>
								<strong>', $txt['RssFeed_feed_import'], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_import_desc'], '</span>
							</dt>
							<dd>
								<input type="text" name="feed_import" value="', !empty($context['rss_feed']['numbertoimport']) ? $context['rss_feed']['numbertoimport'] : '0', '" size="15" />
							</dd>
							<dt>
								<strong>', $txt['RssFeed_feed_keywords'], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_keywords_desc'], '</span>
							</dt>
							<dd>
								<input type="text" name="feed_keywords" value="', !empty($context['rss_feed']['keywords']) ? $context['rss_feed']['keywords'] : '', '" size="50" />
							</dd>
							<dt>
								<strong>', $txt['RssFeed_feed_enabled'], '</strong>
							</dt>
							<dd>
								<input class="check" type="checkbox" name="feed_enabled" value="1"', !empty($context['rss_feed']['enabled']) ? ' checked="checked"' : '', ' />
							</dd>
							<dt>
								<strong>', $txt['RssFeed_feed_full_article'], '</strong>
							</dt>
							<dd>
								<input onchange="refreshOptions();" class="check" type="checkbox" id="feed_full" name="feed_full" value="1"', !empty($context['rss_feed']['getfull']) ? ' checked="checked"' : '', ' />
							</dd>
							<dt id="feed_regex">
								<strong', (isset($context['feed_errors']['feed_regex'])) ? ' class="error"' : '', '>', $txt['RssFeed_feed_regex'], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_regex_desc'], '</span>
							</dt>
							<dd id="feed_regex2">
										<input type="text" size="50" name="feed_regex" value="', !empty($context['rss_feed']['regex']) ? $context['rss_feed']['regex'] : '', '" />
							</dd>
							<dt>
								<strong>', $txt['RssFeed_feed_locked'], '</strong>
							</dt>
							<dd>
								<input class="check" type="checkbox" name="feed_locked" value="1"', !empty($context['rss_feed']['locked']) ? ' checked="checked"' : '', ' />
							</dd>';
	if ($modSettings['postmod_active'])
		echo '
							<dt>
								<strong>', $txt['RssFeed_feed_approve'], '</strong>
							</dt>
							<dd>
								<input class="check" type="checkbox" name="feed_approve" value="1"', !empty($context['rss_feed']['approve']) ? ' checked="checked"' : '', ' />
							</dd>';
	echo '
							<dt>
								<strong>', $txt['RssFeed_feed_singletopic'], '</strong>
							</dt>
							<dd>
								<input class="check" type="checkbox" name="feed_singletopic" value="1"', !empty($context['rss_feed']['singletopic']) ? ' checked="checked"' : '', ' />
							</dd>
							<dt>
								<strong>', $txt['RssFeed_feed_footer'], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_footer_desc'], '</span>
							</dt>
							<dd>
								<textarea name="feed_footer" style="width: 300px; height: 100px; rows="4" cols="20">', !empty($context['rss_feed']['footer']) ? $context['rss_feed']['footer'] : '', '</textarea>
							</dd>
						</dl>
						<p class="confirm_buttons">
							<input class="button_submit" type="submit" name="save" value="', $txt['save'], '" />
							<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />',
							!empty($_REQUEST['feed']) ? '<input type="hidden" name="feed_id" value="' . $_REQUEST['feed'] . '" />' : '', '
						</p>
					</div>
					<span class="lowerframe"><span></span></span>
				</form>
				<script language="JavaScript" type="text/javascript" src="', $settings['default_theme_url'], '/scripts/suggest.js?rc2"></script>
				<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
					var oPosterSuggest = new smc_AutoSuggest({
						sSelf: \'oPosterSuggest\',
						sSessionId: \'', $context['session_id'], '\',
						sSessionVar: \'', $context['session_var'], '\',
						sSuggestId: \'feed_poster\',
						sControlId: \'feed_poster\',
						sSearchType: \'member\',
						bItemList: false,
					});
				// ]]></script>';

	// Javascript for deciding what to show.
	echo '
				<script language="JavaScript" type="text/javascript"><!-- // --><![CDATA[
					function refreshOptions()
					{
						var showRegex = document.getElementById("feed_full").checked;

						// What to show?
						document.getElementById("feed_regex").style.display = showRegex ? "" : "none";
						document.getElementById("feed_regex2").style.display = showRegex ? "" : "none";
					}
					refreshOptions();

					// Start with message icons - and any missing from this theme.
					var icon_urls = {';
	foreach ($context['icons'] as $icon)
		echo '
						"', $icon['value'], '": "', $icon['url'], '"', $icon['is_last'] ? '' : ',';
	echo '
					};';

	// The actual message icon selector.
	echo '
					function showimage()
					{
						document.images.icons.src = icon_urls[document.forms.postmodify.icon.options[document.forms.postmodify.icon.selectedIndex].value];
					}
				// ]]></script>';
}