/**
 * Get jQuery object for TYPO3 6.2 or newer
 *
 * @class In2publishJquery
 */
function In2publishJquery() {
	'use strict';

	/**
	 * This class
	 *
	 * @type {In2publishPageModule}
	 */
	var that = this;

	/**
	 * Relative path to jQuery
	 *
	 * @type {string}
	 */
	this.jQueryPath = '../../../../../typo3conf/ext/in2publish/Resources/Public/JavaScripts/Libraries/jquery-1.11.2.min.js';

	/**
	 * get jQuery and call class and method
	 *
	 * @params {string} instanceName
	 * @params {string} method
	 * @returns {void}
	 */
	this.getJqueryAndCallInstance = function(instanceName, method) {
		if (TYPO3.jQuery === undefined) {
			this.buildJqueryTagAndCallInstance(instanceName, method);
		} else {
			TYPO3.jQuery(document).ready(function() {
				that.callInstance(instanceName, method, TYPO3.jQuery);
			});
		}
	};

	/**
	 * build jQuery tag and call class and method
	 *
	 * @params {string} instanceName
	 * @params {string} method
	 * @returns {void}
	 */
	this.buildJqueryTagAndCallInstance = function(instanceName, method) {
		var tag = document.createElement('script');
		tag.type = 'text/javascript';
		tag.src = this.jQueryPath;
		document.getElementsByTagName('head')[0].appendChild(tag);
		tag.onload = function() {
			$.noConflict();
			jQuery(document).ready(function() {
				that.callInstance(instanceName, method, jQuery);
			});
		};
	};

	/**
	 * Call class and method
	 *
	 * @params {string} instanceName
	 * @params {string} method
	 * @returns {void}
	 */
	this.callInstance = function(instanceName, method, jQuery) {
		var In2publishPageModule = new window[instanceName](jQuery);
		In2publishPageModule[method]();
	};
}
