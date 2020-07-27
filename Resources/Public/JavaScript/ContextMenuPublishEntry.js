/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/In2publishCore/ContextMenuPublishEntry
 *
 * @exports TYPO3/CMS/In2publishCore/ContextMenuPublishEntry
 */
define(function () {
	'use strict';

	/**
	 * @exports TYPO3/CMS/In2publishCore/ContextMenuPublishEntry
	 */
	var ContextMenuPublishEntry = {};

	ContextMenuPublishEntry.publishRecord = function (table, uid) {
		var url = TYPO3.settings.ajaxUrls['in2publishcore_contextmenupublishentry_publish'];
		url += '&page=' + uid;
		$.ajax(url).done(function (response) {
			if (response.success) {
				if (top && top.TYPO3.Backend && top.TYPO3.Backend.NavigationContainer.PageTree) {
					top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
				}
				top.TYPO3.Notification.success(
					response.message
				);
			} else {
				if (response.error) {
					top.TYPO3.Notification.error(
						response.message
					);
				} else {
					top.TYPO3.Notification.warning(
						response.message
					)
				}
			}

		});
	};

	return ContextMenuPublishEntry;
});
