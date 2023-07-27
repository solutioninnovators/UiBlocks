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
	 * @param ui - The javascript element or jQuery object of the UI block (wrapper div)
	 * @param extraParams - Any extra data that you want to pass via get
	 * @param alternateUrl - Optionally specify an entirely different url (other than the current) to use for the reload
	 * @param animate - Whether the loading transition should be animated. boolean true|false
	 * @returns promise
	 * @todo: store and make sure the id gets added back to the reloaded ui if it doesn't exist?
	 */
	reload: function(ui, extraParams, alternateUrl, animate) {
		var defaultOptions = {
			extraParams: undefined,
			alternateUrl: undefined,
			animate: true,
		};

		// Determine if the extraParams parameter is actually an options object by checking if one of its keys is in defaultOptions
		var userDefinedOptions = {};
		var extraParamsAreOptions = false;
		if(extraParams !== undefined && typeof extraParams === 'object') {
			var firstExtraParamsKey = Object.keys(extraParams)[0];
			if(firstExtraParamsKey in defaultOptions) {
				userDefinedOptions = extraParams;
				extraParamsAreOptions = true;
			}
		}
		var options = $.extend({}, defaultOptions, userDefinedOptions);
		if(!extraParamsAreOptions) options.extraParams = extraParams;
		if(alternateUrl !== undefined) options.alternateUrl = alternateUrl;
		if(animate !== undefined) options.animate = animate;

		var $ui = ui instanceof jQuery ? ui : $(ui);

		$ui.trigger('ui-reloading'); // Trigger an event when the ui begins reload

		var reloadRequestTime = new Date().getTime();
		$ui.attr('data-ui-last-reload-request', reloadRequestTime);

		if (options.animate) {
			$ui.css({'pointer-events': 'none'}).animate({opacity: 0.5}, 300);
		}
		if (options.animate == 'spinner') {
			if(!$ui.find('.ui-spinner').length) {
				$ui.css('position', 'relative')
				var $spinner = $("<div class='ui-spinner' style='margin: auto; position: absolute; top: 0; left: 0; bottom: 0; right: 0; font-size: 2rem; width: 2rem; height: 2rem; display: none;'><i class='fa fa-circle-o-notch fa-spin'></i></div>");
				$ui.append($spinner);
				$spinner.fadeIn();
			}
		}

		var result = UiBlocks.ajax(ui, 'reload', options.extraParams, 'get', options.alternateUrl);

		result.then(function(data) {
			if(reloadRequestTime == $ui.attr('data-ui-last-reload-request')) {
				// Update view
				var $newView = $(data.view);

				$ui.replaceWith($newView);

				if (options.animate) {
					$newView.css({opacity: 0.5}).animate({opacity: 1}, 300);
				}

				$(window).trigger('resize'); // Allow other js listening to the resize event to recalculate in case the layout has changed
				$newView.trigger('ui-reloaded'); // Trigger a reloaded event when the ui is reloaded
				$newView.trigger('reloaded'); // Trigger a reloaded event when the ui is reloaded @deprecated
			}
			else {
				console.log('AJAX response ignored because it was obsolete.');
			}
		},
		function(xhr, textStatus, errorThrown) {
			// If a 404 error occurred, just empty out the contents of the block and leave the wrapper.
			if(xhr.status == 404) {
				console.log('Could not locate UI Block during reload. It may no longer be present on the page.')
				$ui.empty();
			}
			// Otherwise, just stop the reload and leave the old data.
			else {
				console.log('Reload request failed.')
				if (xhr.responseText) {
					console.log(JSON.parse(xhr.responseText).message)
				}
			}

			if (options.animate) {
				$ui.css({opacity: 0.5, 'pointer-events': 'initial'}).animate({opacity: 1}, 300);
			}
			if (options.animate == 'spinner') {
				$ui.find('.ui-spinner').fadeOut();
			}

			$(window).trigger('resize'); // Allow other js listening to the resize event to recalculate in case the layout has changed
			$ui.trigger('ui-reloaded'); // Trigger a reloaded event when the ui is reloaded
			$ui.trigger('reloaded'); // Trigger a reloaded event when the ui is reloaded @deprecated
		});

		return result;
	},

	/**
	 * Call any method of a UI block via ajax (the function name must begin with "ajax_")
	 * Leave off the "ajax_" prefix when specifying the function name
	 *
	 * Usage:
	 *
	 * UiBlocks.ajax($('.ui_myUiId'), 'myFunctionName', {key1: 'value', key2: 'value'}, 'post', alternateUrl);
	 *
	 * @param object ui - The javascript element or jQuery object of the UI block (wrapper div)
	 * @param string ajaxFunctionName - The name of the function you want to call (Leave off the "ajax_" prefix when specifying the function name)
	 * @param object|string extraParams - object containing any extra data that you want to pass via post or get. Also accepts an options array (see defaultOptions below)
	 * @param method - Whether to submit the data as "post" or "get" (default: "post")
	 * @param alternateUrl - Optionally specify an entirely different url (other than the current) to submit to. By default, the current url with all query parameters will be used, which is almost always what you want.
     * @returns promise
     */
	ajax: function(ui, ajaxFunctionName, extraParams, method, alternateUrl) {
		var $ui = ui instanceof jQuery ? ui : $(ui);

		var defaultOptions = {
			extraParams: undefined,
			method: 'post',
			alternateUrl: $ui.closest('.ui[data-ui-url]').attr('data-ui-url'), // Use url from the closest UI block with the data-ui-url attribute set, otherwise use current page url todo: Ui.js needs this feature added as well
		};

		// Determine if the extraParams parameter is actually an options object by checking if one of its keys is in defaultOptions
		var userDefinedOptions = {};
		var extraParamsAreOptions = false;
		if(extraParams !== undefined && typeof extraParams === 'object') {
			var firstExtraParamsKey = Object.keys(extraParams)[0];
			if(firstExtraParamsKey in defaultOptions) {
				userDefinedOptions = extraParams;
				extraParamsAreOptions = true;
			}
		}
		var options = $.extend({}, defaultOptions, userDefinedOptions);
		if(!extraParamsAreOptions) options.extraParams = extraParams;
		if(method !== undefined) options.method = method;
		if(alternateUrl !== undefined) options.alternateUrl = alternateUrl;

		if (!options.extraParams) options.extraParams = '';

		if (typeof options.extraParams === 'object') {
			options.extraParams = $.param(options.extraParams);
		}

		if (options.extraParams) {
			options.extraParams = "&" + options.extraParams;
		}

		return $.ajax({
			type: options.method,
			url: options.alternateUrl,
			dataType: 'json',
			data: "ui=" + $ui.attr('data-ui-path') + "&ajax=" + ajaxFunctionName + options.extraParams
		})
		.fail(function(xhr, textStatus, errorThrown) {
			console.log('AJAX request failed.')
			if(xhr.responseText) {
				console.log(JSON.parse(xhr.responseText).message)
			}
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
	$('body').on('reload ui-reload', '.ui', function(e, extraParams, alternateUrl) {
		e.stopPropagation(); // Only call for the element that 'reload' was called on - do not bubble up to other .ui elements
		UiBlocks.reload($(this), extraParams, alternateUrl);
    });
});