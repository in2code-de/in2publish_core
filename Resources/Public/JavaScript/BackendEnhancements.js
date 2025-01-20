'use strict';

/**
 * Module: TYPO3/CMS/In2publishCore/BackendEnhancements
 */
import nprogress from 'nprogress';

class BackendEnhancements {
	constructor() {
		this.registerNprogressHandler();
	}

	registerNprogressHandler() {
		document.querySelectorAll('.t3-js-jumpMenuBox').forEach(
			element => element.addEventListener(
				'change', () => nprogress.start()
			)
		);
	}
}

export default new BackendEnhancements();