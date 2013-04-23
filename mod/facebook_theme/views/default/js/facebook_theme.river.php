<?php
/*
 * river's javascript for facebook_theme
 */
?>

elgg.provide('elgg.facebook_theme.river');

elgg.facebook_theme.river.init = function() {
	$(".elgg-river-item-delete").live('click', elgg.facebook_theme.river.deleteItem);
};

/**
 * ajax call to delete a item from user's river
 */
elgg.facebook_theme.river.deleteItem = function(event) {
	$.blockUI();
	
	var $url = $(this).attr("href");
	elgg.assertTypeOf('string', $url);

	elgg.action($url, {
		success: function(rObj) {
			var $id = rObj.output.id;

			if ($id > 0) {
				$item = "#item-river-" + $id;
				$($item).remove();
			}
		},
		error: function(xhr, status, what) {
			alert("Ops, " + status + "happened and operation failed");
		}
	});

	event.preventDefault();
};	

elgg.register_hook_handler('init', 'system', elgg.facebook_theme.river.init);
