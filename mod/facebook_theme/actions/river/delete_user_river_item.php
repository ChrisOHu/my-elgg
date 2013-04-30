<?php

$item_guid = (int) get_input('id');
$item = elgg_get_river(array('id' => $item_guid));
$item = $item[0];
if (!$item || !$item instanceof ElggRiverItem) {
	register_error(elgg_echo("river:delete:fail"));
	forward(REFERER);
}

$user_guid = elgg_get_logged_in_user_guid();
if ($user_guid <= 0) {
	register_error(elgg_echo("river:delete:fail"));
	forward(REFERER);
}

if (!elgg_delete_from_user_river($user_guid, $item)) {
	register_error(elgg_echo("river:delete:fail"));
	forward(REFERER);
}

system_message(elgg_echo("river:delete:success"));

if (elgg_is_xhr()) {
	echo json_encode(array('id' => $item_guid));
}

forward(REFERER);
