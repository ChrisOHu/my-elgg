<?php
/**
 * A nice img-block for user to post wires.
 *
 * @uses $vars['owner_entity']
 */

$owner = $vars['owner_entity'];

echo elgg_view('page/components/image_block', array(
	'image' => elgg_view_entity_icon($owner, 'small'), 
	'image_alt' => elgg_view_icon('tag'),
	'body' => elgg_view_form('thewire/add'),
	'class' => "elgg-owner-horn",
));

?>
