// Add this function somewhere in your JS to make reloading blocks a piece of cake

$(function() {
	/**
	 * Reload an entire UI block via AJAX by triggering a 'reload' event on its .ui wrapper
	 *
	 * Usage: $('.ui_myUiId').trigger('reload');
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
		if(!extraQueryParams) extraQueryParams = '';

		if(typeof extraQueryParams === 'object') {
			extraQueryParams = $.param(extraQueryParams);
		}

		if(extraQueryParams) {
			extraQueryParams = "&" + extraQueryParams;
		}

		var $ui = $(this); // Just the element that 'reload' was called on
		// @todo: store and make sure the id gets added back to the reloaded ui if it doesn't exist?
		$ui.animate({opacity: 0.5}, 300);

        $.ajax({
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
    });

});