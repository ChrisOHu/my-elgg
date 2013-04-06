<?php
/**
 * The wire's JavaScript
 */

$site_url = elgg_get_site_url();

?>

elgg.provide('elgg.thewire');

elgg.thewire.init = function() {
	$("#thewire-textarea").attr('placeholder', 'say something...');
	$("#thewire-textarea").keydown(function() {
		elgg.thewire.textCounter(this, $("#thewire-characters-remaining span"), 140);
	});
	$("#thewire-textarea").keyup(function() {
		elgg.thewire.textCounter(this, $("#thewire-characters-remaining span"), 140);
	});

	$(".thewire-previous").click(elgg.thewire.viewPrevious);
	$("#thewire-submit-button").click(elgg.thewire.submitWire);
};

/**
 * Update the number of characters left with every keystroke
 *
 * @param {Object}  textarea
 * @param {Object}  status
 * @param {integer} limit
 * @return integer
 */
elgg.thewire.textCounter = function(textarea, status, limit) {

	var remaining_chars = limit - $(textarea).val().length;
	status.html(remaining_chars);

	if (remaining_chars < 0) {
		status.parent().addClass("thewire-characters-remaining-warning");
		$("#thewire-submit-button").attr('disabled', 'disabled');
		$("#thewire-submit-button").addClass('elgg-state-disabled');
	} else {
		status.parent().removeClass("thewire-characters-remaining-warning");
		$("#thewire-submit-button").removeAttr('disabled', 'disabled');
		$("#thewire-submit-button").removeClass('elgg-state-disabled');
	}

	return remaining_chars;
};

/**
 * Display the previous wire post
 *
 * Makes Ajax call to load the html and handles changing the previous link
 *
 * @param {Object} event
 * @return void
 */
elgg.thewire.viewPrevious = function(event) {
	var $link = $(this);
	var postGuid = $link.attr("href").split("/").pop();
	var $previousDiv = $("#thewire-previous-" + postGuid);

	if ($link.html() == elgg.echo('thewire:hide')) {
		$link.html(elgg.echo('thewire:previous'));
		$link.attr("title", elgg.echo('thewire:previous:help'));
		$previousDiv.slideUp(400);
	} else {
		$link.html(elgg.echo('thewire:hide'));
		$link.attr("title", elgg.echo('thewire:hide:help'));
		
		$.ajax({type: "GET",
			url: elgg.config.wwwroot + "ajax/view/thewire/previous",
			dataType: "html",
			cache: false,
			data: {guid: postGuid},
			success: function(htmlData) {
				if (htmlData.length > 0) {
					$previousDiv.html(htmlData);
					$previousDiv.slideDown(600);
				}
			}
		});

	}

	event.preventDefault();
};

/**
 * submit wire post through ajax
 * @param {Object} event
 * @return void
 */
elgg.thewire.submitWire = function(event) {
	var $wireform = $(this).closest(".elgg-form-thewire-add");
	if (!$wireform)
		return;

	var $url = $wireform.attr("action");
	var $__elgg_token = $wireform.find('input[name="__elgg_token"]').attr("value");
	var $__elgg_ts = $wireform.find('input[name="__elgg_ts"]').attr("value");
	var $wirebody = $wireform.find('textarea[name="body"]').val();

	elgg.assertTypeOf('string', $url);
	
	$("#thewire-submit-button").attr('disabled', 'disabled');
	$("#thewire-submit-button").addClass('elgg-state-disabled');
	elgg.action($url, {
		data: {
			__elgg_token: $__elgg_token,
			__elgg_ts: $__elgg_ts,
			body: $wirebody
		},
		success: function(rData) {
			$("#thewire-submit-button").removeAttr('disabled', 'disabled');
			$("#thewire-submit-button").removeClass('elgg-state-disabled');
			$("#thewire-textarea").val("");
			$("#thewire-textarea").attr('placeholder', 'say something...');
			elgg.thewire.textCounter("#thewire-textarea", $("#thewire-characters-remaining span"), 140);
			//alert(rData.output);
			if (rData.output != "")
			{
				$(".elgg-list-river").prepend(rData.output);
			}
		},
		error: function(xhr, status, what) {
			alert("Ops, " + status + " happened and operation failed");
			$("#thewire-submit-button").removeAttr('disabled', 'disabled');
			$("#thewire-submit-button").removeClass('elgg-state-disabled');
			$("#thewire-textarea").val("");
			$("#thewire-textarea").attr('placeholder', 'say something...');
			elgg.thewire.textCounter("#thewire-textarea", $("#thewire-characters-remaining span"), 140);
		}
	});

	event.preventDefault();
};
elgg.register_hook_handler('init', 'system', elgg.thewire.init);
