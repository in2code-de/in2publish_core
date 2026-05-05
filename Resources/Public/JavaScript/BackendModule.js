'use strict';

/**
 * Module: @in2code/in2publish_core/backend-module.js
 */
import DebounceEvent from '@typo3/core/event/debounce-event.js';
import Modal from '@typo3/backend/modal.js';
import Clearable from '@typo3/backend/input/clearable.js';
import LoadingOverlay from '@in2code/in2publish_core/loading-overlay.js';
import ConfirmationModal from '@in2code/in2publish_core/confirmation-modal.js';
import DocumentService from '@typo3/core/document-service.js';
import InformationModal from '@in2code/in2publish_core/information-modal.js';

class In2publishCoreModule {
	static isPublishFilesModule = (
		document.querySelector('.module[data-module-name="in2publish_core_m3"]') !== null
		|| document.querySelector('.module[data-module-name="file_In2publishCoreM3"]') !== null
	);
	static isPublishOverviewModule = (
		document.querySelector('.module[data-module-name="in2publish_core_m1"]') !== null
		|| document.querySelector('.module[data-module-name="web_In2publishCoreM1"]') !== null
	);

	static unchangedFilter = false;
	static changedFilter = false;
	static addedFilter = false;
	static deletedFilter = false;
	static movedFilter = false;

	static initialize() {
		this.toggleDirtyPropertiesListContainerListener();
		if (this.isPublishFilesModule) {
			this.filterItemsByStatus();
			this.setupFilterListeners();
			this.setupClearableInputs();
		} else if (this.isPublishOverviewModule) {
			const changedElement = document.querySelector('.in2publish-icon-status-changed');
			if (changedElement) {
				this.setFilterForPageView();
				this.filterButtonsListener();
			}
			this.addFilterDropdownListener();
			this.addLanguageFilterListener();
			this.addLevelFilterListener();
			this.addSearchListener();
			this.addWorkflowButtonListener();
			this.syncOverviewFilterArguments();
		}
	}


	static addFilterDropdownListener() {
		const stateFilter = document.querySelector('.js-in2publish-statefilter');

		if (!stateFilter) {
			return;
		}

		stateFilter.addEventListener('change', () => {
			In2publishCoreModule.setFilterForPageView();
			In2publishCoreModule.persistOverviewFilters();
			In2publishCoreModule.syncOverviewFilterArguments();
		})
	}

	static addLevelFilterListener() {
		const levelFilter = document.querySelector('.js-in2publish-levelfilter');

		if (!levelFilter) {
			return;
		}

		levelFilter.addEventListener('change', () => {
			const url = new URL(levelFilter.value, window.location.origin);
			const freeTextFilter = document.querySelector('.js-form-search');
			const stateFilter = document.querySelector('.js-in2publish-statefilter');
			const languageFilter = document.querySelector('.js-in2publish-languagefilter');

			if (freeTextFilter && freeTextFilter.value !== '') {
				url.searchParams.set('freeText', freeTextFilter.value);
			}
			if (stateFilter && stateFilter.value !== '') {
				url.searchParams.set('state', stateFilter.value);
			}
			if (languageFilter && languageFilter.value !== '') {
				url.searchParams.set('language', languageFilter.value);
			}

			this.persistOverviewFilters({ keepalive: true });
			this.syncOverviewFilterArguments();
			LoadingOverlay.showOverlay();
			window.location = url.toString();
		})
	}

	static filterItemsByStatus() {
		this.changedFilter = document.querySelector('.js-in2publish-filter[value="changed"]:checked') !== null;
		this.addedFilter = document.querySelector('.js-in2publish-filter[value="added"]:checked') !== null;
		this.deletedFilter = document.querySelector('.js-in2publish-filter[value="deleted"]:checked') !== null;
		this.movedFilter = document.querySelector('.js-in2publish-filter[value="moved"]:checked') !== null;
		this.unchangedFilter = document.querySelector('.js-in2publish-filter[value="unchanged"]:checked') !== null;

		if (this.changedFilter || this.addedFilter || this.deletedFilter ||
			this.movedFilter || this.unchangedFilter) {
			this.hideOrShowElements(document.querySelectorAll('.in2publish-stagelisting__item--changed'), this.changedFilter);
			this.hideOrShowElements(document.querySelectorAll('.in2publish-stagelisting__item--added'), this.addedFilter);
			this.hideOrShowElements(document.querySelectorAll('.in2publish-stagelisting__item--deleted'), this.deletedFilter);
			this.hideOrShowElements(document.querySelectorAll('.in2publish-stagelisting__item--moved'), this.movedFilter);
			this.hideOrShowElements(document.querySelectorAll('.in2publish-stagelisting__item--unchanged'), this.unchangedFilter);
		} else {
			document.querySelectorAll('.in2publish-stagelisting__item').forEach(el => {
				if (el) {
					el.style.display = 'table-row';
				}
			});
		}
	}

	static toggleDirtyPropertiesListContainerListener() {
		document.querySelectorAll('[data-action="opendirtypropertieslistcontainer"]')
			.forEach(el => el.addEventListener('click', (event) => this.toggleDirtyPropertiesListContainer(event)));
	}

	static toggleDirtyPropertiesListContainer(event) {
		const target = event.currentTarget;
		if (!target) return;

		const row = target.closest('.in2publish-page');
		if (!row) return;

		row.classList.toggle('in2publish-page--open');
	}

	static openOrCloseStageListingDropdownContainer(container) {
		if (!container) return;

		if (container.classList.contains('in2publish-stagelisting__dropdown--close')) {
			document.querySelectorAll('.in2publish-stagelisting__dropdown--open').forEach(el => {
				if (el) {
					el.classList.remove('in2publish-stagelisting__dropdown--open');
					el.classList.add('in2publish-stagelisting__dropdown--close');
					el.style.display = 'none';
				}
			});
			container.classList.remove('in2publish-stagelisting__dropdown--close');
			container.classList.add('in2publish-stagelisting__dropdown--open');
			container.style.display = 'block';
		} else {
			container.classList.remove('in2publish-stagelisting__dropdown--open');
			container.classList.add('in2publish-stagelisting__dropdown--close');
			container.style.display = 'none';
		}
	}

	static setFilterForPageView() {
		const stateFilter = document.querySelector('.js-in2publish-statefilter');
		if (!stateFilter) {
			return;
		}

		const selectedState = stateFilter.value;
		const rootGroups = document.querySelectorAll('.in2publish-stagelisting > .in2publish-page-group');

		if (selectedState === '') {
			rootGroups.forEach(group => {
				this.resetPageGroupVisibility(group);
			});
			return;
		}

		rootGroups.forEach(group => {
			this.applyStateFilterToGroup(group, selectedState);
		});
	}

	static resetPageGroupVisibility(group) {
		if (!group) {
			return;
		}

		group.style.display = 'block';

		const page = this.getDirectChildByClass(group, 'in2publish-page');
		if (page) {
			page.style.display = 'block';
		}

		const childrenContainer = this.getDirectChildByClass(group, 'in2publish-page-group__children');
		if (childrenContainer) {
			childrenContainer.style.display = 'block';
			Array.from(childrenContainer.children)
				.filter(child => child.classList.contains('in2publish-page-group'))
				.forEach(childGroup => this.resetPageGroupVisibility(childGroup));
		}
	}

	static applyStateFilterToGroup(group, selectedState) {
		if (!group) {
			return false;
		}

		const page = this.getDirectChildByClass(group, 'in2publish-page');
		const childrenContainer = this.getDirectChildByClass(group, 'in2publish-page-group__children');

		let hasVisibleChildren = false;
		if (childrenContainer) {
			hasVisibleChildren = Array.from(childrenContainer.children)
				.filter(child => child.classList.contains('in2publish-page-group'))
				.map(childGroup => this.applyStateFilterToGroup(childGroup, selectedState))
				.some(Boolean);
			childrenContainer.style.display = hasVisibleChildren ? 'block' : 'none';
		}

		const pageMatches = page ? this.pageMatchesStateFilter(page, selectedState) : false;
		if (page) {
			page.style.display = pageMatches ? 'block' : 'none';
		}

		const groupShouldBeVisible = pageMatches || hasVisibleChildren;
		group.style.display = groupShouldBeVisible ? 'block' : 'none';

		return groupShouldBeVisible;
	}

	static pageMatchesStateFilter(page, selectedState) {
		if (!page) {
			return false;
		}

		const recordState = page.getAttribute('data-record-state') || '';
		if (recordState === selectedState) {
			return true;
		}

		if (selectedState === 'deleted' && (recordState === 'soft_deleted' || recordState === 'deleted')) {
			return true;
		}

		return false;
	}

	static getDirectChildByClass(element, className) {
		if (!element) {
			return null;
		}

		return Array.from(element.children).find(child => child.classList.contains(className)) || null;
	}

	static processFilteredElements(selector, filterStatus) {
		const elements = document.querySelectorAll(selector);
		elements.forEach(el => {
			this.hideOrShowElements([el], filterStatus);
		});
	}

	static hideOrShowElements(elements, status) {
		elements.forEach(element => {
			if (!element) return;

			if (status) {
				element.style.display = 'block';
				this.showParentElements(element);
			} else {
				element.style.display = 'none';
			}
		});
	}

	static showParentElements(element) {
		if (!element) return;

		const parentElement = element.parentElement.closest('.in2publish-page-group');
		if (parentElement) {
			parentElement.style.display = 'block';
			this.showParentElements(parentElement);
		}
	}

	static filterButtonsListener() {
		document.querySelectorAll('*[data-action-toggle-filter-status]').forEach(element => {
			if (!element) return;

			element.addEventListener('click', (e) => {
				e.preventDefault();
				element.classList.toggle('in2publish-functions-bar--active');

				const href = element.getAttribute('href');
				if (href) {
					fetch(href);
				}

				In2publishCoreModule.setFilterForPageView();
			});
		});
	}

	static setupFilterListeners() {
		const filters = document.querySelectorAll('.js-in2publish-filter');

		filters.forEach(filter => {
			if (!filter) return;

			filter.addEventListener('click', event => {
				const input = event.currentTarget;
				if (!input) return;

				input.disabled = true;
				const dataHref = input.getAttribute('data-href');

				if (dataHref) {
					fetch(dataHref)
						.then(response => response.json())
						.finally(() => {
							setTimeout(() => input.disabled = false, 100);
						});
				}

				this.filterItemsByStatus();
			});
		});

			const freeTextForm = document.querySelector('.js-form-search');
			if (freeTextForm) {
				new DebounceEvent('input', event => {
					if (!event.target) return;

					const freeTextValue = event.target.value.toLowerCase();
					const elements = document.querySelectorAll('.in2publish-stagelisting__item');

					elements.forEach(item => {
						if (!item) return;

						if (freeTextValue !== '') {
							const searchable = item.getAttribute('data-searchable')?.toLowerCase() || '';
							item.classList.toggle('d-none', !searchable.includes(freeTextValue));
						} else {
							item.classList.remove('d-none');
						}
					});
				}, 250).bindTo(freeTextForm);
			}
		}

	static setupClearableInputs() {
		const clearableInputs = document.querySelectorAll('.t3js-clearable');
		clearableInputs.forEach(input => {
			if (input && typeof input.clearable === 'function') {
				input.clearable();
			}
		});
	}

	static addLanguageFilterListener() {
		const languageFilter = document.querySelector('.js-in2publish-languagefilter');
		if (!languageFilter) {
			return;
		}

		languageFilter.addEventListener('change', () => {
			this.filterItemsByLanguage(languageFilter.value);
			this.persistOverviewFilters();
			this.syncOverviewFilterArguments();
		})
	}

	static filterItemsByLanguage(languageValue) {
		const pageRecords = document.querySelectorAll('.in2publish-page');

		if (languageValue === '') {
			// Show all records if no language is selected
			pageRecords.forEach(function (record) {
				record.classList.remove('d-none');
			});
		} else {
			// Filter records by language
			pageRecords.forEach(function (record) {
				const recordLanguages = record.getAttribute('data-record-language');

				if (recordLanguages && recordLanguages.split('|').includes(languageValue)) {
					record.classList.remove('d-none');
				} else {
					record.classList.add('d-none');
				}
			});
		}
	}

	static addSearchListener() {
		const freeTextForm = document.querySelector('.js-form-search');

		if (freeTextForm) {
			new DebounceEvent('input', function (event) {
				const freeTextValue = event.target.value;
				const elements = document.querySelectorAll('[data-searchable]');
				(Array.from(elements)).forEach(function (item) {
					if (freeTextValue !== '') {
						const searchable = item.getAttribute('data-searchable');
						if (!searchable.includes(freeTextValue)) {
							item.classList.add('d-none');
						} else {
							item.classList.remove('d-none');
						}
					} else {
						item.classList.remove('d-none');
					}
				});
				In2publishCoreModule.persistOverviewFilters();
				In2publishCoreModule.syncOverviewFilterArguments();
				}, 250).bindTo(freeTextForm);

			const freeTextFormClear = document.querySelector('.js-form-search + .close');
			if (freeTextFormClear) {
				freeTextFormClear.addEventListener('click', function () {
					const elements = document.querySelectorAll('[data-searchable]');
					(Array.from(elements)).forEach(function (item) {
						item.classList.remove('d-none');
					});
					In2publishCoreModule.persistOverviewFilters();
					In2publishCoreModule.syncOverviewFilterArguments();
				});
			}
		}
	}

	static addWorkflowButtonListener() {
		document.querySelectorAll('.js-in2publish-workflowbutton[href]').forEach((workflowButton) => {
			workflowButton.addEventListener('click', () => {
				this.syncOverviewFilterArguments();
				this.persistOverviewFilters({ keepalive: true });
				LoadingOverlay.showOverlay();
			});
		});
	}

	static applyInitialOverviewFilters() {
		const stateFilter = document.querySelector('.js-in2publish-statefilter');
		if (stateFilter && stateFilter.value !== '') {
			this.setFilterForPageView();
		}

		const languageFilter = document.querySelector('.js-in2publish-languagefilter');
		if (languageFilter && languageFilter.value !== '') {
			this.filterItemsByLanguage(languageFilter.value);
		}

		const freeTextForm = document.querySelector('.js-form-search');
		if (freeTextForm && freeTextForm.value !== '') {
			const freeTextValue = freeTextForm.value;
			const elements = document.querySelectorAll('[data-searchable]');
			(Array.from(elements)).forEach(function (item) {
				const searchable = item.getAttribute('data-searchable');
				if (!searchable.includes(freeTextValue)) {
					item.classList.add('d-none');
				} else {
					item.classList.remove('d-none');
				}
			});
		}
	}

	static persistOverviewFilters() {
		const filterContainer = document.querySelector('.in2publishjs__publishfilter');
		const persistUri = filterContainer?.dataset.persistUri;
		if (!persistUri) {
			return;
		}

		const url = new URL(persistUri, window.location.origin);
		const overviewFilters = this.getOverviewFilters();

		url.searchParams.set('freeText', overviewFilters.freeText);
		url.searchParams.set('state', overviewFilters.state);
		url.searchParams.set('language', overviewFilters.language);
		url.searchParams.set('pageRecursionLimit', overviewFilters.pageRecursionLimit);

		fetch(url.toString(), {
			credentials: 'same-origin',
			keepalive: true,
		});
	}

	static syncOverviewFilterArguments() {
		const overviewFilters = this.getOverviewFilters();
		const overviewReturnUrl = this.getOverviewReturnUrl(overviewFilters);
		const publishAllLink = document.querySelector('.js-publish-all-records');
		if (publishAllLink?.href) {
			const url = new URL(publishAllLink.href, window.location.origin);
			url.searchParams.set('pageRecursionLimit', overviewFilters.pageRecursionLimit);
			url.searchParams.set('freeText', overviewFilters.freeText);
			url.searchParams.set('state', overviewFilters.state);
			url.searchParams.set('language', overviewFilters.language);
			publishAllLink.href = url.toString();
		}

		const publishBagForm = document.querySelector('#publishBagForm');
		if (publishBagForm) {
			const pageRecursionLimitInput = publishBagForm.querySelector('input[name="pageRecursionLimit"]');
			if (pageRecursionLimitInput) {
				pageRecursionLimitInput.value = overviewFilters.pageRecursionLimit;
			}

			const freeTextInput = publishBagForm.querySelector('input[name="freeText"]');
			if (freeTextInput) {
				freeTextInput.value = overviewFilters.freeText;
			}

			const stateInput = publishBagForm.querySelector('input[name="state"]');
			if (stateInput) {
				stateInput.value = overviewFilters.state;
			}

			const languageInput = publishBagForm.querySelector('input[name="language"]');
			if (languageInput) {
				languageInput.value = overviewFilters.language;
			}
		}

		document.querySelectorAll('.js-in2publish-workflowbutton[data-details-uri]').forEach((workflowButton) => {
			const detailsUri = workflowButton.dataset.detailsUri;
			if (!detailsUri) {
				return;
			}

			const detailsUrl = new URL(detailsUri, window.location.origin);
			detailsUrl.searchParams.set('returnUrl', overviewReturnUrl);
			workflowButton.dataset.detailsUri = detailsUrl.toString();
		});
	}

	static getOverviewFilters() {
		const freeTextFilter = document.querySelector('.js-form-search');
		const stateFilter = document.querySelector('.js-in2publish-statefilter');
		const languageFilter = document.querySelector('.js-in2publish-languagefilter');
		const levelFilter = document.querySelector('.js-in2publish-levelfilter');

		return {
			freeText: freeTextFilter?.value || '',
			state: stateFilter?.value || '',
			language: languageFilter?.value || '',
			pageRecursionLimit: this.getSelectedPageRecursionLimit(levelFilter),
		};
	}

	static getSelectedPageRecursionLimit(levelFilter) {
		if (!levelFilter?.value) {
			return '1';
		}

		const levelUrl = new URL(levelFilter.value, window.location.origin);
		return levelUrl.searchParams.get('pageRecursionLimit') || '1';
	}

	static getOverviewReturnUrl(overviewFilters) {
		const url = new URL(window.location.href);
		url.searchParams.set('pageRecursionLimit', overviewFilters.pageRecursionLimit);

		if (overviewFilters.freeText === '') {
			url.searchParams.delete('freeText');
		} else {
			url.searchParams.set('freeText', overviewFilters.freeText);
		}

		if (overviewFilters.state === '') {
			url.searchParams.delete('state');
		} else {
			url.searchParams.set('state', overviewFilters.state);
		}

		if (overviewFilters.language === '') {
			url.searchParams.delete('language');
		} else {
			url.searchParams.set('language', overviewFilters.language);
		}

		return url.toString();
	}
}

DocumentService.ready().then(() => {
	In2publishCoreModule.initialize();
	if (In2publishCoreModule.isPublishOverviewModule) {
		In2publishCoreModule.applyInitialOverviewFilters();
	}
});

export default In2publishCoreModule;
