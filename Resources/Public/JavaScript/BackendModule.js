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
			this.setFilterForPageView();
			this.filterButtonsListener();
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
			document.querySelectorAll('.in2publish-stagelisting__item').forEach(el => el.style.display = 'block');
		}
	}

	static toggleDirtyPropertiesListContainerListener() {
		document.querySelectorAll('[data-action="opendirtypropertieslistcontainer"]')
			.forEach(el => el.addEventListener('click', (event) => this.toggleDirtyPropertiesListContainer(event)));
	}

	static toggleDirtyPropertiesListContainer(event) {
		const target = event.currentTarget;
		const row = target.closest('.in2publish-stagelisting__item');
		const dirtyPropertiesContainer = row.querySelector('.in2publish-stagelisting__dropdown');

		dirtyPropertiesContainer.classList.toggle('in2publish-stagelisting__dropdown--close');
		dirtyPropertiesContainer.classList.toggle('in2publish-stagelisting__dropdown--open');

		// Add display toggle
		if (dirtyPropertiesContainer.classList.contains('in2publish-stagelisting__dropdown--open')) {
			dirtyPropertiesContainer.style.display = 'block';
		} else {
			dirtyPropertiesContainer.style.display = 'none';
		}
	}

	static openOrCloseStageListingDropdownContainer(container) {
		if (container.classList.contains('in2publish-stagelisting__dropdown--close')) {
			document.querySelectorAll('.in2publish-stagelisting__dropdown--open').forEach(el => {
				el.classList.remove('in2publish-stagelisting__dropdown--open');
				el.classList.add('in2publish-stagelisting__dropdown--close');
				el.style.display = 'none';
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
		this.changedFilter = document.querySelector('.in2publish-icon-status-changed')
			.classList.contains('in2publish-functions-bar--active');
		this.addedFilter = document.querySelector('.in2publish-icon-status-added')
			.classList.contains('in2publish-functions-bar--active');
		this.deletedFilter = document.querySelector('.in2publish-icon-status-deleted')
			.classList.contains('in2publish-functions-bar--active');
		this.movedFilter = document.querySelector('.in2publish-icon-status-moved')
			.classList.contains('in2publish-functions-bar--active');

		if (this.changedFilter || this.addedFilter || this.deletedFilter || this.movedFilter) {
			document.querySelectorAll('.in2publish-stagelisting__item--unchanged')
				.forEach(el => el.parentElement.style.display = 'none');
			this.hideOrShowElements(
				Array.from(document.querySelectorAll('.in2publish-stagelisting__item--changed'))
					.map(el => el.parentElement),
				this.changedFilter
			);
			this.hideOrShowElements(
				Array.from(document.querySelectorAll('.in2publish-stagelisting__item--added'))
					.map(el => el.parentElement),
				this.addedFilter
			);
			this.hideOrShowElements(
				Array.from(document.querySelectorAll('.in2publish-stagelisting__item--deleted'))
					.map(el => el.parentElement),
				this.deletedFilter
			);
			this.hideOrShowElements(
				Array.from(document.querySelectorAll('.in2publish-stagelisting__item--moved'))
					.map(el => el.parentElement),
				this.movedFilter
			);
		} else {
			document.querySelectorAll('.in2publish-stagelisting__item')
				.forEach(el => el.parentElement.style.display = 'block');
		}
	}

	static hideOrShowElements(elements, status) {
		elements.forEach(element => {
			if (status) {
				element.style.display = 'block';
				this.showParentElements(element);
			} else {
				element.style.display = 'none';
			}
		});
	}

	static showParentElements(element) {
		const parentElement = element.parentElement?.closest('ul')?.previousElementSibling?.closest('.in2publish-stagelisting__item')?.parentElement;
		if (parentElement) {
			parentElement.style.display = 'block';
			this.showParentElements(parentElement);
		}
	}

	static filterButtonsListener() {
		document.querySelectorAll('*[data-action-toggle-filter-status]').forEach(element => {
			element.addEventListener('click', (e) => {
				e.preventDefault();
				element.classList.toggle('in2publish-functions-bar--active');
				fetch(element.getAttribute('href'));
				In2publishCoreModule.setFilterForPageView();
			});
		});
	}

	static setupFilterListeners() {
		const filters = document.querySelectorAll('.js-in2publish-filter');

		Array.from(filters).forEach(filter => {
			filter.addEventListener('click', event => {
				const input = event.currentTarget;
				input.disabled = true;
				fetch(input.getAttribute('data-href'))
					.then(response => response.json())
					.finally(() => {
						setTimeout(() => input.disabled = false, 100);
					});

				this.filterItemsByStatus();
			});
		});

		const searchForm = document.querySelector('.js-form-search');
		if (searchForm) {
			new DebounceEvent('input', event => {
				const searchValue = event.target.value.toLowerCase();
				const elements = document.querySelectorAll('.in2publish-stagelisting__item');

				Array.from(elements).forEach(item => {
					if (searchValue !== '') {
						const searchable = item.getAttribute('data-searchable').toLowerCase();
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
						.forEach(item => item.classList.remove('d-none'));
				});
			}
		}
	}

	static setupClearableInputs() {
		Array.from(document.querySelectorAll('.t3js-clearable'))
			.forEach(input => input.clearable());
	}
}

DocumentService.ready().then(() => {
	In2publishCoreModule.initialize();
});

export default In2publishCoreModule;