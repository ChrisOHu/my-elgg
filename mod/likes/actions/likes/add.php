<?php
/**
 * Elgg add like action
 *
 */

//stuff to be liked
$entity_guid = (int) get_input('guid');

//check to see if the user has already liked the item
if (elgg_annotation_exists($entity_guid, 'likes')) {
	system_message(elgg_echo("likes:alreadyliked"));
	forward(REFERER);
}
// Let's see if we can get an entity with the specified GUID
$entity = get_entity($entity_guid);
if (!$entity) {
	register_error(elgg_echo("likes:notfound"));
	forward(REFERER);
}

// limit likes through a plugin hook (to prevent liking your own content for example)
if (!$entity->canAnnotate(0, 'likes')) {
	// plugins should register the error message to explain why liking isn't allowed
	forward(REFERER);
}

$user = elgg_get_logged_in_user_entity();
$annotation = create_annotation($entity->guid,
								'likes',
								"likes",
								"",
								$user->guid,
								$entity->access_id);

// tell user annotation didn't work if that is the case
if (!$annotation) {
	register_error(elgg_echo("likes:failure"));
	forward(REFERER);
}

// notify if poster wasn't owner
if ($entity->owner_guid != $user->guid) {

	likes_notify_user($entity->getOwnerEntity(), $user, $entity);
}

system_message(elgg_echo("likes:likes"));

if (elgg_is_xhr()) {
	$like_item = elgg_get_annotation_from_id($annotation);
	$vars_l = array();
	$li = elgg_view_list_item($like_item, $vars_l);
	if ($li)
		echo "<li id=\"item-annotation-$annotation\" class=\"elgg-item\">$li</li>";  
	//elgg_view('likes/river_footer', array('object' => $entity));
}

// Forward back to the page where the user 'liked' the object
forward(REFERER);
