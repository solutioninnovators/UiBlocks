/**
 * Reload an entire UI block via AJAX
 *
 * Usage:
 *
 * reloadUI($('.ui_myUiId'), {key1: 'value', key2: 'value'}, url);
 *
 * Triggers a reloaded event on the block after reload is complete
 *
 * @return promise
 */
function reloadUI($ui, extraQueryParams, url) {
	if(!extraQueryParams) extraQueryParams = '';
	if(!url) url = '';

	if(typeof extraQueryParams === 'object') {
		extraQueryParams = $.param(extraQueryParams);
	}

	if(extraQueryParams) {
		extraQueryParams = "&" + extraQueryParams;
	}

	// @todo: store and make sure the id gets added back to the reloaded ui if it doesn't exist?
	$ui.animate({opacity: 0.5}, 300);

	return $.ajax({
		type: 'get',
		url: url,
		dataType: 'json',
		data: "ui=" + $ui.attr('data-ui-path') + "&ajax=reload" + extraQueryParams,
		success: function(data) {
			// Update view
			var $newView = $(data.view);

			$ui.replaceWith($newView);

			$newView.css({opacity: 0.5}).animate({opacity: 1}, 500);

			$(window).trigger('resize'); // Allow other js listening to the resize event to recalculate in case the layout has changed
			$newView.trigger('reloaded'); // Trigger a reloaded event when the ui is reloaded
		},
		error: function (xhr, textStatus, errorThrown) {
			console.log('Error: ' + textStatus + ' ' + errorThrown); // Log error in console
		}
	});
}

$(function() {
	/**
	 * Alternate method of calling reloadUI() via triggering an event. Does not have a return value.
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
	$('body').on('reload', '.ui', function(e, extraQueryParams, url) {
		e.stopPropagation(); // Only call for the element that 'reload' was called on - do not bubble up to other .ui elements
		reloadUI($(this), extraQueryParams, url);
    });
});