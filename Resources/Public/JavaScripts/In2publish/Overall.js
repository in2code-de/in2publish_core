/**
 * In2publish Overall functions - for every module
 *
 * @params {jQuery} $
 * @class In2publishOverall
 */
function In2publishOverall($) {
	'use strict';

	/**
	 * Initialize
	 *
	 * @returns {void}
	 */
	this.initialize = function() {
		this.addPagestateChangeListener();
		this.addDateTimePicker();
		this.reloadPageTreeListener();
		typo3VersionToBodyTag();
	};

	/**
	 * Listen if a pagestate was changed
	 *
	 * @returns {void}
	 */
	this.addPagestateChangeListener = function() {
		$('*[data-in2publish-workflowstate="true"]:checked').each(function() {
			var $this = $(this);
			showOrHideScheduledPublishField($this.val(), getTargetFieldFromWorkflowStateChangeButton($this));
		});
		$('*[data-in2publish-workflowstate="true"]').change(function() {
			var $this = $(this);
			showOrHideScheduledPublishField($this.val(), getTargetFieldFromWorkflowStateChangeButton($this));
		});
	};

	/**
	 * Add datetimepicker for data-in2publish-datetimepicker="true" fields
	 *
	 * @returns {void}
	 */
	this.addDateTimePicker = function() {
		$('*[data-in2publish-datetimepicker="true"]').each(function() {
			var In2publishDateTimePicker = new window.In2publishDateTimePicker($);
			In2publishDateTimePicker.addDateTimePicker($(this));
		});
	};

	/**
	 * Reload page tree if data-in2publish-reloadtree="true"
	 *
	 * @returns {void}
	 */
	this.reloadPageTreeListener = function() {
		if ($('*[data-in2publish-reloadtree="true"]').length) {
			reloadPageTree();
		}
	};

	/**
	 * ****** Internal ******
	 */

	/**
	 * Get jQuery object of wrapping div of scheduled publish date field
	 *
	 * @param {jQuery} $workflowStateChangeButton
	 * @returns {jQuery}
	 */
	var getTargetFieldFromWorkflowStateChangeButton = function($workflowStateChangeButton)
	{
		var $targetField;
		if ($workflowStateChangeButton.closest('form').length) {
			$targetField = $workflowStateChangeButton
				.closest('form')
				.find('*[data-container="scheduledPublishField"]')
				.parent();
		} else {
			$targetField = $workflowStateChangeButton
				.closest('body')
				.find('*[data-container="scheduledPublishField"]')
				.parent();
		}
		return $targetField;
	};

	/**
	 * Show or hide datepicker
	 *
	 * @param {string} pageState
	 * @param {jQuery} $targetContainer container to show or hide
	 * @returns {void}
	 */
	var showOrHideScheduledPublishField = function(pageState, $targetContainer) {
		if (pageState === '1') {
			showScheduledPublishField($targetContainer);
		} else {
			hideScheduledPublishField($targetContainer);
		}
	};

	/**
	 * Show datepicker field
	 *
	 * @param {jQuery} $targetContainer container to show or hide
	 * @returns {void}
	 */
	var showScheduledPublishField = function($targetContainer) {
		$targetContainer.show();
	};

	/**
	 * Hide datepicker field
	 *
	 * @param {jQuery} $targetContainer container to show or hide
	 * @returns {void}
	 */
	var hideScheduledPublishField = function($targetContainer) {
		$targetContainer.hide();
	};

	/**
	 * Reload TYPO3 page tree
	 *
	 * @returns {void}
	 */
	var reloadPageTree = function() {
		if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
			top.content.nav_frame.refresh_nav();
		}
	};

	/**
	 * Set a css class to body if TYPO3 is 6.2 class="in2publish-t3-version-6_2"
	 */
	var typo3VersionToBodyTag = function() {
		if ($('*[data-in2publish-t3-version]').length) {
			var version = $('*[data-in2publish-t3-version]').data('in2publish-t3-version');
			version = parseFloat(version);
			if (version < 7.0) {
				$('body').addClass('in2publish-t3-version-6_2');
			}
		}
	};
}
