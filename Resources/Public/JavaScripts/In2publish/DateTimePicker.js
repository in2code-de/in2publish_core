/**
 * In2publish Overall functions - for every module
 *
 * @params {jQuery} $
 * @class In2publishDateTimePicker
 */
function In2publishDateTimePicker($) {
	'use strict';

	/**
	 * This class
	 *
	 * @type {In2publishDateTimePicker}
	 */
	var that = this;

	/**
	 * Timeframe for scheduled publish date
	 *
	 * @type {number}
	 */
	var daysForScheduledDate = 30;

	/**
	 * @type {int}
	 */
	var scheduledPublish = 0;

	/**
	 * @type {null|jQuery}
	 */
	var $timer = null;

	/**
	 * @type {null|jQuery}
	 */
	var $clearValueTag = null;

	/**
	 * Add datetimepicker for data-in2publish-datetimepicker="true" fields
	 *
	 * @param {jQuery} $datePickerElement
	 * @returns {void}
	 */
	this.addDateTimePicker = function($datePickerElement) {
		convertTimestampValueToReadableDate($datePickerElement);

		var $datePickerContainer = $('<span />').appendTo($datePickerElement.parent());
		var now = new Date();
		var soon = new Date();
		soon.setDate(now.getDate() + daysForScheduledDate);
		$datePickerElement.prop('readonly', 'readonly');

		new Pikaday ({
			field: $datePickerElement.get(0),
			container: $datePickerContainer.get(0),
			minDate: now,
			maxDate: soon,
			firstDay: 1,
			bound: true,
			defaultDate: now,
			setDefaultDate: false, // would initially set date and time (but not only one of them)
			showTime: true,
			showSeconds: false,
			use24hour: true,
			i18n: {
				previousMonth: '<',
				nextMonth: '>',
				months: getMonthsFromDatepickerInputField($datePickerElement),
				weekdays: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
				weekdaysShort: getWeekdaysFromDatepickerInputField($datePickerElement)
			},
			onSelect: function() {
				if (getTimeLeft(this.toString())) {
					scheduledPublish = getTimestampFromDate(this.toString());
					$datePickerElement.val(getReadableTimeFromDate(this.toString()));
					$datePickerElement.siblings('*[data-in2publish-datetimepicker-timestamp="true"]').val(getTimestampFromDate(this.toString()));
					createTimerToSibling($datePickerElement, this);
					addClearValueTag($datePickerElement);
				} else {
					clearDateField($datePickerElement);
				}
			}
		});
	};

	/**
	 * ****** Internal ******
	 */

	/**
	 * Add clear value tag
	 *
	 * @param $date
	 * @returns {void}
	 */
	var addClearValueTag = function($date) {
		if ($clearValueTag === null) {
			$clearValueTag = $('<span />')
				.addClass('in2publish-removevalue')
				.addClass('in2publish-icon-x-altx-alt')
				.appendTo($date.parent())
				.click(function () {
					clearDateField($date);
				});
		}
		$clearValueTag.show();
	};

	/**
	 * Clear date field value
	 *
	 * @param $date
	 * @returns {void}
	 */
	var clearDateField = function($date) {
		scheduledPublish = 0;
		hideTimerToSibling();
		$date.val('');
		$date.siblings('*[data-in2publish-datetimepicker-timestamp="true"]').val('');
		if ($clearValueTag !== null) {
			$clearValueTag.hide();
		}
	};

	/**
	 * Prefill a date field with date instead of timestamp
	 *
	 * @param {jQuery} $date
	 * @returns {void}
	 */
	var convertTimestampValueToReadableDate = function($date) {
		var initialValue = $date.val();
		if (initialValue > 0) {
			$date.val(getReadableTimeFromDate(initialValue * 1000));
			createTimerToSibling($date, new Date(initialValue * 1000));
			addClearValueTag($date);
		}
		if (initialValue === '0') {
			clearDateField($date);
		}
	};

	/**
	 * Get months from data-in2publish-datetimepicker-months="" attribute
	 *
	 * @param {jQuery} $field
	 * @returns {string[]}
	 */
	var getMonthsFromDatepickerInputField = function($field) {
		var months = [
			'January',
			'February',
			'March',
			'April',
			'May',
			'June',
			'July',
			'August',
			'September',
			'October',
			'November',
			'December'
		];
		if ($field.data('in2publish-datetimepicker-months') !== '') {
			months = $field.data('in2publish-datetimepicker-months').split(',');
		}
		return months;
	};

	/**
	 * Get weekdays from data-in2publish-datetimepicker-days="" attribute
	 *
	 * @param {jQuery} $field
	 * @returns {string[]}
	 */
	var getWeekdaysFromDatepickerInputField = function($field) {
		var weekdays = [
			'Sun',
			'Mon',
			'Tue',
			'Wed',
			'Thu',
			'Fri',
			'Sat'
		];
		if ($field.data('in2publish-datetimepicker-days') !== '') {
			weekdays = $field.data('in2publish-datetimepicker-days').split(',');
		}
		return weekdays;
	};

	/**
	 * Get time left "Wed Sep 23 2015 00:00:00 GMT+0200 (CEST)"
	 * 		Format hh:mm
	 *
	 * @param {jQuery} $date
	 * @returns {string}
	 */
	var getTimeLeft = function($date) {
		var date = new Date($date);
		var now = new Date();
		var timestampFuture = date.getTime();
		var timestampNow = now.getTime();
		var deltaTimestamp = (timestampFuture - timestampNow);
		var hoursLeft = deltaTimestamp / 1000 / 60 / 60;
		var delta = new Date(timestampFuture - timestampNow);
		var minutesLeft = ('0' + delta.getMinutes()).slice(-2);
		return Math.floor(hoursLeft) + ':' + minutesLeft;
	};

	/**
	 * Get timestamp from date format like "Wed Sep 23 2015 00:00:00 GMT+0200 (CEST)"
	 *
	 * @param {jQuery} $date
	 * @returns {int}
	 */
	var getTimestampFromDate = function($date) {
		var date = new Date($date);
		return date.getTime() / 1000;
	};

	/**
	 * Get readable time from date format like "Wed Sep 23 2015 00:00:00 GMT+0200 (CEST)"
	 * 		Format dd.mm.yyyy
	 *
	 * @param {jQuery} $date
	 * @returns {string}
	 */
	var getReadableTimeFromDate = function($date) {
		var date = new Date($date);
		var year = date.getFullYear();
		var month = ('0' + (date.getMonth() + 1)).slice(-2);
		var day = ('0' + date.getDate()).slice(-2);
		var hour = ('0' + date.getHours()).slice(-2);
		var minute = ('0' + date.getMinutes()).slice(-2);
		return day + '.' + month + '.' + year + ' ' + hour + ':' + minute;
	};

	/**
	 * Create time left tag
	 *
	 * @param {jQuery} $sibling
	 * @param {date} date
	 * @returns {void}
	 */
	var createTimerToSibling = function($sibling, date) {
		if ($timer === null) {
			$timer = $('<span />')
				.addClass('in2publish__workflowcontainer__timer')
				.addClass('in2publish-icon-clock2')
				.prependTo($sibling.parent());
		}
		var label = getTimeLeft(date.toString());
		label += ' ' + $sibling.data('in2publish-datetimepicker-timerlabel');
		$timer.html(label).show();
	};

	/**
	 * Hide timer
	 *
	 * @returns {void}
	 */
	var hideTimerToSibling = function() {
		if ($timer !== null) {
			$timer.hide();
		}
	};
}
