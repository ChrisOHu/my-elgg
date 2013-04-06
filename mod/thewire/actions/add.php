<?php
/**
 * Action for adding a wire post
 * 
 */

// don't filter since we strip and filter escapes some characters
$body = get_input('body', '', false);

$access_id = ACCESS_PUBLIC;
$method = 'site';
$parent_guid = (int) get_input('parent_guid');

// make sure the post isn't blank
if (empty($body)) {
	register_error(elgg_echo("thewire:blank"));
	forward(REFERER);
}

$ids = thewire_save_post($body, elgg_get_logged_in_user_guid(), $access_id, $parent_guid, $method);
$guid = $ids["post-id"];
$rid = $ids["river-id"];
if (!$guid || !$rid) {
	register_error(elgg_echo("thewire:error"));
	forward(REFERER);
}

// Send response to original poster if not already registered to receive notification
if ($parent_guid) {
	thewire_send_response_notification($guid, $parent_guid, $user);
	$parent = get_entity($parent_guid);
	forward("thewire/thread/$parent->wire_thread");
}

system_message(elgg_echo("thewire:posted"));
if (elgg_is_xhr())
{
	$rvitem_array = elgg_get_river(array('id' => $rid));
	$rvitem = $rvitem_array[0];
	if (gettype($rvitem) == "object")
	{
		$vars = array();
		$litem = elgg_view_list_item($rvitem, $vars);
	}
	else 
		$litem = null;
	if ($litem)
	{
		if (elgg_instanceof($rvitem))
			$id = "elgg-{$rvitem->getType()}-{$rvitem->getGUID()}";
		else
			$id = "item-{$rvitem->getType()}-{$rvitem->id}";

		$item_class = "elgg-item {$vars['item-class']}";
		echo "<li id=\"$id\" class=\"$item_class\">$litem</li>";
	}
}
forward(REFERER);
