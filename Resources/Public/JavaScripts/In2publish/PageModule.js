/**
 * In2publish Page Module functions
 *
 * @params {jQuery} $
 * @class In2publishPageModule
 */
function In2publishPageModule($) {
	'use strict';

	/**
	 * This class
	 *
	 * @type {In2publishPageModule}
	 */
	var that = this;

	/**
	 * Container class name
	 *
	 * @type {string}
	 */
	this.workflowContainerClassName = 'in2publish__workflowcontainer';

	/**
	 * Remove this params on a redirect
	 *
	 * @type {string[]}
	 */
	this.removeParametersOnRedirect = [
		'workflowstate[justpublished]'
	];

	/**
	 * Initialize
	 *
	 * @returns {void}
	 */
	this.initialize = function() {
		this.addWorkflowContainerOpenListener();
		this.addWorkflowContainerCloseListener();
		this.addSubmitListener();
		this.addPublishListener();
	};

	/**
	 * Open/close container on click
	 *
	 * @returns {void}
	 */
	this.addWorkflowContainerOpenListener = function() {
		$('*[data-action-workflowopen]').click(function(e) {
			e.stopPropagation();
			openOrCloseWorkflowContainer($(this).data('action-workflowopen'));
		});
	};

	/**
	 * Close workflowcontainer
	 *
	 * @returns {void}
	 */
	this.addWorkflowContainerCloseListener = function() {
		$('body').click(function() {
			closeAllWorkflowContainers();
		});
		// Close - cross in container
		$('.in2publish__workflowcontainer__button-close').click(function() {
			closeAllWorkflowContainers();
		});
		// ESC key press
		$(document).keyup(function(e) {
			if (e.keyCode == 27) {
				closeAllWorkflowContainers();
			}
		});
		$('.' + that.workflowContainerClassName).click(function(e) {
			e.stopPropagation();
		});
	};

	/**
	 * Add listener to pagestate selector
	 *
	 * @returns {void}
	 */
	this.addSubmitListener = function() {
		$('*[data-workflow-container-submit]').click(function(e) {
			var $this = $(this);

			$this.addClass('sending');
			var recordIdentifier = $this.siblings('*[data-workflow-container-recordidentifier]').val();

			var params = {
				identifier: $('*[name="in2publish_pagestate[' + recordIdentifier + '][identifier]"]').val(),
				tableName: $('*[name="in2publish_pagestate[' + recordIdentifier + '][tableName]"]').val(),
				state: $('*[name="in2publish_pagestate[' + recordIdentifier + '][pagestate]"]:checked').val(),
				message: $('*[name="in2publish_pagestate[' + recordIdentifier + '][message]"]').val(),
				scheduledpublish: $('*[name="in2publish_pagestate[' + recordIdentifier + '][scheduledPublishDate]"]').val()
			};

			var paramsString = '';
			for (var key in params) {
				paramsString += '&workflowstate[' + key + ']=' + encodeURIComponent(params[key]);
			}

			$.ajax({
				type: 'POST',
				url: window.location.href,
				data: paramsString,
				success: function(data) {
					if (data == "reload") {
						window.location.href = cleanUriFromAnchors(getCurrentUri()) + '&workflowstate[justpublished]=1';
					} else {
						$this.closest('.in2publish__workflowcontainer').html(data);
					}
				},
				error: function() {
					alert('Error: Could not set workflow state for this page');
				}
			});
			e.preventDefault();
		});
	};

	/**
	 * Add listener to publish buttons in page and list module
	 *
	 * @returns {void}
	 */
	this.addPublishListener = function() {
		$('*[data-ajax-publish]').click(function(e) {
			var $this = $(this);
			var href = $this.prop('href');
			$this.addClass('sending');
			ajaxAndReload(href);
			e.preventDefault();
		});
	};

	/**
	 * ************ Internal ************
	 */

	/**
	 * Open or close workflow container
	 *
	 * @param {string} trigger could be "pages:123" to open element with data-action-workflowcontainer="pages:123"
	 * @returns {void}
	 */
	var openOrCloseWorkflowContainer = function(trigger) {
		var $workflowContainer = $('*[data-action-workflowcontainer="' + trigger + '"]');
		$workflowContainer.toggle().parent().toggle();

		/**
		 * Workarround to get the container visible in 7.6
		 * Class .table-fit sets a transform which has a strange side-effect and crops the popup
		 */
		$workflowContainer.closest('.table-fit').css('transform', 'none');
	};

	/**
	 * Close workflow container
	 *
	 * @returns {void}
	 */
	var closeAllWorkflowContainers = function() {
		$('.' + that.workflowContainerClassName).hide();
		$('.in2publish__workflowcontainer__lightbox').hide();
	};

	/**
	 * Reload current url with some params + reload page tree
	 *
	 * @param {object} params key:value
	 * @returns {void}
	 */
	var sendParamsAndReloadWithoutParams = function(params) {
		var paramsString = '';
		for (var key in params) {
			paramsString += '&workflowstate[' + key + ']=' + encodeURIComponent(params[key]);
		}
		ajaxAndReload(window.location.href, paramsString);
	};

	/**
	 * Send ajax request to a target and reload current page
	 *
	 * @param {string} href send ajax request to this target
	 * @param {string} paramsString optional parameters that should be send with
	 */
	var ajaxAndReload = function(href, paramsString) {
		$.ajax({
			type: 'POST',
			url: href,
			data: paramsString,
			success: function() {
				window.location.href = cleanUriFromAnchors(getCurrentUri()) + '&workflowstate[justpublished]=1';
			},
			error: function() {
				alert('Error: Publishing of this page failed');
			}
		});
	};

	/**
	 * Clean uri string from any anchors
	 * 		page.html?foo=bar#anchor => page.html?foo=bar
	 *
	 * @param uri
	 * @returns {*}
	 */
	var cleanUriFromAnchors = function(uri) {
		if (uri.indexOf('#') !== -1) {
			var parts = uri.split('#');
			return parts[0];
		}
		return uri;
	};

	/**
	 * Get current URI but remove in2publish params
	 *
	 * @returns {string}
	 */
	var getCurrentUri = function() {
		var uri = '';
		var uriParts = window.location.href.split(/[&?]+/);
		for (var i = 0; i < uriParts.length; i++) {
			var parameterParts = uriParts[i].split('=');
			if (that.removeParametersOnRedirect.indexOf(parameterParts[0]) === -1) {
				if (i > 0) {
					uri += (i === 1 ? '?' : '&');
				}
				uri += uriParts[i];
			}
		}
		return uri;
	};
}

var jQueryObject = new window.In2publishJquery();
jQueryObject.getJqueryAndCallInstance('In2publishPageModule', 'initialize');
jQueryObject.getJqueryAndCallInstance('In2publishOverall', 'initialize');
