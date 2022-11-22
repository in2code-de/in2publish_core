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
 * Module: TYPO3/CMS/In2publishCore/BackendEnhancements
 *
 * @exports TYPO3/CMS/In2publishCore/BackendEnhancements
 */
define(['nprogress'], function (nprogress) {
	'use strict';

	class BackendEnhancements {

		constructor() {
			console.log(nprogress)
			this.registerNprogressHandler()
		}

		registerNprogressHandler() {
			document.querySelectorAll('.t3-js-jumpMenuBox').forEach(
				element => element.addEventListener(
					'change', event => nprogress.start()
				)
			)
		}
	}

	return new BackendEnhancements();
});
