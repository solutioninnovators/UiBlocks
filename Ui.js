/**
 * If your project needs to support for IE, include the polyfill.io service before this script:
 *
 * <script src="https://cdn.polyfill.io/v2/polyfill.min.js"></script>
 */

var UiBlocks = {
	/**
	 * Reload an entire UI block via AJAX
	 *
	 * Usage:
	 *
	 * UiBlocks.reload(document.querySelector('.ui_myUiId'), {key1: 'value', key2: 'value'}, alternateUrl);
	 *
	 * Triggers a ui-reloaded event on the block after reload is complete
	 *
	 * @param ui - The javascript element or jQuery object of the UI block (wrapper div)
	 * @param extraParams - Any extra data that you want to pass via get
	 * @param alternateUrl - Optionally specify an entirely different url (other than the current) to use for the reload
	 * @param animate - Whether the loading transition should be animated. boolean true|false
	 * @returns promise
	 * @todo: store and make sure the id gets added back to the reloaded ui if it doesn't exist?
	 * @todo: Update to support options array in place of extraParams, like Ui.jquery.js
	 */
	reload: function(ui, extraParams, alternateUrl, animate) {
		var ui = ui instanceof jQuery ? ui[0] : ui; // Convert jQuery objects to plain js elements
		if (animate == null) animate = true;

		ui.dispatchEvent(new CustomEvent('ui-reloading')); // Trigger a ui-reloading event when the ui begins to reload

		if (animate) {
			ui.style.pointerEvents = 'none';
			ui.animate([{opacity: 1}, {opacity: 0.5}], 200);
			ui.style.opacity = 0.5;
		}

		var result = UiBlocks.ajax(ui, 'reload', extraParams, 'get', alternateUrl);

		result.then(function (data) {
			// Update view

			var tempDiv = document.createElement('div');
			tempDiv.innerHTML = data.view;
			var newView = tempDiv.firstChild;

			ui.replaceWith(newView);

			if (animate) {
				newView.animate([{opacity: 0.5}, {opacity: 1}], 200);
			}

			window.dispatchEvent(new Event('resize')); // Allow other js listening to the resize event to recalculate in case the layout has changed
			newView.dispatchEvent(new CustomEvent('ui-reloaded')); // Trigger a ui-reloaded event when the ui is finished reloading
			newView.dispatchEvent(new CustomEvent('reloaded')); // Trigger a reloaded event when the ui is reloaded @deprecated
		},
		function (xhr, textStatus, errorThrown) {
			console.log('Error: ' + textStatus + ' ' + errorThrown); // Log error in console
		});

		return result;
	},

	/**
	 * Call any method of a UI block via ajax (the function name must begin with "ajax_")
	 * Leave off the "ajax_" prefix when specifying the function name
	 *
	 * Usage:
	 *
	 * UiBlocks.ajax(document.querySelector('.ui_myUiId'), 'myFunctionName', {key1: 'value', key2: 'value'}, 'post', alternateUrl).then(function(data) {
	 * 	// Success callback
	 * },
	 * function(data) {
	 * 	// Failure callback
	 * });
	 *
	 * @param ui - The javascript element or jQuery object of the UI block (wrapper div)
	 * @param ajaxFunctionName - The name of the function you want to call (Leave off the "ajax_" prefix when specifying the function name)
	 * @param extraParams - object containing any extra data that you want to pass via post or get todo: Also accept a query string
	 * @param method - Whether to submit the data as "post" or "get" (default: "post")
	 * @param alternateUrl - Optionally specify an entirely different url (other than the current) to submit to. By default, the current url with all query parameters will be used, which is almost always what you want.
     * @returns promise
	 * @todo: Update to support options array in place of extraParams, like Ui.jquery.js
     */
	ajax: function(ui, ajaxFunctionName, extraParams, method, alternateUrl) {
		var ui = ui instanceof jQuery ? ui[0] : ui; // Convert jQuery objects to plain js elements
		if (!method) method = 'post';
		if (!alternateUrl) alternateUrl = '';

		var urlParams = alternateUrl ? new URLSearchParams() : new URLSearchParams(window.location.search);
		var body = null;
		var fetchOptions = null;

		if(method === 'get') {
			urlParams.set('ui', ui.getAttribute('data-ui-path'));
			urlParams.set('ajax', ajaxFunctionName);
			if(extraParams) {
				// Add any extra parameters to the query string
				for (var key in extraParams) {
					urlParams.set(key, extraParams[key]);
				}
			}

			fetchOptions = {
				method: 'GET',
				credentials: 'same-origin'
			}
		}
		else { // post
			var postParams = new URLSearchParams();
			postParams.set('ui', ui.getAttribute('data-ui-path'));
			postParams.set('ajax', ajaxFunctionName);
			if(extraParams) {
				// Add any extra parameters to the post body
				for (var key in extraParams) {
					postParams.set(key, extraParams[key]);
				}
			}

			fetchOptions = {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded'
				},
				body: postParams.toString()
			}
		}

		var url = alternateUrl + '?' + urlParams.toString();

		return fetch(url, fetchOptions).then(function(response) {
			return response.json();
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


if(typeof jQuery != 'undefined') {
	$(function () {
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
		$('body').on('reload ui-reload', '.ui', function (e, extraParams, alternateUrl) {
			e.stopPropagation(); // Only call for the element that 'reload' was called on - do not bubble up to other .ui elements
			UiBlocks.reload($(this), extraParams, alternateUrl);
		});
	});
}