var UIBlocks = {
	/**
	 * Reload an entire UI block via AJAX
	 *
	 * Usage:
	 *
	 * UIBlocks.reload($('.ui_myUiId'), {key1: 'value', key2: 'value'}, url);
	 *
	 * Triggers a uib-reloaded event on the block after reload is complete
	 *
	 * @return promise
	 */
	reload: function($ui, extraQueryParams, url, animate) {
		if (!extraQueryParams) extraQueryParams = '';
		if (animate == null) animate = true;

		if (typeof extraQueryParams === 'object') {
			extraQueryParams = $.param(extraQueryParams);
		}

		if (extraQueryParams) {
			extraQueryParams = "&" + extraQueryParams;
		}

		// @todo: store and make sure the id gets added back to the reloaded ui if it doesn't exist?
		if (animate) {
			$ui.css({'pointer-events': 'none'}).animate({opacity: 0.5}, 300);
		}

		return $.ajax({
			type: 'get',
			url: url,
			dataType: 'json',
			data: "ui=" + $ui.attr('data-ui-path') + "&ajax=reload" + extraQueryParams,
			success: function (data) {
				// Update view]
				var $newView = $(data.view);

				$ui.replaceWith($newView);

				if (animate) {
					$newView.css({opacity: 0.5}).animate({opacity: 1}, 300);
				}

				$(window).trigger('resize'); // Allow other js listening to the resize event to recalculate in case the layout has changed
				$newView.trigger('uib-reloaded'); // Trigger a reloaded event when the ui is reloaded
				$newView.trigger('reloaded'); // Trigger a reloaded event when the ui is reloaded @deprecated
			},
			error: function (xhr, textStatus, errorThrown) {
				console.log('Error: ' + textStatus + ' ' + errorThrown); // Log error in console
			}
		});
	}
};

$(function() {
	/**
	 * Alternate method of calling reloadUI() via triggering an event. Does not have a return value.
	 *
	 * @deprecated
	 *
	 * Usage:
	 *
	 * $('.ui_myUiId').trigger('reload');
	 *
	 * Optionally, you may pass additional parameters to modify the ajax request's query string:
	 *
	 * $('.ui_myUiId').trigger('reload', [{key1: 'value', key2: 'value'}] );
	 *
	 * You may also specify an entirely different url to submit to:
	 * $('.ui_myUiId').trigger('reload', [{}, url] );
	 *
     */
	$('body').on('reload uib-reload', '.ui', function(e, extraQueryParams, url) {
		e.stopPropagation(); // Only call for the element that 'reload' was called on - do not bubble up to other .ui elements
		UIBlocks.reload($(this), extraQueryParams, url);
    });
});