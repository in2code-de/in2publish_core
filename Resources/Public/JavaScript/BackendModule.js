'use strict';

define([
	'jquery', 'TYPO3/CMS/Core/Event/DebounceEvent', 'TYPO3/CMS/Backend/Input/Clearable'
], function ($, DebounceEvent) {
	var In2publishModule = {
		isNewUI: (document.querySelector('.typo3-fullDoc[data-ui-refresh]') !== null),
		unchangedFilter: false,
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
		if (In2publishModule.isNewUI) {
			In2publishModule.setupClearableInputs();
			In2publishModule.filterItemsByStatus();
			In2publishModule.setupFilterListeners();
		} else {
			In2publishModule.setFilterForPageView();
			In2publishModule.filterButtonsListener();
		}
		In2publishModule.overlayListener();
	};

	In2publishModule.toggleDirtyPropertiesListContainerListener = function () {
		$('*[data-action="opendirtypropertieslistcontainer"]').click(function () {
			var target = $(this).attr('data-target');

			var $containerDropdown = $('*[data-diff-for="' + target + '"]');
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

	In2publishModule.setupFilterListeners = function () {
		const filters = document.querySelectorAll('.js-in2publish-filter');

		(Array.from(filters)).forEach(function (filter) {
			filter.addEventListener('click', function(event) {
				fetch(event.currentTarget.getAttribute('data-href'));

				In2publishModule.filterItemsByStatus();
			});
		});

		const searchForm = document.querySelector('.js-form-search');
		if (searchForm) {
			new DebounceEvent('input', function (event) {
				const searchValue = event.target.value;
				const elements = document.querySelectorAll('.in2publish-stagelisting__item');

				(Array.from(elements)).forEach(function (item) {
					if (searchValue !== '') {
						const searchable = item.getAttribute('data-searchable');

						if (!searchable.includes(searchValue)) {
							item.classList.add('d-none');
						}
						else {
							item.classList.remove('d-none');
						}
					}
					else {
						item.classList.remove('d-none');
					}
				});

			}, 250).bindTo(searchForm);

			const searchFormClear = document.querySelector('.js-form-search + .close');
			if (searchFormClear) {
				searchFormClear.addEventListener('click', function () {
					const elements = document.querySelectorAll('.in2publish-stagelisting__item');

					(Array.from(elements)).forEach(function (item) {
						item.classList.remove('d-none');
					});
				});
			}
		}
	}

	In2publishModule.setupClearableInputs = function () {
		(Array.from(document.querySelectorAll('.t3js-clearable'))).forEach(function (input) {
			input.clearable();
		});
	};

	In2publishModule.filterItemsByStatus = function () {
		In2publishModule.changedFilter = (document.querySelector('.js-in2publish-filter[value="changed"]:checked') !== null);
		In2publishModule.addedFilter = (document.querySelector('.js-in2publish-filter[value="added"]:checked') !== null);
		In2publishModule.deletedFilter = (document.querySelector('.js-in2publish-filter[value="deleted"]:checked') !== null);
		In2publishModule.movedFilter = (document.querySelector('.js-in2publish-filter[value="moved"]:checked') !== null);
		In2publishModule.unchangedFilter = (document.querySelector('.js-in2publish-filter[value="unchanged"]:checked') !== null);

		if (In2publishModule.changedFilter ||
			In2publishModule.addedFilter ||
			In2publishModule.deletedFilter ||
			In2publishModule.movedFilter ||
			In2publishModule.unchangedFilter
		) {
			In2publishModule.hideOrShowPages($('.in2publish-stagelisting__item--changed'), In2publishModule.changedFilter);
			In2publishModule.hideOrShowPages($('.in2publish-stagelisting__item--added'), In2publishModule.addedFilter);
			In2publishModule.hideOrShowPages($('.in2publish-stagelisting__item--deleted'), In2publishModule.deletedFilter);
			In2publishModule.hideOrShowPages($('.in2publish-stagelisting__item--moved'), In2publishModule.movedFilter);
			In2publishModule.hideOrShowPages($('.in2publish-stagelisting__item--unchanged'), In2publishModule.unchangedFilter);
		} else {
			$('.in2publish-stagelisting__item').show();
		}
	};

	$(function () {
		In2publishModule.objects.body = $('body');
		In2publishModule.objects.preLoader = $('.in2publish-preloader');
		In2publishModule.objects.typo3DocBody = $('#typo3-docbody');
		In2publishModule.initialize();
	});

	return In2publishModule;
});
