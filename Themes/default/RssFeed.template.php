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

	// Post a message if there is any.
	if (!empty($context['feed_message']))
		foreach ($context['feed_message'] as $action => $message)
			echo '
				<div class="', $message ,'box">
					', $txt['RssFeed_feed_'. $action .'_'. $message] ,'
				</div>';

	template_show_list('rss_feeder_list');
}

function template_rss_feeder_add()
{
	global $context, $txt, $settings, $scripturl, $modSettings;

	echo '
				<form name="postmodify" action="', $scripturl, '?action=admin;area=RssFeed;sa=add;do=save" method="post" accept-charset="ISO-8859-1">
					<div class="title_bar">
						<h3 class="titlebg">', $txt['RssFeed_feed_add'], '</h3>
					</div>
					<div class="roundframe">';

	if (!empty($context['errors']))
		echo '
						<div class="errorbox">
							', $txt['RssFeed_feed_add_error'], '
						</div>';

	echo '
						<dl class="settings">';

	foreach ($context['fields'] as $field => $params)
	{
		echo '
							<dt>
								<strong', (isset($context['errors'][$field])) ? ' class="error"' : '', '>', $txt['RssFeed_feed_'. $field], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_'. $field .'_desc'], '</span>
							</dt>
							<dd>';

		if ($params['type'] == 'text')
			echo'
								<input type="text" size="50" name="feed[', $field ,']" value="'. (!empty($context['feed'][$field]) ? $context['feed'][$field] : '') .'" id="feed_', $field ,'" />';

		else
			echo '
								<input type="checkbox" name="feed[', $field ,']" value="1"', !empty($context['rss_feed'][$field]) ? ' checked="checked"' : '', ' />';

		echo '
							</dd>';
	}

	if ($modSettings['postmod_active'])
		echo '
							<dt>
								<strong>', $txt['RssFeed_feed_approve'], '</strong>
							</dt>
							<dd>
								<input class="check" type="checkbox" name="feed[approve]" value="1"', !empty($context['feed']['approve']) ? ' checked="checked"' : '', ' />
							</dd>';

	echo '
							<dt>
								<strong>', $txt['message_icon'], '</strong>
							</dt>
							<dd>
								<select name="feed[icon]" id="icon" onchange="showimage()">';

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
								<select name="feed[id_board]">';

	foreach ($context['categories'] as $category)
	{
		echo '
									<optgroup label="', $category['name'], '">';
		foreach ($category['boards'] as $board)
			echo '
										<option value="', $board['id'], '"', (!empty($context['feed']['id_board']) && $context['feed']['id_board'] == $board['id']) ? ' selected="selected"' : '', '>', $board['child_level'] > 0 ? str_repeat('==', $board['child_level']-1) . '=&gt;' : '', $board['name'], '</option>';
		echo '
									</optgroup>';
	}

	echo '
								</select>
							</dd>
							<dt>
								<strong>', $txt['RssFeed_feed_full_article'], '</strong>
							</dt>
							<dd>
								<input onchange="refreshOptions();" class="check" type="checkbox" id="feed_full" name="feed[full]" value="1"', !empty($context['feed']['full']) ? ' checked="checked"' : '', ' />
							</dd>
							<dt id="feed_regex">
								<strong', (isset($context['errors']['regex'])) ? ' class="error"' : '', '>', $txt['RssFeed_feed_regex'], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_regex_desc'], '</span>
							</dt>
							<dd id="feed_regex2">
										<input type="text" size="50" name="feed[regex]" value="', !empty($context['feed']['regex']) ? $context['feed']['regex'] : '', '" />
							</dd>
							<dt>
								<strong>', $txt['RssFeed_feed_footer'], '</strong><br /><span class="smalltext">', $txt['RssFeed_feed_footer_desc'], '</span>
							</dt>
							<dd>
								<textarea name="feed[footer]" style="width: 300px; height: 100px; rows="4" cols="20">', !empty($context['feed']['footer']) ? $context['feed']['footer'] : '', '</textarea>
							</dd>
						</dl>
						<p class="confirm_buttons">
							<input class="button_submit" type="submit" name="save" value="', $txt['save'], '" />
							<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
						</p>
					</div>
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
