'use strict';

define([
	'jquery'
], function ($) {
	var In2publishModule = {
		changedFilter: false,
		addedFilter: false,
		deletedFilter: false,
		movedFilter: false,
		objects: {
			body: undefined,
			preLoader: undefined,
			typo3DocBody: undefined
		}
	};

	In2publishModule.initialize = function () {
		In2publishModule.toggleDirtyPropertiesListContainerListener();
		In2publishModule.setFilterForPageView();
		In2publishModule.filterButtonsListener();
		In2publishModule.overlayListener();
	};

	In2publishModule.toggleDirtyPropertiesListContainerListener = function () {
		$('*[data-action="opendirtypropertieslistcontainer"]').click(function () {
			var $containerDropdown = $(this).closest('.in2publish-stagelisting__item').find('.in2publish-stagelisting__dropdown:first');
			In2publishModule.openOrCloseStageListingDropdownContainer($containerDropdown);

			var $containerMessages = $(this).closest('.in2publish-stagelisting__item').find('.in2publish-stagelisting__messages:first');
			In2publishModule.openOrCloseStageListingMessagesContainer($containerMessages);
		});
	};

	In2publishModule.openOrCloseStageListingDropdownContainer = function ($container) {
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

	In2publishModule.openOrCloseStageListingMessagesContainer = function ($container) {
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

	In2publishModule.setFilterForPageView = function () {
		In2publishModule.changedFilter = $('.in2publish-icon-status-changed').hasClass('in2publish-functions-bar--active');
		In2publishModule.addedFilter = $('.in2publish-icon-status-added').hasClass('in2publish-functions-bar--active');
		In2publishModule.deletedFilter = $('.in2publish-icon-status-deleted').hasClass('in2publish-functions-bar--active');
		In2publishModule.movedFilter = $('.in2publish-icon-status-moved').hasClass('in2publish-functions-bar--active');

		if (In2publishModule.changedFilter ||
			In2publishModule.addedFilter ||
			In2publishModule.deletedFilter ||
			In2publishModule.movedFilter
		) {
			$('.in2publish-stagelisting__item--unchanged').parent().hide();
			In2publishModule.hideOrShowPages($('.in2publish-stagelisting__item--changed').parent(), In2publishModule.changedFilter);
			In2publishModule.hideOrShowPages($('.in2publish-stagelisting__item--added').parent(), In2publishModule.addedFilter);
			In2publishModule.hideOrShowPages($('.in2publish-stagelisting__item--deleted').parent(), In2publishModule.deletedFilter);
			In2publishModule.hideOrShowPages($('.in2publish-stagelisting__item--moved').parent(), In2publishModule.movedFilter);
		} else {
			$('.in2publish-stagelisting__item').parent().show();
		}
	};

	In2publishModule.hideOrShowPages = function (pages, status) {
		if (status) {
			pages.each(function () {
				var $this = $(this);
				$this.show();
				In2publishModule.showParentPages($this);
			});
		} else {
			pages.hide();
		}
	};

	In2publishModule.showParentPages = function (element) {
		var parentPage = element.parent().closest('ul').siblings('.in2publish-stagelisting__item').parent();
		if (undefined !== parentPage && parentPage.length) {
			parentPage.show();
			In2publishModule.showParentPages(parentPage);
		}
	};

	In2publishModule.filterButtonsListener = function () {
		$('*[data-action-toggle-filter-status]').click(function (e) {
			e.preventDefault();
			var $this = $(this);
			$this.toggleClass('in2publish-functions-bar--active');
			$.ajax({
				url: $this.prop('href')
			});
			In2publishModule.setFilterForPageView();
		});
	};

	In2publishModule.overlayListener = function () {
		$('[data-in2publish-confirm]').each(function () {
			var element = $(this);
			element.on('click', function (event) {
				if (element.data('in2publish-confirm')) {
					if (element.hasClass('in2publish-stagelisting__item__publish--blocked') || !confirm(element.data('in2publish-confirm'))) {
						event.preventDefault();
						event.stopPropagation();
						event.stopImmediatePropagation();
						return;
					}
				}
				if ('TRUE' === element.data('in2publish-overlay')) {
					In2publishModule.showPreloader();
				}
			});
		});
	};

	In2publishModule.showPreloader = function () {
		In2publishModule.objects.preLoader.removeClass('in2publish-preloader--hidden');
		In2publishModule.objects.typo3DocBody.addClass('stopScrolling');
	};

	In2publishModule.hidePreLoader = function () {
		In2publishModule.objects.preLoader.addClass('in2publish-preloader--hidden');
		In2publishModule.objects.typo3DocBody.removeClass('stopScrolling');
	};

	$(function () {
		In2publishModule.objects.body = $('body');
		In2publishModule.objects.preLoader = $('.in2publish-preloader');
		In2publishModule.objects.typo3DocBody = $('#typo3-docbody');
		In2publishModule.initialize();
	});

	return In2publishModule;
});
