<?php
/**
 * $vars['by']: creator or type
 * $vars['entity']: user-entity
 */ 

$river_categories_menu = elgg_view_menu('river-categories', array(
	//'entity' => elgg_get_page_owner_entity(),
	'class' => 'elgg-menu-hz',
	'sort_by' => 'priority',
));

echo <<<HTML
<div class="elgg-river-categories">
	$river_categories_menu
</div>
HTML;

?>
