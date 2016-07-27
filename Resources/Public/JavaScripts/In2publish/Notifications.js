/**
 * PublishNotification
 * @constructor
 */
function PublishNotification() {
	'use strict';

	/**
	 * Interval for request in ms
	 *
	 * @type {number}
	 */
	var interval = 5000;

	/**
	 * Write request to console.log() if no notifications found
	 *
	 * @type {boolean}
	 */
	var logToConsoleIfNotificationsEmpty = false;

	/**
	 * Initialize
	 *
	 * @returns {void}
	 */
	this.initialize = function() {
		Notification.requestPermission();
		setInterval(function() {
			getInstructionsFromAjax();
		}, interval);
	};

	/**
	 * Get URI to call
	 */
	var getAjaxUri = function() {
		return TYPO3.settings.In2publish.fireAndForgetUri;
	};

	/**
	 * Get messages from eID script
	 *
	 * @return {void}
	 */
	var getInstructionsFromAjax = function() {
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.overrideMimeType('application/json');
		xmlhttp.open('GET', getAjaxUri(), true);
		xmlhttp.onreadystatechange = function () {
			if (xmlhttp.readyState === 4 && xmlhttp.status === 200) {
				getInstructionsFromJson(xmlhttp.responseText);
			}
		};
		xmlhttp.send(null);
	};

	/**
	 * Get object from Json (normally called after AJAX request)
	 *
	 * @param {string} responseText
	 */
	var getInstructionsFromJson = function(responseText) {
		var instructions = JSON.parse(responseText);
		showMessages(instructions);
	};

	/**
	 * Show messages from json object
	 *
	 * @param {object} instructions
	 * @return {void}
	 */
	var showMessages = function(instructions) {
		if (instructions.error) {
			console.log(instructions.error);
		} else if (instructions.notifications !== undefined && instructions.notifications.length > 0) {
			for (var i = 0; i < instructions.notifications.length; i++) {
				notify(
					instructions.notifications[i].title,
					instructions.notifications[i].message,
					instructions.notifications[i].uri
				);
			}
		} else {
			if (logToConsoleIfNotificationsEmpty) {
				console.log('no instructions found in JSON');
				console.log(instructions);
			}
		}
	};

	/**
	 * Show notification message
	 *
	 * @param {string} title
	 * @param {string} message
	 * @param {string} uri
	 * @return {void}
	 */
	var notify = function(title, message, uri) {
		if (Notification.permission === 'granted') {
			var notification = new Notification(
				title,
				{
					body: message,
					icon: window.location.origin + '/typo3conf/ext/in2publish/Resources/Public/Images/server_green.png'
				}
			);
			if (uri !== undefined && uri) {
				notification.onclick = function() {
					openInNewWindow(uri);
				};
			}
		}
	};

	/**
	 * Open an URI in a new tab
	 *
	 * @param {string} uri
	 */
	var openInNewWindow = function(uri) {
		var win = window.open(uri, '_blank');
		win.focus();
	};
}

var in2publishNotification = new window.PublishNotification($);
in2publishNotification.initialize();
