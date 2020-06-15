var UiBlocks = {
	/**
	 * Reload an entire UI block via AJAX
	 *
	 * Usage:
	 *
	 * UiBlocks.reload($('.ui_myUiId'), {key1: 'value', key2: 'value'}, alternateUrl);
	 *
	 * Triggers a ui-reloaded event on the block after reload is complete
	 *
	 * @return promise
	 */
	reload: function(ui, extraQueryParams, alternateUrl, animate) {
		var $ui = ui instanceof jQuery ? ui : $(ui);
		if (animate == null) animate = true;

		$ui.trigger('ui-reloading'); // Trigger an event when the ui begins reload

		// @todo: store and make sure the id gets added back to the reloaded ui if it doesn't exist?

		if (animate) {
			$ui.css({'pointer-events': 'none'}).animate({opacity: 0.5}, 300);
		}

		UiBlocks.ajax(ui, 'reload', extraQueryParams, 'get', alternateUrl).then(function (data) {
			// Update view
			var $newView = $(data.view);

			$ui.replaceWith($newView);

			if (animate) {
				$newView.css({opacity: 0.5}).animate({opacity: 1}, 300);
			}

			$(window).trigger('resize'); // Allow other js listening to the resize event to recalculate in case the layout has changed
			$newView.trigger('ui-reloaded'); // Trigger a reloaded event when the ui is reloaded
			$newView.trigger('reloaded'); // Trigger a reloaded event when the ui is reloaded @deprecated
		},
		function (xhr, textStatus, errorThrown) {
			console.log('Error: ' + textStatus + ' ' + errorThrown); // Log error in console
		});
	},

	/**
	 * Call any method of a UI block via ajax (the function name must begin with "ajax_")
	 * Leave off the "ajax_" prefix when specifying the function name
	 *
	 * Usage:
	 *
	 * UiBlocks.ajax($('.ui_myUiId'), 'myFunctionName', {key1: 'value', key2: 'value'}, 'post', alternateUrl);
	 *
	 * @param ui - The javascript element or jQuery object of the UI block (wrapper div)
	 * @param ajaxFunctionName - The name of the function you want to call (Leave off the "ajax_" prefix when specifying the function name)
	 * @param extraQueryParams - Any data that you want to pass via post or get
	 * @param type - Whether to submit the data as "post" or "get" (default: "post")
	 * @param alternateUrl - Optionally specify an entirely different url (other than the current) to submit to
     * @returns promise
     */
	ajax: function(ui, ajaxFunctionName, extraQueryParams, type, alternateUrl) {
		var $ui = ui instanceof jQuery ? ui : $(ui);
		if (!type) type = 'post';

		if (!extraQueryParams) extraQueryParams = '';

		if (typeof extraQueryParams === 'object') {
			extraQueryParams = $.param(extraQueryParams);
		}

		if (extraQueryParams) {
			extraQueryParams = "&" + extraQueryParams;
		}

		return $.ajax({
			type: type,
			url: alternateUrl,
			dataType: 'json',
			data: "ui=" + $ui.attr('data-ui-path') + "&ajax=" + ajaxFunctionName + extraQueryParams
		});
	},

	/**
	 * Function to delay ajax call, i.e. when using keyup
	 * From https://remysharp.com/2010/07/21/throttling-function-calls
	 */
	debounce: function(fn, delay) {
	    var timer = null;
	    return function (event) {
	        // This prevents the tab and arrow keys from triggering keyup
	        var code = event.which;
	        // keycode 9 = tab, 37-40 = arrow keys
	        if(code == 9 || code == 37 || code == 38 || code == 39 || code == 40) {
	            return;
	        }

	        var context = this, args = arguments;
	        clearTimeout(timer);
	        timer = setTimeout(function () {
	            fn.apply(context, args);
	        }, delay);
	    };
	}
};

var UIBlocks = UiBlocks; // Allow either uppercase or lowercase "i" when referencing the object

$(function() {
	/**
	 * Alternate method of calling UiBlocks.reload() via triggering an event. Does not have a return value.
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
	 * You may also specify an entirely different url (other than the current) to submit to:
	 * $('.ui_myUiId').trigger('reload', [{}, alternateUrl] );
	 *
     */
	$('body').on('reload ui-reload', '.ui', function(e, extraQueryParams, alternateUrl) {
		e.stopPropagation(); // Only call for the element that 'reload' was called on - do not bubble up to other .ui elements
		UiBlocks.reload($(this), extraQueryParams, alternateUrl);
    });
});