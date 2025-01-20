'use strict';

/**
 * Module: @in2code/in2publish_core/context-menu-publish-entry.js
 */
class ContextMenuPublishEntry {
	static publishRecord(table, uid) {
		const url = `${TYPO3.settings.ajaxUrls['in2publishcore_contextmenupublishentry_publish']}&page=${uid}`;

		fetch(url)
			.then(response => response.json())
			.then(response => {
				if (response.success) {
					if (top?.TYPO3?.Backend?.NavigationContainer?.PageTree) {
						top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
					}
					top.TYPO3.Notification.success(response.message);
				} else {
					if (response.error) {
						top.TYPO3.Notification.error(response.message);
					} else {
						top.TYPO3.Notification.warning(response.message);
					}
				}
			})
			.catch(error => {
				top.TYPO3.Notification.error('Error publishing record', error.message);
			});
	}
}

export default ContextMenuPublishEntry;