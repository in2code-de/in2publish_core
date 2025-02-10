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

class In2publishCoreModule {
	static isPublishFilesModule = (
		document.querySelector('.module[data-module-name="in2publish_core_m3"]') !== null
		|| document.querySelector('.module[data-module-name="file_In2publishCoreM3"]') !== null
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
		} else {
			const changedElement = document.querySelector('.in2publish-icon-status-changed');
			if (changedElement) {
				this.setFilterForPageView();
				this.filterButtonsListener();
			}
		}
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

		const row = target.closest('.in2publish-stagelisting__item');
		if (!row) return;

		const dirtyPropertiesContainer = row.querySelector('.in2publish-stagelisting__dropdown');
		if (!dirtyPropertiesContainer) return;

		dirtyPropertiesContainer.classList.toggle('in2publish-stagelisting__dropdown--close');
		dirtyPropertiesContainer.classList.toggle('in2publish-stagelisting__dropdown--open');

		if (dirtyPropertiesContainer.classList.contains('in2publish-stagelisting__dropdown--open')) {
			dirtyPropertiesContainer.style.display = 'block';
		} else {
			dirtyPropertiesContainer.style.display = 'none';
		}
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
		const statusElements = {
			changed: document.querySelector('.in2publish-icon-status-changed'),
			added: document.querySelector('.in2publish-icon-status-added'),
			deleted: document.querySelector('.in2publish-icon-status-deleted'),
			moved: document.querySelector('.in2publish-icon-status-moved')
		};

		this.changedFilter = statusElements.changed?.classList.contains('in2publish-functions-bar--active') || false;
		this.addedFilter = statusElements.added?.classList.contains('in2publish-functions-bar--active') || false;
		this.deletedFilter = statusElements.deleted?.classList.contains('in2publish-functions-bar--active') || false;
		this.movedFilter = statusElements.moved?.classList.contains('in2publish-functions-bar--active') || false;

		if (this.changedFilter || this.addedFilter || this.deletedFilter || this.movedFilter) {
			const unchangedElements = document.querySelectorAll('.in2publish-stagelisting__item--unchanged');
			unchangedElements.forEach(el => {
				if (el?.parentElement) {
					el.parentElement.style.display = 'none';
				}
			});

			this.processFilteredElements('.in2publish-stagelisting__item--changed', this.changedFilter);
			this.processFilteredElements('.in2publish-stagelisting__item--added', this.addedFilter);
			this.processFilteredElements('.in2publish-stagelisting__item--deleted', this.deletedFilter);
			this.processFilteredElements('.in2publish-stagelisting__item--moved', this.movedFilter);
		} else {
			document.querySelectorAll('.in2publish-stagelisting__item').forEach(el => {
				if (el?.parentElement) {
					el.parentElement.style.display = 'block';
				}
			});
		}
	}

	static processFilteredElements(selector, filterStatus) {
		const elements = document.querySelectorAll(selector);
		elements.forEach(el => {
			if (el?.parentElement) {
				this.hideOrShowElements([el.parentElement], filterStatus);
			}
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

		const parentElement = element.parentElement?.closest('ul')?.previousElementSibling?.closest('.in2publish-stagelisting__item')?.parentElement;
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

		const searchForm = document.querySelector('.js-form-search');
		if (searchForm) {
			new DebounceEvent('input', event => {
				if (!event.target) return;

				const searchValue = event.target.value.toLowerCase();
				const elements = document.querySelectorAll('.in2publish-stagelisting__item');

				elements.forEach(item => {
					if (!item) return;

					if (searchValue !== '') {
						const searchable = item.getAttribute('data-searchable')?.toLowerCase() || '';
						item.classList.toggle('d-none', !searchable.includes(searchValue));
					} else {
						item.classList.remove('d-none');
					}
				});
			}, 250).bindTo(searchForm);

			const searchFormClear = document.querySelector('.js-form-search + .close');
			if (searchFormClear) {
				searchFormClear.addEventListener('click', () => {
					document.querySelectorAll('.in2publish-stagelisting__item')
						.forEach(item => {
							if (item) {
								item.classList.remove('d-none');
							}
						});
				});
			}
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
}

DocumentService.ready().then(() => {
	In2publishCoreModule.initialize();
});

export default In2publishCoreModule;
