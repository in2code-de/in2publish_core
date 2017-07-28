/**
 * In2publish Module functions
 *
 * @params {jQuery} $
 * @class In2publishModule
 */
function In2publishModule($) {
	'use strict';

	/**
	 * This class
	 *
	 * @type {In2publishModule}
	 */
	var that = this;

	/**
	 * Filter is changed?
	 *
	 * @type {boolean}
	 */
	this.changedFilter = false;

	/**
	 * @type {boolean}
	 */
	this.addedFilter = false;

	/**
	 * @type {boolean}
	 */
	this.deletedFilter = false;

	/**
	 * @type {boolean}
	 */
	this.movedFilter = false;

	/**
	 * Initialize
	 *
	 * @returns {void}
	 */
	this.initialize = function() {
		that.addClassBodyTag();
		toggleDirtyPropertiesListContainerListener();
		that.setFilterForPageView();
		that.filterButtonsListener();
		that.messageListener();
		that.overlayListener();
		ajaxUriListener();
		workflowFilterListener();
	};

	/**
	 * Add class to body tag
	 *
	 * @returns {void}
	 */
	this.addClassBodyTag = function() {
		var dataModuleContainer = $('*[data-module]');
		if (dataModuleContainer !== undefined) {
			var moduleName = dataModuleContainer.data('module');
			$('body').addClass('in2publish-module-' + moduleName);
		}
	};

	/**
	 * Open/Close dirty properties container
	 *
	 * @returns {void}
	 */
	var toggleDirtyPropertiesListContainerListener = function() {
		$('*[data-action="opendirtypropertieslistcontainer"]').click(function() {
			var $containerDropdown = $(this).closest('.in2publish-stagelisting__item').find('.in2publish-stagelisting__dropdown:first');
			openOrCloseStageListingDropdownContainer($containerDropdown);

			var $containerMessages = $(this).closest('.in2publish-stagelisting__item').find('.in2publish-stagelisting__messages:first');
			openOrCloseStageListingMessagesContainer($containerMessages);
		});
	};

	/**
	 * Open or close .in2publish-stagelisting__dropdown
	 *
	 * @param $container
	 * @returns {void}
	 */
	var openOrCloseStageListingDropdownContainer = function($container) {
		if ($container.hasClass('in2publish-stagelisting__dropdown--close')) {
			$('.in2publish-stagelisting__dropdown--open')
				.removeClass('in2publish-stagelisting__dropdown--open')
				.addClass('in2publish-stagelisting__dropdown--close')
				.hide();
			$container
				.removeClass('in2publish-stagelisting__dropdown--close')
				.addClass('in2publish-stagelisting__dropdown--open')
				.show();
		} else {
			$container
				.removeClass('in2publish-stagelisting__dropdown--open')
				.addClass('in2publish-stagelisting__dropdown--close')
				.hide();
		}
	};

	/**
	 * Open or close .in2publish-stagelisting__messages
	 *
	 * @param $container
	 * @returns {void}
	 */
	var openOrCloseStageListingMessagesContainer = function($container) {
		if ($container.length > 0) {
			if ($container.hasClass('in2publish-stagelisting__messages--close')) {
				$('.in2publish-stagelisting__messages--open')
					.removeClass('in2publish-stagelisting__messages--open')
					.addClass('in2publish-stagelisting__messages--close')
					.hide();
				$container
					.removeClass('in2publish-stagelisting__messages--close')
					.addClass('in2publish-stagelisting__messages--open')
					.show();
			} else {
				$container
					.removeClass('in2publish-stagelisting__messages--open')
					.addClass('in2publish-stagelisting__messages--close')
					.hide();
			}
		}
	};

	/**
	 * Read target given in data-action-ajax-uri="index.php?id=123"
	 *
	 * Optional: data-action-uri="href" - Take uri from href argument. preventDefault will be used to stop default click
	 * Optional: data-action-ajax-result="div.classname" - If there is a result and it should be pasted into a container
	 * Optional: data-action-ajax-callback-done="abc" - If there should be a function called if AJAX is done
	 * Optional: data-action-ajax-callback-start="abc" - If there should be a function called if AJAX is started - preloader will not be used here
	 * Optional: data-action-ajax-once="true" - If ajax request should be fired only once.
	 * 		If already fired, data-action-ajax-once === 'done'
	 *
	 * @returns {void}
	 */
	var ajaxUriListener = function() {
		$('*[data-action-ajax-uri]').click(function(e) {
			var $this = $(this);
			var uri = $this.data('action-ajax-uri');
			if (uri === 'href') {
				uri = $this.prop('href');
				e.preventDefault();
			}
			var callbackDone = $this.data('action-ajax-callback-done');
			var callbackStart = $this.data('action-ajax-callback-start');
			var once = $this.data('action-ajax-once') === true;
			var container = $this.data('action-ajax-result');
			var filled = false;
			if (container !== undefined) {
				var $container = $(container);
				filled = $container.data('container-filled') === true;
			}

			if (!once || !filled) {
				$.ajax({
					url: uri,
					beforeSend: function() {
						if (callbackStart !== undefined) {
							that[callbackStart](uri, $container, $this);
						} else {
							showPreloader();
						}
					},
					complete: function() {
						hidePreloader();
					},
					success: function(data) {
						if (data && container !== undefined) {
							$container.html(data);
							$container.data('container-filled', true);
						}
						if (callbackDone !== undefined) {
							that[callbackDone](uri, $container, $this);
						}
						initializeOverallFunctions();
					}
				});
			}
		});
	};

	/**
	 * Callback before AJAX request for publishing with fireAndForget
	 *
	 * @param {string} uri not used
	 * @param {string} $container not used
	 * @param {jQuery} $element Clicked element not used
	 */
	this.publishingStart = function(uri, $container, $element) {
		$element.find('i').addClass('loadinganimation').addClass('sending');
	};

	/**
	 * Callback after AJAX request for publishing with fireAndForget
	 *
	 * @param {string} uri not used
	 * @param {string} $container not used
	 * @param {jQuery} $element Clicked element not used
	 */
	this.publishingDone = function(uri, $container, $element) {
		location.reload();
	};

	/**
	 * Callback after AJAX request for opening container
	 *
	 * @param {string} uri not used
	 * @param {jQuery} $container container where the AJAX data is pasted into
	 * @param {jQuery} $element Clicked element not used
	 */
	this.openorclosecontainers = function(uri, $container, $element) {
		openOrCloseStageListingDropdownContainer($container.find('.in2publish-stagelisting__dropdown'));
		openOrCloseStageListingMessagesContainer($container.find('.in2publish-stagelisting__messages'));
	};

	/**
	 * @returns {void}
	 */
	this.setFilterForPageView = function() {
		this.initializeFilterStatus();

		if (that.changedFilter || that.addedFilter || that.deletedFilter || that.movedFilter) {
			that.hidePages($('.in2publish-stagelisting__item--unchanged').parent());
			that.hideOrShowPages($('.in2publish-stagelisting__item--changed').parent(), that.changedFilter);
			that.hideOrShowPages($('.in2publish-stagelisting__item--added').parent(), that.addedFilter);
			that.hideOrShowPages($('.in2publish-stagelisting__item--deleted').parent(), that.deletedFilter);
			that.hideOrShowPages($('.in2publish-stagelisting__item--moved').parent(), that.movedFilter);
		} else {
			that.showPages($('.in2publish-stagelisting__item').parent());
		}
	};

	/**
	 * @returns {void}
	 */
	this.filterButtonsListener = function() {
		$('*[data-action-toggle-filter-status]').click(function(e) {
			e.preventDefault();
			var $this = $(this);
			$this.toggleClass('in2publish-functions-bar--active');
			$.ajax({
				url: $this.prop('href')
			});
			that.setFilterForPageView();
		});
	};

	/**
	 * @returns void
	 */
	this.messageListener = function() {
		var in2publishMessage = $('.in2publish-messages');
		if (in2publishMessage.length) {
			var errorMessages = $('.in2publish-messages > div');
			that.displayElementsWithDelay(errorMessages);

			if (in2publishMessage.hasClass('in2publish-messages--removeable')) {
				errorMessages.each(function() {
					that.addCloseTrigger($(this));
				});
			}
		}
	};

	/**
	 * @returns void
	 */
	this.overlayListener = function() {
		$('[data-in2publish-overlay]').each(function() {
			var element = $(this);
			if (element.data('in2publish-overlay') === 'TRUE') {
				element.on('click', function(event) {
					if (element.data('in2publish-confirm')) {
						if (element.hasClass('in2publish-stagelisting__item__publish--blocked') || !confirm(element.data('in2publish-confirm'))) {
							event.preventDefault();
							return;
						}
					}
					showPreloader();
				});
			}
		});
	};

	/**
	 * Event listener for filter list
	 *
	 * @returns void
	 */
	var workflowFilterListener = function() {
		if ($('*[data-workflow-filter="true"]').length) {
			$('*[data-workflow-filter="true"] *[data-workflow-filter-field="true"]').on('keyup change', function() {
				filterList();
			});
		}
	};

	/**
	 * Filter list is triggered on field change
	 *
	 * @returns void
	 */
	var filterList = function() {
		$('*[data-record-item="true"]').each(function() {
			var $this = $(this);
			var hideItem = false;
			var allDataAttributes = getAllDataRecordAttributesOfElement($this);
			$.each(allDataAttributes, function(key, currentData) {
				var filterField = $('*[name="' + key + '"]');
				if (filterField.length && filterField.val() !== '') {
					var value = filterField.val().toLowerCase();
					if (typeof currentData === 'string') {
						currentData = currentData.toLowerCase();
						if (currentData.indexOf(value) === -1) {
							hideItem = true;
						}
					} else {
						if (currentData !== parseInt(value)) {
							hideItem = true;
						}
					}
				}
			});
			if (hideItem === true) {
				$this.hide();
			} else {
				$this.show();
			}
		});
		countAndShowNumberOfResults();
	};

	/**
	 * @param {jQuery} $element
	 * @return {object}
	 */
	var getAllDataRecordAttributesOfElement = function($element) {
		var attributes = {};
		$.each($element.get(0).attributes, function(i, attribute) {
			if (attribute.name.indexOf('data-record-') !== -1) {
				attributes[attribute.name] = attribute.value;
			}
		});
		return attributes;
	};

	/**
	 * @returns {void}
	 */
	var countAndShowNumberOfResults = function() {
		var container = $('*[data-count-container="true"]');
		if (container.length) {
			container.html($('*[data-record-item="true"]:visible').length);
		}
	};


	/**
	 * ******* Internal *******
	 */

	/**
	 * Show preloading image
	 *
	 * @returns {void}
	 */
	var showPreloader = function() {
		$('.in2publish-preloader').removeClass('in2publish-preloader--hidden');
		$('#typo3-docbody').addClass('stopScrolling');
	};

	/**
	 * Hide preloading image
	 *
	 * @returns {void}
	 */
	var hidePreloader = function() {
		$('.in2publish-preloader').addClass('in2publish-preloader--hidden');
		$('#typo3-docbody').removeClass('stopScrolling');
	};

	/**
	 * initialize overall methods again (show datepicker, etc...)
	 *
	 * @returns {void}
	 */
	var initializeOverallFunctions = function() {
		var In2publishOverall = new window.In2publishOverall($);
		In2publishOverall.initialize();
	};

	/**
	 * @returns void
	 */
	this.initializeFilterStatus = function() {
		that.changedFilter = $('.in2publish-icon-status-changed').hasClass('in2publish-functions-bar--active');
		that.addedFilter = $('.in2publish-icon-status-added').hasClass('in2publish-functions-bar--active');
		that.deletedFilter = $('.in2publish-icon-status-deleted').hasClass('in2publish-functions-bar--active');
		that.movedFilter = $('.in2publish-icon-status-moved').hasClass('in2publish-functions-bar--active');
	};

	/**
	 * @returns {void}
	 */
	this.hidePages = function(pages) {
		pages.hide();
	};

	/**
	 * @returns {void}
	 */
	this.showPages = function(pages) {
		pages.show();
	};

	/**
	 * @returns {void}
	 */
	this.hideOrShowPages = function(pages, status) {
		if (status) {
			pages.each(function() {
				var $this = $(this);
				$this.show();
				that.showParentPages($this);
			});
		} else {
			pages.hide();
		}
	};

	/**
	 * @returns {void}
	 */
	this.showParentPages = function(element) {
		var parentPage = element.parent().closest('ul').siblings('.in2publish-stagelisting__item').parent();
		if (parentPage !== undefined && parentPage.length) {
			parentPage.show();
			that.showParentPages(parentPage);
		}
	};

	/**
	 * @returns {void}
	 */
	this.displayElementsWithDelay = function(elements) {
		var delay = 0;
		elements.each(function() {
			var element = $(this);
			element.queue('fade', function(next) {
				element.delay(delay).fadeIn(500, next);
			});
			element.dequeue('fade');
			delay += 300;
		});
	};

	/**
	 * @returns {void}
	 */
	this.addCloseTrigger = function(element) {
		var closeButton = $('<span />').addClass('in2publish-icon-x-altx-alt').click(function() {
			element.remove();
		});
		element.append(closeButton);
	};
}

var jQueryContainer;
if (TYPO3.jQuery !== undefined) {
	jQueryContainer = TYPO3.jQuery;
} else {
	jQueryContainer = jQuery;
}
jQueryContainer(document).ready(function($) {
	'use strict';

	var In2publishModule = new window.In2publishModule($);
	In2publishModule.initialize();

	var In2publishOverall = new window.In2publishOverall($);
	In2publishOverall.initialize();
});
