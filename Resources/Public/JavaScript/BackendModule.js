'use strict';

define([
	'jquery',
	'TYPO3/CMS/Core/Event/DebounceEvent',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Input/Clearable',
	'TYPO3/CMS/In2publishCore/LoadingOverlay',
	'TYPO3/CMS/In2publishCore/ConfirmationModal',
], function ($, DebounceEvent, Modal, LoadingOverlay, ConfirmationModal) {
	var In2publishCoreModule = {
		isPublishFilesModule: (document.querySelector('.module[data-module-name="in2publish_core_m3"]') !== null)
			// TYPO3 v11
			|| (document.querySelector('.module[data-module-name="file_In2publishCoreM3"]') !== null),
		unchangedFilter: false,
		changedFilter: false,
		addedFilter: false,
		deletedFilter: false,
		movedFilter: false,
	};

	In2publishCoreModule.initialize = function () {
		In2publishCoreModule.toggleDirtyPropertiesListContainerListener();
		In2publishCoreModule.setupClearableInputs();
		if (In2publishCoreModule.isPublishFilesModule) {
			In2publishCoreModule.filterItemsByStatus();
			In2publishCoreModule.setupFilterListeners();
		} else {
			In2publishCoreModule.setFilterForPageView();
			In2publishCoreModule.filterButtonsListener();
			In2publishCoreModule.addFilterDropdownListener();
			In2publishCoreModule.addLevelFilterListener();
			In2publishCoreModule.addSearchListener();
		}
	};

	In2publishCoreModule.addFilterDropdownListener = function () {
		const stateFilter = document.querySelector('.js-in2publish-statefilter');
		if (!stateFilter) {
			return;
		}

		stateFilter.addEventListener('change', () => {
			In2publishCoreModule.setFilterForPageView();
		})
	}

	In2publishCoreModule.addLevelFilterListener = function () {
		const levelFilter = document.querySelector('.js-in2publish-levelfilter');
		if (!levelFilter) {
			return;
		}

		levelFilter.addEventListener('change', () => {
			window.location = levelFilter.value;
		})
	}

	In2publishCoreModule.addSearchListener = function () {
		const searchForm = document.querySelector('.js-form-search');
		if (searchForm) {
			new DebounceEvent('input', function (event) {
				const searchValue = event.target.value.toLowerCase();
				const elements = document.querySelectorAll('[data-searchable]');

				(Array.from(elements)).forEach(function (item) {
					if (searchValue !== '') {
						const searchable = item.getAttribute('data-searchable').toLowerCase();

						if (!searchable.includes(searchValue)) {
							item.classList.add('d-none');
						} else {
							item.classList.remove('d-none');
						}
					} else {
						item.classList.remove('d-none');
					}
				});

			}, 250).bindTo(searchForm);

			const searchFormClear = document.querySelector('.js-form-search + .close');
			if (searchFormClear) {
				searchFormClear.addEventListener('click', function () {
					const elements = document.querySelectorAll('[data-searchable]');

					(Array.from(elements)).forEach(function (item) {
						item.classList.remove('d-none');
					});
				});
			}
		}
	}

	In2publishCoreModule.filterItemsByStatus = function () {
		In2publishCoreModule.changedFilter = (document.querySelector('.js-in2publish-filter[value="changed"]:checked') !== null);
		In2publishCoreModule.addedFilter = (document.querySelector('.js-in2publish-filter[value="added"]:checked') !== null);
		In2publishCoreModule.deletedFilter = (document.querySelector('.js-in2publish-filter[value="deleted"]:checked') !== null);
		In2publishCoreModule.movedFilter = (document.querySelector('.js-in2publish-filter[value="moved"]:checked') !== null);
		In2publishCoreModule.unchangedFilter = (document.querySelector('.js-in2publish-filter[value="unchanged"]:checked') !== null);

		if (In2publishCoreModule.changedFilter ||
			In2publishCoreModule.addedFilter ||
			In2publishCoreModule.deletedFilter ||
			In2publishCoreModule.movedFilter ||
			In2publishCoreModule.unchangedFilter
		) {
			In2publishCoreModule.hideOrShowPages($('.in2publish-stagelisting__item--changed'), In2publishCoreModule.changedFilter);
			In2publishCoreModule.hideOrShowPages($('.in2publish-stagelisting__item--added'), In2publishCoreModule.addedFilter);
			In2publishCoreModule.hideOrShowPages($('.in2publish-stagelisting__item--deleted'), In2publishCoreModule.deletedFilter);
			In2publishCoreModule.hideOrShowPages($('.in2publish-stagelisting__item--moved'), In2publishCoreModule.movedFilter);
			In2publishCoreModule.hideOrShowPages($('.in2publish-stagelisting__item--unchanged'), In2publishCoreModule.unchangedFilter);
		} else {
			$('.in2publish-stagelisting__item').show();
		}
	};

	In2publishCoreModule.toggleDirtyPropertiesListContainerListener = function () {
		document.querySelectorAll('[data-action="opendirtypropertieslistcontainer"]').forEach(
			el => el.addEventListener('click', In2publishCoreModule.toggleDirtyPropertiesListContainer)
		);
	};

	/**
	 * @param {Event} event
	 */
	In2publishCoreModule.toggleDirtyPropertiesListContainer = function (event) {
		/** @var {HTMLElement} target */
		const target = event.currentTarget;
		const row = target.closest('.in2publish-page');

		row.classList.toggle('in2publish-page--open');
	}

	In2publishCoreModule.openOrCloseStageListingDropdownContainer = function ($container) {
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

	In2publishCoreModule.setFilterForPageView = function () {
		const stateFilter = document.querySelector('.js-in2publish-statefilter');
		if (!stateFilter) {
			return;
		}

		In2publishCoreModule.changedFilter = stateFilter.value === 'changed';
		In2publishCoreModule.addedFilter = stateFilter.value === 'added';
		In2publishCoreModule.deletedFilter = stateFilter.value === 'deleted';
		In2publishCoreModule.movedFilter = stateFilter.value === 'moved';

		if (In2publishCoreModule.changedFilter ||
			In2publishCoreModule.addedFilter ||
			In2publishCoreModule.deletedFilter ||
			In2publishCoreModule.movedFilter
		) {
			$('.in2publish-page[data-record-state="unchanged"]').hide();
			In2publishCoreModule.hideOrShowPages($('.in2publish-page[data-record-state="changed"]'), In2publishCoreModule.changedFilter);
			In2publishCoreModule.hideOrShowPages($('.in2publish-page[data-record-state="added"]'), In2publishCoreModule.addedFilter);
			In2publishCoreModule.hideOrShowPages($('.in2publish-page[data-record-state="deleted"]'), In2publishCoreModule.deletedFilter);
			In2publishCoreModule.hideOrShowPages($('.in2publish-page[data-record-state="moved"]'), In2publishCoreModule.movedFilter);
		} else {
			$('.in2publish-page').show();
		}
	};

	In2publishCoreModule.hideOrShowPages = function (pages, status) {
		console.log(pages, status)
		if (status) {
			pages.each(function () {
				var $this = $(this);
				$this.show();
				// In2publishCoreModule.showParentPages($this);
			});
		} else {
			pages.hide();
		}
	};

	In2publishCoreModule.showParentPages = function (element) {
		var parentPage = element.parent().closest('ul').siblings('.in2publish-page');
		if (undefined !== parentPage && parentPage.length) {
			parentPage.show();
			In2publishCoreModule.showParentPages(parentPage);
		}
	};

	In2publishCoreModule.filterButtonsListener = function () {
		$('*[data-action-toggle-filter-status]').click(function (e) {
			e.preventDefault();
			var $this = $(this);
			$this.toggleClass('in2publish-functions-bar--active');
			$.ajax({
				url: $this.prop('href')
			});
			In2publishCoreModule.setFilterForPageView();
		});
	};

	In2publishCoreModule.setupFilterListeners = function () {
		const filters = document.querySelectorAll('.js-in2publish-filter');

		(Array.from(filters)).forEach(function (filter) {
			filter.addEventListener('click', function (event) {
				const input = event.currentTarget;
				input.disabled = true;
				fetch(input.getAttribute('data-href'))
					.then(response => response.json())
					// Do not reset the buttons state to its actual value because it is more confusing that having
					// a filter state not properly set when reloading the page
					// .then(data => input.checked = data.newStatus)
					.finally(() => {
						// The request might be so fast that the user only sees a flicker when the input gets disabled.
						// Set a timeout to make the disabled state more visible and prevent spamming.
						setTimeout(() => input.disabled = false, 100);
					});

				In2publishCoreModule.filterItemsByStatus();
			});
		});

		In2publishCoreModule.addSearchListener();
	}

	In2publishCoreModule.setupClearableInputs = function () {
		(Array.from(document.querySelectorAll('.t3js-clearable'))).forEach(function (input) {
			input.clearable();
		});
	};

	$(function () {
		In2publishCoreModule.initialize();
	});

	return In2publishCoreModule;
});
