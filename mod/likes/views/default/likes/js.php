<?php
/**
 * Likes JavaScript extension for elgg.js
 */
?>

elgg.provide('elgg.likes');

elgg.likes.init = function() {
	$(document).ready(function() {
		$(".elgg-likes-submit-add").live('click', elgg.likes.submitLikesAdd);
		$(".elgg-likes-submit-delete").live('click', elgg.likes.submitLikesDelete);
	});
};

/**
 * Repositions the likes popup
 *
 * @param {String} hook    'getOptions'
 * @param {String} type    'ui.popup'
 * @param {Object} params  An array of info about the target and source.
 * @param {Object} options Options to pass to
 *
 * @return {Object}
 */
elgg.ui.likesPopupHandler = function(hook, type, params, options) {
	if (params.target.hasClass('elgg-likes')) {
		options.my = 'right bottom';
		options.at = 'left top';
		return options;
	}
	return null;
};

/**
 * ajax call to add like
 *
 * @param {Object} event
 *
 * @return void
 */
elgg.likes.submitLikesAdd = function(event) {
	$.blockUI();
	var $submit = $(this).closest(".elgg-likes-submit-add"); 
	var $url = $submit.attr("href");
	elgg.assertTypeOf('string', $url);
	
	var $guid_string = $url.match(/guid=[0-9]+/);
	$guid_string = $guid_string[0].match(/[0-9]+/);

	if ($guid_string[0] != "") {
	var $guid_str = "#likes-";
	$guid_str = $guid_str.concat($guid_string[0]);
	elgg.action($url, {
		success: function(rObj) {
			//$(this).removeClass("elgg-icon-thumbs-up").addClass("elgg-icon-thumbs-down");
			var $likers = $($guid_str).find(".elgg-list-annotation");
			//alert(rObj.output);
			if ($likers && rObj.output) {
				var who_like_a = 'a[href=\"' + $guid_str + '\"]';
				var likes_str = $(who_like_a).text();
				var likes_num = likes_str.match(/[0-9]+/);
				likes_num = parseInt(likes_num[0]);
				likes_num++;
				like_str = likes_str.replace(/[0-9]+/, likes_num);
				$(who_like_a).html(like_str);

				$likers.prepend(rObj.output);

				var $add_url = $submit.attr("href");
				$delete_url = $add_url.replace(/add/, "delete");
				$submit.attr("href", $delete_url);
				$submit.attr("title", "no more like this");
				$submit.toggleClass("elgg-likes-submit-add elgg-likes-submit-delete");
				$submit.children("span").toggleClass("elgg-icon-thumbs-up elgg-icon-thumbs-down");
			}
		},
		error:function(xhr, status, what) {
			alert("Ops, " + status + "happened and operation failed");
			}
	});

	event.preventDefault();
	}
};

/**
 * ajax call to remove like
 *
 * @param {Object} event
 *
 * @return void
 */
elgg.likes.submitLikesDelete = function(event) {
	$.blockUI();
	var $submit = $(this).closest(".elgg-likes-submit-delete"); 
	var $url = $submit.attr("href");
	elgg.assertTypeOf('string', $url);

	var $guid_string = $url.match(/guid=[0-9]+/);
	$guid_string = $guid_string[0].match(/[0-9]+/);

	if ($guid_string[0] != "") {

	var $guid_str = "#likes-";
	$guid_str = $guid_str.concat($guid_string[0]);

	elgg.action($url, {
		success: function(rObj) {
			//alert(rObj.output);
			if (rObj.output.id > 0) {

				var who_like_a = 'a[href=\"' + $guid_str + '\"]';
				var likes_str = $(who_like_a).text();
				var likes_num = likes_str.match(/[0-9]+/);
				likes_num = parseInt(likes_num[0]);
				if (--likes_num < 0)
					likes_num = 0;
				like_str = likes_str.replace(/[0-9]+/, likes_num);
				$(who_like_a).html(like_str);

				var $id_str = "#item-annotation-";
				var $id = rObj.output.id;
				$id_str = $id_str.concat($id);
				$($id_str).remove();

				var $delete_url = $submit.attr("href");
				var $add_url = $delete_url.replace(/delete/, "add");
				$submit.attr("href", $add_url);
				$submit.attr("title", "like this");
				$submit.toggleClass("elgg-likes-submit-delete elgg-likes-submit-add");
				$submit.children("span").toggleClass("elgg-icon-thumbs-down elgg-icon-thumbs-up");
			}
		},
		error:function(xhr, status, what) {
			alert("Ops, " + status + "happened and operation failed");
		}
	});

	event.preventDefault();
	}
};

elgg.register_hook_handler('init', 'system', elgg.likes.init);
elgg.register_hook_handler('getOptions', 'ui.popup', elgg.ui.likesPopupHandler);
