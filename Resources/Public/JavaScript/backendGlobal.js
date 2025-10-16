'use strict';

import Viewport from '@typo3/backend/viewport.js';
import { ModuleStateStorage } from '@typo3/backend/storage/module-state-storage.js';
class BackendGlobal {
	publishFilesModule = 'in2publish_core_m3';
	fileListModuleStateIdentifier = 'media';

	constructor() {
		this.enhancePublishingUrl();
	}

	/**
	 * Extends the URL for the “Publish Files” module.
	 * Adds the ID of the last folder selected in the file list module if it is missing from the URL.
	 */
	enhancePublishingUrl() {
		if (Viewport.ContentContainer) {
			const self = this;
			const ContentContainer = Viewport.ContentContainer;
			const originalSetUrl = ContentContainer.setUrl.bind(ContentContainer);

			ContentContainer.setUrl = function (urlToLoad, interactionRequest, module) {
				let modifiedUrl = urlToLoad;

				if (module === self.publishFilesModule && !urlToLoad.includes('id=')) {
					const fileListState = ModuleStateStorage.current(self.fileListModuleStateIdentifier);
					if (fileListState.identifier) {
						const separator = modifiedUrl.includes('?') ? '&' : '?';
						modifiedUrl += `${separator}id=${encodeURIComponent(fileListState.identifier)}`;
					}
				}

				return originalSetUrl(modifiedUrl, interactionRequest, module);
			};
		}
	}
}

export default new BackendGlobal();

